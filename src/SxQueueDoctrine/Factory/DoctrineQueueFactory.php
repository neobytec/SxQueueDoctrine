<?php

namespace SxQueueDoctrine\Factory;

use SxQueueDoctrine\Options\DoctrineOptions;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SxQueueDoctrine\Queue\DoctrineQueue;

/**
 * DoctrineQueueFactory
 */
class DoctrineQueueFactory implements FactoryInterface
{
    /**
     * @param $requestedName: Nombre de la cola a ejecutar (default por defecto)
     */
    public function createService(ServiceLocatorInterface $serviceLocator, $name = '', $requestedName = '')
    {
        $sl = $serviceLocator->getServiceLocator();

        // Configuración de las colas específica para Doctrine
        $config        = $sl->get('Config');
        $queuesOptions = $config['sx_queue']['queues'];
        $options       = isset($queuesOptions[$requestedName]) ? $queuesOptions[$requestedName] : array();
        $queueOptions  = new DoctrineOptions($options);

        // Gestor de  Jobs: invokables y controllers definidos en el array de configuración key=job_manager
        $jobPluginManager = $sl->get('SxQueue\Job\JobPluginManager');

        // Doctrine get entity manager
        $entityManager = $sl->get('EntityManager');


        $queue = new DoctrineQueue($entityManager, $queueOptions, $requestedName, $jobPluginManager);

        return $queue;
    }
}
