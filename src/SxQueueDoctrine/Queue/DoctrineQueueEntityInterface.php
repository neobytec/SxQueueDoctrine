<?php
namespace SxQueueDoctrine\Queue;

interface DoctrineQueueEntityInterface
{
    public function getId();
    
    public function getQueue();

    public function getData();
    
    public function getStatus();
    
    public function getCreated();

    public function getScheduled();
    
    public function getExecuted();
    
    public function getFinished();
    
    public function getFailed();
    
    public function getAttempts();
    
    public function getMessage();
    
    public function getTrace();
}