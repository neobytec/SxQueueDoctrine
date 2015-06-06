<?php

namespace SxQueueDoctrine\Factory;

use SxQueueDoctrine\Controller\DoctrineWorkerController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SxQueueDoctrine\Controller\SxQueueDoctrine\Controller;

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
        return new DoctrineWorkerController();
    }
}
