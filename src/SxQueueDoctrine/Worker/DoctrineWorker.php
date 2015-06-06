<?php

namespace SxQueueDoctrine\Worker;

use Exception;
use SxQueue\Job\JobInterface;
use SxQueue\Queue\QueueInterface;
use SxQueue\Worker\AbstractWorker;
use SxQueueDoctrine\Queue\DoctrineQueueInterface;
use SxQueueDoctrine\Job\Exception as JobException;
use SxQueue\Job\AbstractJob;
use Zend\Mvc\MvcEvent;

/**
 * Worker for Doctrine
 */
class DoctrineWorker extends AbstractWorker
{
    /**
     * @var JobInterface
     */
    protected $job;

    /**
     * @var QueueInterface
     */
    protected $queue;
    
    /**
     * @var boolean
     */
    protected $shutdownCallback = true;
    
    /**
     * @var boolean
     */
    protected $error = false;
    
    /**
     * {@inheritDoc}
     */
    public function processJob(JobInterface $job, QueueInterface $queue)
    {
        if (!$queue instanceof DoctrineQueueInterface) {
            return;
        }
        
        $this->job = $job;
        $this->queue = $queue;
        
        // Register error handlers
        set_error_handler(array($this, 'errorHandler'));
        $listener = $this->eventManager->getSharedManager()->attach(
            'Zend\Mvc\Application',
            \Zend\Mvc\MvcEvent::EVENT_DISPATCH_ERROR, 
            function(MvcEvent $e) {
                $this->exceptionHandler($e->getParam('exception'));
            }
        );
        register_shutdown_function(array($this, 'shutdownHandler'));
        
        // Execute job
        try {
            $this->job->execute($this->queue);
            
            if (!$this->error) {
                $this->job->setResult(AbstractJob::JOB_STATUS_COMPLETED);
                $this->queue->delete($this->job);
            }
        } catch (\Exception $e) {
            $this->exceptionHandler($e);    
        }

        // Unregister 
        restore_error_handler();
        $this->eventManager->getSharedManager()->detach('Zend\Mvc\Application', $listener);
        $this->shutdownCallback = false;
    }
    
    /**
     * Error handler
     * 
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $this->shutdownCallback = false;
        $this->error = true;
        
        $this->job->setResult(AbstractJob::JOB_STATUS_FAILED);
        $this->queue->failed(
            $this->job, 
            array(
                'message' => $errstr,
                'trace' => "ERROR: {$errno} {$errstr} file: {$errfile} line: {$errline}"
            )
        );
    }
    
    /**
     * Exception handler
     * 
     * @param \Exception $exception
     */
    public function exceptionHandler($exception)
    {
        $this->shutdownCallback = false;
        
        $this->job->setResult(AbstractJob::JOB_STATUS_FAILED);
        $this->queue->failed(
            $this->job,
            array(
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            )
        );
    }
    
    /**
     * Handler for exit / die
     */
    public function shutdownHandler()
    {
        if ($this->shutdownCallback) {
            $this->job->setResult(AbstractJob::JOB_STATUS_FAILED);
            $this->queue->failed(
                $this->job,
                array(
                    'message' => 'Exit called or induced'
                )
            );
            $this->shutdownCallback = false;
        }
    }
}
