<?php

require __DIR__.'/../vendor/autoload.php';

use Simondubois\UnsplashDownloader\Application;
use Simondubois\UnsplashDownloader\Command;

$application = new Application();
$application->add(new Command());
$application->run();
