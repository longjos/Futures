<?php
/**
 * Future
 *
 * PHP Version 5.3 
 *
 * @author Sean Crystal <sean.crystal@gmail.com>
 * @copyright 2011 Sean Crystal
 * @license http://www.opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @link https://github.com/spiralout/Futures
 */
class Future
{
    /** max value size */
    const SIZE = 100000;

    /** unique key for the Futures message queue */
    const MSG_QUEUE_KEY = 0xFAC3;
    
    /**
     * Constructor
     *
     * @var Closure $func
     * @var bool $autoStart
     * @var resource $collectionId Resource Identifier for collection queue
     */
    function __construct(Closure $func, $autoStart = true, $collectionKey = NULL)
    {
        self::$futureCount++;

        $this->func = $func;        
        $this->autoStart = $autoStart;
        $this->collectionKey = $collectionKey;

        $this->initMessageQueue();

        $autoStart and $this->doFork();
    }

    /**
     * Retrieve the value of this Future. Blocks until value is available
     *
     * @return mixed
     */
    function __invoke()
    {
        return $this->get();
    }

    /**
     * Get the value of this Future. Blocks until the value is available
     *
     * @return mixed
     */
    function get()
    {           
        if (!$this->completed) {
            if (!(msg_receive($this->mqid, $this->messageType, $msgType, self::SIZE, $this->value, true, 0, $error))) {
                return false;
            }

            $this->cleanUp();
            $this->completed = true;
        }

        return $this->value;
    }

    /**
     * Get the value of this Future if available, but do not block. Returns false if the
     * value is not yet available
     *
     * @return mixed|false
     */
    function getNoWait()
    {
        if (!$this->completed) { 
            if (!msg_receive($this->mqid, $this->messageType, $msgType, self::SIZE, $this->value, true, MSG_IPC_NOWAIT, $error)) {
                return false;
            }
            $this->cleanUp();
            $this->completed = true;
        }

        return $this->value;
    }

    /**
     * Start the Future computation if it was not autostarted
     */
    function start()
    {
        $this->autoStart or $this->doFork();
    }

    /**
     * Check if this Future is finished computing its value
     *
     * @return bool
     */
    function isCompleted()
    {
        return $this->completed;
    }
    
    /**
     * Return this futures queue id
     * 
     * @return int
     */
    function getFutureId()
    {
        return $this->messageType;
    }

    /**
     * Fork a child process to compute the value of this Future
     */
    private function doFork()
    {
        if ($this->pid = pcntl_fork()) {  // parent
            /* nop */
        } else {  //child
            $func = $this->func;

            try {
                $value = $func();
            } catch (Exception $e) {
                $value = $e;
            }
        
            // Put the value on the message queue and send a Value Ready message to the collection queue
            if (!msg_send($this->mqid, $this->messageType, $value, true, true, $error)) {
                $this->cleanUp();
            }
            // Set VALUE_READY on the collection Queue;
            if(isset($this->collectionKey))
            {
                msg_send($this->collectionQueueId, FutureCollection::VALUE_READY, $this->messageType, true, true, $error);
            }
            exit(0);
        } 
    }

    /**
     * Cleanup any resources acquired
     */
    private function cleanUp()
    {
        self::$futureCount--;

        if (self::$futureCount == 0) {
            msg_remove_queue($this->mqid);
            msg_remove_queue($this->collectionQueueId);
        }
    }

    /**
     * Generate a unique message type key
     *
     * @return int
     */
    private function getMessageType()
    {
        return floor(microtime(true) * 1000000 + rand(2, 1000));
    }

    /**
     * Create a message queue to return the result of this Future to the creator
     */
    private function initMessageQueue()
    {
        $this->messageType = $this->getMessageType();

        if (!($this->mqid = msg_get_queue(self::MSG_QUEUE_KEY))) {
            throw new Exception('Could not create message queue with key '. self::MSG_QUEUE_KEY);
        }
        if(isset($this->collectionKey))
        {
            if (!($this->collectionQueueId = msg_get_queue($this->collectionKey)))
            {
                throw new Exception("Could not create collection message queue with key ". $this->collectionKey);
            }
        }
    }
    

    /** @var bool */
    private $completed = false;

    /** @var bool */
    private $autoStart = true;

    /** @var mixed */
    private $value;

    /** @var resource */
    private $mqid;
    
    /** @var resource Collection Queue ID */
    private $collectionQueueId;
    
    /** @var int Collection Queue Key */
    private $collectionKey;

    /** @var int */
    private $pid;

    /** @var Closure */
    private $func;

    /** @var int */
    private $messageType;

    /** @staticvar $int */
    static $futureCount = 0;
}   



