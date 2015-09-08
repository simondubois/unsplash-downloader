#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Simondubois\UnsplashDownloader\Application;

$application = new Application();
$application->run();
