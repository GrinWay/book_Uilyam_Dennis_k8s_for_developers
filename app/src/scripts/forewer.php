<?php

$redis = new Redis();

$host = 'app-redis-read';
$port = 6379;

$redis->connect($host, $port);

$isEmpty = false;

while (false === $isEmpty) {
    echo "Waiting for messages..." . \PHP_EOL;

    // RPUSH: kubectl exec -it pod/app-redis-v1-0 -- redis-cli RPUSH queue "task1"
    $task = $redis->blPop(['queue'], 1); // 0 = ждать бесконечно

    if (empty($task)) {
        $isEmpty = true;
    } else {
        $isEmpty = false;
    }

    \print_r($task);
}

echo 'Exit' . \PHP_EOL;