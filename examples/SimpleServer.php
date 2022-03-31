<?php
require __DIR__ . '/vendor/autoload.php';

$sdk = new \Symplify\SSTSDK\Client('goober');

echo $sdk->hello() . PHP_EOL;
