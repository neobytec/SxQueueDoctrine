<?php

namespace SxQueueDoctrine\Worker;

use Exception;
use SxQueue\Job\JobInterface;
use SxQueue\Queue\QueueInterface;
use SxQueue\Worker\AbstractWorker;
use SxQueueDoctrine\Queue\DoctrineQueueInterface;
use SxQueueDoctrine\Job\Exception as JobException;

/**
 * Worker for Doctrine
 */
class DoctrineWorker extends AbstractWorker
{
    /**
     * {@inheritDoc}
     */
    public function processJob(JobInterface $job, QueueInterface $queue)
    {
        if (!$queue instanceof DoctrineQueueInterface) {
            return;
        }

        try {
            $job->execute($queue);
            $queue->delete($job);
            $job->setResult($job::JOB_STATUS_COMPLETED);
        } catch (Exception $exception) {
            $job->setResult($job::JOB_STATUS_FAILED);
            echo "ERROR:".$exception->getMessage();
            $queue->failed($job, array('message' => $exception->getMessage(),
                                       'trace' => $exception->getTraceAsString()));
        }
    }
}
