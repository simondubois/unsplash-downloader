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


    public function validData() {
        static::$downloadPath = getcwd().'/tests/tmp';

        return [
            [static::$downloadPath, 1, null],
        ];
    }

    //
    // TEST CASES
    //

    /**
     * @dataProvider validData
     */
    public function testValid($destination, $quantity, $history)
    {
        $proxy = new Unsplash($destination, $quantity, $history);
        $this->assertInstanceOf('Simondubois\UnsplashDownloader\Proxy\Unsplash', $proxy);

        $connect = $proxy->connect();
        $this->assertTrue($connect);

        $photos = $proxy->photos();
        $this->assertCount($quantity, $photos);
        $this->assertContainsOnlyInstancesOf('Crew\Unsplash\Photo', $photos);

        foreach ($photos as $photo) {
            $photoSource = $proxy->photoSource($photo);
            $this->assertEquals($photo->links['download'], $photoSource);

            $photoDestination = $proxy->photoDestination($photo);
            $this->assertEquals("$destination/{$photo->id}.jpg", $photoDestination);

            $download = $proxy->download($photo);
            $this->assertEquals(Unsplash::DOWNLOAD_SUCCESS, $download);
            $this->assertFileExists($photoDestination);
        }
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
