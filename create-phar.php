<?php

// Define script variables
$projectRoot = __DIR__;
$projectName = basename(__DIR__);
$buildRoot   = $projectRoot.'/build';
$pharFile    = $projectName.'.phar';

// Remove previous builds
if (is_file($buildRoot.'/'.$projectName)) {
    unlink($buildRoot.'/'.$projectName);
}
if (is_file($buildRoot.'/'.$pharFile)) {
    unlink($buildRoot.'/'.$pharFile);
}

// Remove dev dependencies
exec('composer install --no-dev');

// Build phar file
$phar = new Phar($buildRoot.'/'.$pharFile, 0, $pharFile);
$directoryIterator = new RecursiveDirectoryIterator($projectRoot.'/cli', FilesystemIterator::SKIP_DOTS);
$recursiveIterator = new RecursiveIteratorIterator($directoryIterator);
$phar->buildFromIterator($recursiveIterator, $projectRoot);
$directoryIterator = new RecursiveDirectoryIterator($projectRoot.'/src', FilesystemIterator::SKIP_DOTS);
$recursiveIterator = new RecursiveIteratorIterator($directoryIterator);
$phar->buildFromIterator($recursiveIterator, $projectRoot);
$directoryIterator = new RecursiveDirectoryIterator($projectRoot.'/vendor', FilesystemIterator::SKIP_DOTS);
$recursiveIterator = new RecursiveIteratorIterator($directoryIterator);
$phar->buildFromIterator($recursiveIterator, $projectRoot);
$phar->setStub(<<<EOF
#!/usr/bin/env php
<?php
Phar::mapPhar('$pharFile');
require 'phar://$pharFile/cli/index.php';
__HALT_COMPILER();
EOF
);

// // Compress phar file
// $compressed = $phar->convertToExecutable(Phar::TAR, Phar::GZ, '.phar.tgz');

// Make file executable
chmod($buildRoot.'/'.$pharFile, 0777);

// Remove phar extension
rename($buildRoot.'/'.$pharFile, $buildRoot.'/'.$projectName);

// Restore dev dependencies
exec('composer install');
