<?php

require __DIR__.'/../vendor/autoload.php';

use Simondubois\UnsplashDownloader\Application;
use Simondubois\UnsplashDownloader\Command\Download;

$application = new Application();
$application->add(new Download());
$application->run();
