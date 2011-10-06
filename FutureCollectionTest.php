<?php
    require_once('Future.php');
    require_once('FutureCollection.php');
    
    $ack = new stdClass;
    $ack->foo = 1;
    $futureCollection = new FutureCollection();


    $harf = new Future(function() { sleep(3); return PHP_EOL.'3 second process started first'.PHP_EOL; }, true, $futureCollection->getCollectionId());
    $nads = new Future(function() use ($ack) { return PHP_EOL.'Immediate Process Started Second'. $ack->foo . PHP_EOL; }, true, $futureCollection->getCollectionId());
    $blah = new Future(function() use ($ack) { sleep(1); return PHP_EOL.'1 Second Process Started Last'. ($ack->foo + 42) . PHP_EOL; }, true, $futureCollection->getCollectionId());

    $futureCollection->addFuture($harf)
            ->addFuture($nads)
            ->addFuture($blah);


    echo PHP_EOL."COUNT of FUTURES ".$futureCollection->count();
    // Loop through all futures until they have all finished.
    while($futureCollection->count() > 0)
    {
        $finishedFuture = $futureCollection->getNextFuture();
        if($finishedFuture instanceof Future)
        {
            print_r($finishedFuture());
        }
    }
