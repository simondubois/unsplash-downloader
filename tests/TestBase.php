<?php namespace Tests;

use PHPUnit_Framework_TestCase;

abstract class TestBase extends PHPUnit_Framework_TestCase
{
    public function destination()
    {
        return getcwd().'/tests/tmp';
    }

    public function quantity()
    {
        return 1;
    }

    abstract public function history();

    public function setUp()
    {
        $destination = $this->destination();

        if (is_file($destination)) {
            throw new Exception('Path "'.$destination.'" should not be a file.');
        }

        if (is_dir($destination)) {
            static::emptyDirectory($destination);
        } else {
            $mkdir = mkdir($destination);

            if ($mkdir === false) {
                throw new Exception('Directory "'.$destination.'" can not be created.');
            }
        }

        touch($destination.'/existing_history.txt');
    }

    public function tearDown()
    {
        static::emptyDirectory($this->destination());
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
