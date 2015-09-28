# unsplash-downloader

[![Build Status](https://travis-ci.org/simondubois/unsplash-downloader.svg)](https://travis-ci.org/simondubois/unsplash-downloader)
[![Code Coverage](https://scrutinizer-ci.com/g/simondubois/unsplash-downloader/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/simondubois/unsplash-downloader/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/simondubois/unsplash-downloader/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/simondubois/unsplash-downloader/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/4556fb29-ce84-4668-a918-ce4fb39f3083/mini.png)](https://insight.sensiolabs.com/projects/4556fb29-ce84-4668-a918-ce4fb39f3083)

CLI to download photos from unsplash.com


## Usage
	unsplash-downloader [--destination DESTINATION] [--quantity QUANTITY] [--history HISTORY]

### Executable
The executable is located into the build directory.
The only requirement is to have PHP installed.

### Options
	--destination DESTINATION
Directory where to download photos.
*Default: current working directory.*

      --quantity QUANTITY
Number of photos to download.
*Default: 10.*

      --history HISTORY
Filename to use as download history. When photos are downloaded, their IDs will be stored into the file. Then any further download is going to ignore photos that have their ID in the history. Usefull to delete unwanted pictures and prevent the CLI to download them again.
*Default: none.*


## Build from source

### Get sources
	git clone git@github.com:simondubois/unsplash-downloader.git

### Install dependencies
	cd unsplash-downloader/
	composer install

### Make your changes

	cli/index.php
CLI stub.
Initial script to define application commands and run it.

	src/Application.php
An Application is the container for a collection of commands.
It is the main entry point of a Console application.
This class is optimized for a standard CLI environment.
 
	src/Command/Download.php
A download command to handle the whole process to download photos.
The steps are :
 - check option validity (destination, count and history).
 - create a proxy (to deal with Unsplash API).
 - connect proxy to API.
 - get list of photos.
 - download each photo.

	src/Proxy/Unsplash.php
Proxy dealing with the Unsplah API :
- connect to the server.
- list photos
- download photos

### Run tests
	vendor/bin/phpunit
Test coverage can be found under tests/coverage.

### Build PHAR
	php create-phar.php
The generated PHAR can be found under build directory.