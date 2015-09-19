<?php namespace Tests;

use PHPUnit_Framework_TestCase;

class TestBase extends PHPUnit_Framework_TestCase
{
    public static function getDownloadPath()
    {
        return getcwd().'/tests/tmp';
    }

    public function setUp()
    {
        $downloadPath = $this->getDownloadPath();

        if (is_file($downloadPath)) {
            throw new Exception('Path "'.$downloadPath.'" should not be a file.');
        }

        if (is_dir($downloadPath)) {
            static::emptyDirectory($downloadPath);
        } else {
            $mkdir = mkdir($downloadPath);

            if ($mkdir === false) {
                throw new Exception('Directory "'.$downloadPath.'" can not be created.');
            }
        }

        touch($downloadPath.'/existing_history.txt');
    }

    public function tearDown()
    {
        static::emptyDirectory($this->getDownloadPath());
    }


    public function validParameterProvider()
    {
        $downloadPath = $this->getDownloadPath();

        return [
            'no history' => [$downloadPath, 1, null],
            'new history' => [$downloadPath, 1, $downloadPath.'/new_history.txt'],
            'existing history' => [$downloadPath, 1, $downloadPath.'/existing_history.txt'],
        ];
    }

    public static function emptyDirectory($path)
    {
        $files = scandir($path);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $unlink = unlink($path.'/'.$file);

            if ($unlink === false) {
                throw new Exception('Can not empty directory "'.$path.'".');
            }
        }
    }
}
