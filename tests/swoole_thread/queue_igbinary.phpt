--TEST--
swoole_thread: queue push/pop roundtrip with complex types (array and object)
--SKIPIF--
<?php
require __DIR__ . '/../include/skipif.inc';
skip_if_nts();
skip_if_extension_not_exist('igbinary');
?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';

use Swoole\Thread;
use Swoole\Thread\Queue;

$args = Thread::getArguments();

if (empty($args)) {
    $queue = new Queue();
    $results = new Thread\Map();

    $thread = new Thread(__FILE__, $queue, $results);

    $queue->push(['key' => 'value', 'nested' => [1, 2, 3]], Queue::NOTIFY_ONE);
    $queue->push((object) ['name' => 'test', 'value' => 42], Queue::NOTIFY_ONE);
    $queue->push(null, Queue::NOTIFY_ONE); // sentinel

    $thread->join();

    Assert::eq($results['array_key'], 'value');
    Assert::eq($results['array_nested_0'], 1);
    Assert::eq($results['object_name'], 'test');
    Assert::eq($results['object_value'], 42);
    echo "OK\n";
} else {
    [$queue, $results] = $args;

    $item1 = $queue->pop(-1);
    if ($item1 !== null) {
        $results['array_key'] = $item1['key'];
        $results['array_nested_0'] = $item1['nested'][0];
    }

    $item2 = $queue->pop(-1);
    if ($item2 !== null) {
        $results['object_name'] = $item2->name;
        $results['object_value'] = $item2->value;
    }

    $queue->pop(-1); // consume sentinel
    exit(0);
}
?>
--EXPECT--
OK
