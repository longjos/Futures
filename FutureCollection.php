<?php
/**
 * FutureCollection
 *
 * Allows for a Collection of Futures to be acted on completion
 * 
 * PHP Version 5.3 
 *
 * @author Josh Long <longjos@gmail.com>
 * @copyright 2011 Josh Long
 * @license http://www.opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @link https://github.com/spiralout/Futures
 */
class FutureCollection
{
 
    const VALUE_READY = 1;
    
    /** max value size */
    const SIZE = 100000;
    
    private $futures = array();
    
    
    private $collectionQueueId;
    
    private $collectionQueueKey = 0xFAD3;
    
    function __construct()
    {
        if(!($this->collectionQueueId = msg_get_queue($this->getMessageQueueId())))
        {
            throw Exception("Unable to get Collection Queue");
        }
    }
    
    /**
     * Add a Future to this collection
     * 
     * @param Future $future
     * @return FutureCollection Return self for fluent interface 
     */
    function addFuture(Future $future)
    {
        $this->futures[$future->getFutureId()] = $future;
        return $this;
    }
    
    /**
     * Return the next Future with VALUE_READY
     * 
     * @return Future
     */
    function getNextFuture()
    {
         if (!(msg_receive($this->collectionQueueId, self::VALUE_READY, $msgType, self::SIZE, $futureId, true, 0, $error))) {
                return false;
            }
         $finishedFuture = $this->futures[$futureId];
         unset($this->futures[$futureId]);
         return $finishedFuture;;
    }
    
    /**
     * Return the number of futures in this collection
     * 
     * @return int
     */
    function count()
    {
        return count($this->futures);
    }
    
    /**
     * Return the key for this collection
     * 
     * @return type 
     */
    function getCollectionId()
    {
        return $this->getMessageQueueId();
    }
    
    /**
     * Get a unique ID for our collection queue
     * 
     * @return int
     */
    private function getMessageQueueId()
    {
        if(!isset($this->collectionQueueKey))
        {
            $this->collectionQueueKey = rand(1, 2000);
        }
        return $this->collectionQueueKey;
    }
}
