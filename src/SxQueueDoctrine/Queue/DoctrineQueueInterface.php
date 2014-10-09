<?php

namespace SxQueueDoctrine\Queue;

use SxQueue\Queue\QueueInterface;
use SxQueue\Job\JobInterface;

interface DoctrineQueueInterface extends QueueInterface
{
    /**
     * Bury a job. When a job is buried, it won't be retrieved from the queue
     *
     * @param  JobInterface $job
     * @param  array        $options
     * @return void
     */
    public function failed(JobInterface $job, array $options = array());
}
