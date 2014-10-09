<?php

namespace SxQueueDoctrine\Queue;

use DateTime;
use DateTimeZone;
use DateInterval;

// Doctrine DBAL
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\LockMode;  // Para usar con bloqueos de tabla
use Doctrine\DBAL\Types\Type;

// Doctrine ORM
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;

// QUEUE
use SxQueue\Queue\AbstractQueue;
use SxQueue\Job\JobInterface;
use SxQueue\Job\JobPluginManager;
use SxQueueDoctrine\Exception;
use SxQueueDoctrine\Options\DoctrineOptions;

class DoctrineQueue extends AbstractQueue implements DoctrineQueueInterface
{
    const STATUS_PENDING = 1;
    const STATUS_RUNNING = 2;
    const STATUS_DELETED = 3;
    const STATUS_FAILED  = 4;

    const LIFETIME_DISABLED  = 0;
    const LIFETIME_UNLIMITED = -1;

    /**
     * Options for this queue
     *
     * @var DoctrineOptions $options
     */
    protected $options;

    /**
     * Doctrine Entity Manager
     *
     * @var EntityManager $entityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param EntityManager    $entityManager: Entity manager de doctrine
     * @param string           $tableName: Opciones relacionadas con el módulo de docrine
     * @param string           $name: Nombre de la cola (default por defecto)
     * @param JobPluginManager $jobPluginManager: Manager que tiene un array de invokables
     *                                            y factories con los Jobs
     */
    public function __construct(
        EntityManager $entityManager,
        DoctrineOptions $options,
        $name,
        JobPluginManager $jobPluginManager
        )
    {
        if (!$entityManager) {
            throw new Exception\EntityManagerNotFoundException();
        } else {
            $this->em  = $entityManager;
        }
        $this->options = clone $options;

        parent::__construct($name, $jobPluginManager);
    }

    /**
     * @return \SxQueueDoctrine\Options\DoctrineOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    private function printLog($message)
    {
        $now = new Datetime;
        echo "\n" . $now->format("Y-m-s H:i:s") . 
             " [" . $this->getName() . "] " . $message;
    }


    /**
     * Inserta en la cola de tareas un nuevo Job
     *
     * Note : see DoctrineQueue::parseOptionsToDateTime for schedule and delay options
     */
    public function push(JobInterface $job, array $options = array())
    {
        $entityName = $this->options->getEntityName();
        $scheduled  = $this->parseOptionsToDateTime($options);
        
        $entityObject = new $entityName;

        $entityObject->setQueue($this->getName())
                    ->setData($job->jsonSerialize())
                    ->setStatus(self::STATUS_PENDING)
                    ->setDateScheduled($scheduled);
        $this->em->persist($entityObject);
        $this->em->flush();

        $job->setId($entityObject->getId());
    }

