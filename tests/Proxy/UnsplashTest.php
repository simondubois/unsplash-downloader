<?php namespace Tests\Proxy;

use PHPUnit_Framework_TestCase;
use Simondubois\UnsplashDownloader\Proxy\Unsplash;

class UnsplashTest extends PHPUnit_Framework_TestCase
{

    //
    // PREPARE TEST
    //

    private static $downloadPath;

    public static function setUpBeforeClass()
    {
        static::$downloadPath = getcwd().'/tests/tmp';

        if (is_file(static::$downloadPath)) {
            throw new Exception('Path "'.static::$downloadPath.'" should not be a file.');
        } elseif (is_dir(static::$downloadPath)) {
            static::emptyDirectory(static::$downloadPath);
        } else {
            $mkdir = mkdir(static::$downloadPath);
            if ($mkdir === false) {
                throw new Exception('Directory "'.static::$downloadPath.'" can not be created.');
            }
        }
    }

    public static function tearDownAfterClass()
    {
        static::emptyDirectory(static::$downloadPath);
    }



    //
    // TEST CASES
    //

    public function testDefault()
    {
        $proxy = new Unsplash(static::$downloadPath, 10, null);

        $this->assertInstanceOf('Simondubois\UnsplashDownloader\Proxy\Unsplash', $proxy);
    }


    //
    // HELPERS
    //

    public static function emptyDirectory($path) {
        $files = scandir($path);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $unlink = unlink("$path/$file");

            if ($unlink === false) {
                throw new Exception('Can not empty directory "'.$path.'".');
            }
        }

    }
}
