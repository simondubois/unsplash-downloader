# unsplash-downloader

[![Build Status](https://travis-ci.org/simondubois/unsplash-downloader.svg)](https://travis-ci.org/simondubois/unsplash-downloader)
[![Code Coverage](https://scrutinizer-ci.com/g/simondubois/unsplash-downloader/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/simondubois/unsplash-downloader/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/simondubois/unsplash-downloader/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/simondubois/unsplash-downloader/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/4556fb29-ce84-4668-a918-ce4fb39f3083/mini.png)](https://insight.sensiolabs.com/projects/4556fb29-ce84-4668-a918-ce4fb39f3083)

CLI to download photos from unsplash.com


## Usage

Download photos :

	unsplash-downloader [--destination DESTINATION] [--quantity QUANTITY] [--history HISTORY] [--featured]
	unsplash-downloader [--categories]

### Executable
The executable is located into the build directory.

### Requirements

Dependency : PHP 5.6 or hhvm or nightly.

To use Unsplash API (and thus this CLI), you have to get an application ID and secret from https://unsplash.com/developers. Then, create a file ``unsplash.ini`` in your working directory with the following content :

	applicationId = "your-application-id"
	secret = "your-secret"

### Options
	--destination DESTINATION
Directory where to download photos.
*Default: current working directory*

      --quantity QUANTITY
Number of photos to download.
*Default: 10*

      --history HISTORY
Filename to use as download history. When photos are downloaded, their IDs will be stored into the file. Then any further download is going to ignore photos that have their ID in the history. Usefull to delete unwanted pictures and prevent the CLI to download them again.
*Default: none*

      --featured
Download only featured photos.
*Default: false*


      --categories
List categories and quit (no download will be performed).
*Default: false*

-----

## Build from source

### Get sources
	git clone git@github.com:simondubois/unsplash-downloader.git

### Install dependencies
	cd unsplash-downloader/
	composer install

### Make your changes
```
	cli/index.php
```
CLI stub. Initial script to define application commands and run it.
```
	src/Application.php
```
An Application is the container for a collection of commands.
It is the main entry point of a Console application.
This class is optimized for a standard CLI environment.
```
	src/Download.php
```
A download command to handle the whole process to download photos. Steps are :

- check option validity (destination, count and history).
- load credentials (from local unsplash.ini file).
- create a task (to deal with Unsplash API).
- execute the task.
```
	src/Task.php
```
A task to download photos from Unsplash. Steps are

- connect to the server
- list photos
- download photos
```
	src/Unsplash.php
```
A proxy to deal with the Unsplah API :

- connect to the server.
- list photos
```
	src/History.php
```
A proxy to handle history operations like :

- loading history from file
- checking existence of entity in history
- appending data to history
- saving history to file

### Run tests
	vendor/bin/phpunit
Test coverage can be found under `tests/coverage`.

### Build PHAR
	php create-phar.php
The generated PHAR can be found under `build`.
