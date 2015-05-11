<?php
require_once __DIR__ . "/vendor/autoload.php";

$tasks = [
    'CrosspostThread'
];
if (!isset($argv[1])) {
    die("\033[1;31mPossible Tasks: " . implode(', ', $tasks) . "\033[0m\n");
}

Dotenv::load(__DIR__);

$task = "Slacky\\Jobs\\{$argv[1]}";
$job = new $task;
echo $job->run() . "\n";