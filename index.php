<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Butler\Application;

function main($event, $context): array
{
    $conf = include 'conf/jobs.php';
    $app = new Application();
    $app->setJobs($conf);
    $app->setEvent($event);
    return $app->run();
}