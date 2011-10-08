<?php
    require_once('../Future.php');
    require_once('../FutureCollection.php');
    class testFutureCollection extends PHPUnit_Framework_TestCase
    {
        public function testFutureThing()
        {
            $ack = new stdClass;
            $ack->foo = 1;
            $futureCollection = new FutureCollection();


            $harf = new Future(function() { sleep(3); return '3'; }, true, $futureCollection->getCollectionId());
            $nads = new Future(function() { return '1'; }, true, $futureCollection->getCollectionId());
            $blah = new Future(function() { sleep(1); return '2'; }, true, $futureCollection->getCollectionId());

            $futureCollection->addFuture($harf)
                    ->addFuture($nads)
                    ->addFuture($blah);


            echo PHP_EOL."COUNT of FUTURES ".$futureCollection->count().PHP_EOL;
            // Loop through all futures until they have all finished.
            $expectedValue = 1;
            while($futureCollection->count() > 0)
            {
                $finishedFuture = $futureCollection->getNextFuture();
		                
                if($finishedFuture instanceof Future)
                {
                    $value = $finishedFuture();
                    $this->assertEquals($expectedValue, $value);
                    $expectedValue++;
                }
            }
        }
    }