    /**
     *  Obtiene de BBDD jobs pendientes de ejecución
     */
    public function pop(array $options = array())
    {
        
        // Limpia tareas marcadas para borrar o completadas.
        $this->printLog("Limpiando tabla.");
        $this->purge();

        $this->printLog("Buscando tareas...");

        try {
             // Borramos cache. Clear "descuelga" las entidades del manager.
            $this->em->clear();
            $this->em->getConnection()->beginTransaction();

            $entityName   = $this->options->getEntityName();

            $queryBuilder = $this->em->createQueryBuilder();
            $selectQuery  = $queryBuilder->select('q')
                        ->from($entityName, 'q')
                        ->where(
                            'q.queue = ?1 AND
                             q.status = ?2 AND
                             q.dateScheduled <= ?3 AND
                             q.attempts < ?4'
                        )
                        ->orderBy('q.dateScheduled', 'ASC')
                        ->setParameters([
                            1 => $this->getName(),
                            2 => static::STATUS_PENDING,
                            3 => new DateTime,
                            4 => $this->options->getMaxAttempts()
                        ])
                        ->setMaxResults(1)
                        ->getQuery();
    
            $selectQuery->setLockMode(LockMode::PESSIMISTIC_WRITE);
            $queryResult = $selectQuery->getResult();
           

            // Si no se obtienen datos de BBDD, se activa el tiempo de idle configurado 
            // antes del siguiente bucle
            if (empty($queryResult)) {
                sleep($this->options->getSleepWhenIdle());
                $this->em->getConnection()->rollback();
                return null;
            }

            $jobId       = $queryResult[0]->getId();
            $jobAttempts = $queryResult[0]->getAttempts();
            $jobData     = $queryResult[0]->getData();

            $this->printLog("Ejecutando nuevo Job. Id: ".$jobId);

            // Actualizar job
            $queryBuilder = $this->em->createQueryBuilder();    
            $updateQuery = $queryBuilder->update($entityName, 'qu')  
                    ->set('qu.attempts', '?1')
                    ->set('qu.dateExecuted', '?2')
                    ->set('qu.status', '?3')
                    ->where('qu.id = ?4')  
                    ->setParameters([
                        1 => (int)$jobAttempts + 1,
                        2 => new Datetime,
                        3 => static::STATUS_RUNNING,
                        4 => $jobId,
                    ])
                    ->getQuery();  
            $updateResult = $updateQuery->execute();

            $this->em->flush();
            $this->em->getConnection()->commit();

        } catch (ORMException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        $data = json_decode($jobData, true);
        $data['metadata']['id'] = $jobId;

        return $this->createJob($data['class'], $data['content'], $data['metadata']);
    }


    /**
     * Borrar un registro o lo marca para ser borrado dependiendo de la configuración.
     *
     * Note: When $deletedLifetime === 0 the job will be deleted immediately
     */
    public function delete(JobInterface $job, array $options = array())
    {
        if ($this->options->getDeletedLifetime() === static::LIFETIME_DISABLED) {
            $this->printLog("Borrado inmediato.");
            $ref = $this->em->getReference($this->options->getEntityName(), $job->getId());
            $this->em->remove($ref);
            $this->em->flush();

        } else {
            $this->printLog("Update status: pendiente de borrar.");
            $queryBuilder = $this->em->createQueryBuilder();    
            $updateQuery = $queryBuilder->update($entityName = $this->options->getEntityName(), 'q')  
                    ->set('q.status', '?1')
                    ->set('q.dateFinished', '?2')
                    ->where('q.id = ?3 AND q.status = ?4')
                    ->setParameters([
                        1 => static::STATUS_DELETED,
                        2 => new Datetime,
                        3 => $job->getId(),
                        4 => static::STATUS_RUNNING
                    ])
                    ->getQuery();

            $updateResult = $updateQuery->execute();
            $this->em->flush();
        }
    }

    /**
     * Marca una tarea como ejecutada con error y la vuelve a poner en cola.
     * Almacena el mensaje de error en el registro.
     *
     * Note: When $failedLifetime === 0 the job will be deleted immediately
     */
    public function failed(JobInterface $job, array $options = array())
    {
        if ($this->options->getFailedLifetime() === static::LIFETIME_DISABLED) {
            $this->printLog("Borrando registro con errores. Id:".$job->getId());

            $ref = $this->em->getReference($this->options->getEntityName(), $job->getId());
            $this->em->remove($ref);
        } else {
            $this->printLog("Guardando errores en tarea...".$job->getId());
            $message = isset($options['message']) ? $options['message'] : null;
            $trace   = isset($options['trace']) ? $options['trace'] : null;

            // Sacar información relativa al job y configura el update según condiciones
            $entityName = $this->options->getEntityName();
            $jobData    = $this->em->getRepository($entityName)->find($job->getId());
            
            // Actualizar job con los errores
            $queryBuilder = $this->em->createQueryBuilder();
            $query = $queryBuilder->update($entityName, 'q')  
                    ->set('q.dateFailed', '?1')
                    ->set('q.message', '?2')
                    ->set('q.trace', '?3')
                    ->where('q.id = ?4')  
                    ->setParameters([
                        1 => new DateTime,
                        2 => $message,
                        3 => $trace,
                        4 => $job->getId(),
                    ]);


            if ($jobData->getAttempts() >= ($this->options->getMaxAttempts()-1)) {
                $query->set('q.dateFinished',':dateFinished')
                      ->set('q.status', ':status')
                      ->setParameter('dateFinished', new Datetime)
                      ->setParameter('status', static::STATUS_FAILED);
            } else {
                $query->set('q.status', ':status')
                      ->setParameter('status', static::STATUS_PENDING);
            }
            $q = $query->getQuery();
            $q->execute();
        }
    }

    /**
     * Borra jobs antiguos según configuración para deleted y failed.
     */
    protected function purge()
    {
        $entityName = $this->options->getEntityName();

        // Trabajos marcados con "failed" (error).
        if ($this->options->getFailedLifetime() > static::LIFETIME_UNLIMITED) {

            $failedLifetime = $this->parseOptionsToDateTime(array('delay' => - ($this->options->getFailedLifetime() * 60)));

            $queryBuilder = $this->em->createQueryBuilder();
            $deleteQuery  = $queryBuilder->delete($entityName, 'q')  
                    ->where('q.dateFinished < ?1 AND q.status = ?2 AND q.queue = ?3 AND q.dateFinished IS NOT NULL')  
                    ->setParameters([
                        1 => $failedLifetime,
                        2 => static::STATUS_FAILED,
                        3 => $this->getName()
                    ])
                    ->getQuery();
            $deleteQuery->execute();
        }

        // Trabajos marcados con "deleted"
        if ($this->options->getDeletedLifetime() > static::LIFETIME_UNLIMITED) {

            $deletedLifetime = $this->parseOptionsToDateTime(array('delay' => - ($this->options->getDeletedLifetime() * 60)));
            
            $queryBuilder = $this->em->createQueryBuilder();
            $deleteQuery  = $queryBuilder->delete($entityName, 'q')  
                    ->where('q.dateFinished < ?1 AND q.status = ?2 AND q.queue = ?3 AND q.dateFinished IS NOT NULL')  
                    ->setParameters([
                        1 => $deletedLifetime,
                        2 => static::STATUS_DELETED,
                        3 => $this->getName()
                    ])
                    ->getQuery();
            $deleteQuery->execute();
        }
    }

    /**
     * Parses options to a datetime object
     *
     * valid options keys:
     *
     * scheduled: the time when the job will be scheduled to run next
     * - numeric string or integer - interpreted as a timestamp
     * - string parserable by the DateTime object
     * - DateTime instance
     * delay: the delay before a job become available to be popped (defaults to 0 - no delay -)
     * - numeric string or integer - interpreted as seconds
     * - string parserable (ISO 8601 duration) by DateTimeInterval::__construct
     * - string parserable (relative parts) by DateTimeInterval::createFromDateString
     * - DateTimeInterval instance
     *
     * @see http://en.wikipedia.org/wiki/Iso8601#Durations
     * @see http://www.php.net/manual/en/datetime.formats.relative.php
     *
     * @param $options array
     * @return DateTime
     */
    protected function parseOptionsToDateTime($options)
    {

        $now       = new DateTime(null, new DateTimeZone(date_default_timezone_get()));
        $now  = new Datetime;
        $scheduled = clone ($now);

        if (isset($options['scheduled'])) {
            switch (true) {
                case is_numeric($options['scheduled']):
                    $scheduled = new DateTime(
                        sprintf("@%d", (int) $options['scheduled']),
                        new DateTimeZone(date_default_timezone_get())
                    );
                    break;
                case is_string($options['scheduled']):
                    $scheduled = new DateTime($options['scheduled'], new DateTimeZone(date_default_timezone_get()));
                    break;
                case $options['scheduled'] instanceof DateTime:
                    $scheduled = $options['scheduled'];
                    break;
            }
        }

        if (isset($options['delay'])) {
            switch (true) {
                case is_numeric($options['delay']):
                    $delay = new DateInterval(sprintf("PT%dS", abs((int) $options['delay'])));
                    $delay->invert = ($options['delay'] < 0) ? 1 : 0;
                    break;
                case is_string($options['delay']):
                    try {
                        // first try ISO 8601 duration specification
                        $delay = new DateInterval($options['delay']);
                    } catch (\Exception $e) {
                        // then try normal date parser
                        $delay = DateInterval::createFromDateString($options['delay']);
                    }
                    break;
                case $options['delay'] instanceof DateInterval:
                    $delay = $options['delay'];
                    break;
                default:
                    $delay = null;
            }

            if ($delay instanceof DateInterval) {
                $scheduled->add($delay);
            }
        }

        return $scheduled;
    }
}
