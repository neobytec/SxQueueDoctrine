<?php

namespace SxQueueDoctrine\Options;

use SxQueueDoctrine\Queue\DoctrineQueue;
use Zend\Stdlib\AbstractOptions;

/**
 * DoctrineOptions
 */
class DoctrineOptions extends AbstractOptions
{

    /**
     * Entity name which should be used to store jobs
     *
     * @var string
     */
    protected $entityName = '';

    /**
     * how long to keep deleted (successful) jobs (in minutes)
     *
     * @var int
     */
    protected $deletedLifetime = DoctrineQueue::LIFETIME_DISABLED;

    /**
     * how long to keep failed jobs (in minutes)
     *
     * @var int
     */
    protected $failedLifetime = DoctrineQueue::LIFETIME_DISABLED;

    /**
     * How long show we sleep when no jobs available for processing (in seconds)
     *
     * @var int
     */
    protected $sleepWhenIdle = 1;

    /**
     * Numero de intentos máximos de ejecución hasta dar por cancelada una tarea.
     *
     * @var int
     */
    protected $maxAttempts = 5;


    /**
     * @param  int  $maxAttemps
     * @return void
     */
    public function setMaxAttempts($maxAttempts)
    {
        $this->maxAttempts = (int) $maxAttempts;
    }

    /**
     * @return int
     */
    public function getMaxAttempts()
    {
        return $this->maxAttempts;
    }

    /**
     * @param  int  $failedLifetime
     * @return void
     */
    public function setFailedLifetime($failedLifetime)
    {
        $this->failedLifetime = (int) $failedLifetime;
    }

    /**
     * @return int
     */
    public function getFailedLifetime()
    {
        return $this->failedLifetime;
    }

    /**
     * @param  int  $deletedLifetime
     * @return void
     */
    public function setDeletedLifetime($deletedLifetime)
    {
        $this->deletedLifetime = (int) $deletedLifetime;
    }

    /**
     * @return int
     */
    public function getDeletedLifetime()
    {
        return $this->deletedLifetime;
    }

    /**
     * @param  string $entityName
     * @return void
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @param int $sleepWhenIdle
     */
    public function setSleepWhenIdle($sleepWhenIdle)
    {
        $this->sleepWhenIdle = $sleepWhenIdle;
    }

    /**
     * @return int
     */
    public function getSleepWhenIdle()
    {
        return $this->sleepWhenIdle;
    }
}
