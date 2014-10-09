<?php

return array(
    'service_manager' => array(
        'factories' => array(
            'SxQueueDoctrine\Worker\DoctrineWorker'    => 'SxQueueDoctrine\Factory\DoctrineWorkerFactory',
        )
    ),

    'controllers' => array(
        'factories' => array(
            'SxQueueDoctrine\Controller\DoctrineWorkerController' => 'SxQueueDoctrine\Factory\DoctrineWorkerControllerFactory',
        ),
    ),

    'console'   => array(
        'router' => array(
            'routes' => array(
                'sx-queue-doctrine-worker' => array(
                    'type'    => 'Simple',
                    'options' => array(
                        'route'    => 'queue doctrine <queue> [--timeout=] --start',
                        'defaults' => array(
                            'controller' => 'SxQueueDoctrine\Controller\DoctrineWorkerController',
                            'action'     => 'process'
                        ),
                    ),
                ),
            ),
        ),
    ),
);
