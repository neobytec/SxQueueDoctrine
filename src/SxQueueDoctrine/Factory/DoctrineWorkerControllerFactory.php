<?php

namespace SxQueueDoctrine\Factory;

use SxQueueDoctrine\Controller\DoctrineWorkerController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * WorkerFactory
 */
class DoctrineWorkerControllerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $worker = $serviceLocator->getServiceLocator()
                                 ->get('SxQueueDoctrine\Worker\DoctrineWorker');

        return new DoctrineWorkerController($worker);
    }
}
