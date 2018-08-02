<?php
/** 测试 */
include './IdWorker.php';

function test() {
    $idWorker = new SnowflakeIdWorker(0, 0);
    for ($i = 0; $i < 1000; $i++) {
        $id = $idWorker->nextId();
        echo $id . PHP_EOL;
    }
}

test();
