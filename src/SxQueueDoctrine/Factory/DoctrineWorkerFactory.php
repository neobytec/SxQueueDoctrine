<?php
namespace SxQueueDoctrine\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SxQueueDoctrine\Worker\DoctrineWorker;

/**
 * WorkerFactory
 */
class DoctrineWorkerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $queuePluginManager = $serviceLocator->get('SxQueue\Queue\QueuePluginManager');

        return new DoctrineWorker($queuePluginManager);
    }
}
