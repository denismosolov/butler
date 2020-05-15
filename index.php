<?php

declare(strict_types=1);

// include 'vendor/autoload.php';
// we don't really need autoloading here right now
include 'src/Application.php';

use Butler\Application;

function main($event, $context): array
{
    $conf = include 'conf/jobs.php';
    $app = new Application();
    $app->setJobs($conf);
    $app->setEvent($event);
    return $app->run();
}