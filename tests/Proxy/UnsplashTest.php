<?php namespace Tests\Proxy;

use PHPUnit_Framework_TestCase;
use Simondubois\UnsplashDownloader\Proxy\Unsplash;

class UnsplashTest extends PHPUnit_Framework_TestCase
{

    //
    // PREPARE TEST
    //

    private static $downloadPath;

    public function setUp()
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

        touch(static::$downloadPath.'/existing_history.txt');
    }

    public function tearDown()
    {
        static::emptyDirectory(static::$downloadPath);
    }


    public function parameterProvider() {
        static::$downloadPath = getcwd().'/tests/tmp';

        return [
            [static::$downloadPath, 1, null],
            [static::$downloadPath, 1, static::$downloadPath.'/new_history.txt'],
            [static::$downloadPath, 1, static::$downloadPath.'/existing_history.txt'],
        ];
    }



    //
    // UNIT TESTS
    //

    /**
     * @dataProvider parameterProvider
     */
    public function testConstruct($destination, $quantity, $history)
    {
        $proxy = new Unsplash($destination, $quantity, $history);
        $this->assertInstanceOf('Simondubois\UnsplashDownloader\Proxy\Unsplash', $proxy);

        return $proxy;
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testConnect($destination, $quantity, $history)
    {
        $proxy = $this->testConstruct($destination, $quantity, $history);

        $connect = $proxy->connect();
        $this->assertTrue($connect);

        return $proxy;
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testPhotos($destination, $quantity, $history)
    {
        $proxy = $this->testConnect($destination, $quantity, $history);

        $photos = $proxy->photos();
        $this->assertCount($quantity, $photos);
        $this->assertContainsOnlyInstancesOf('Crew\Unsplash\Photo', $photos);

        return $photos;
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testPhotoSource($destination, $quantity, $history)
    {
        $proxy = $this->testConnect($destination, $quantity, $history);

        $photos = $proxy->photos();
        foreach ($photos as $photo) {
            $photoSource = $proxy->photoSource($photo);
            $this->assertEquals($photo->links['download'], $photoSource);
        }

        return $proxy;
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testPhotoDestination($destination, $quantity, $history)
    {
        $proxy = $this->testConnect($destination, $quantity, $history);

        $photos = $proxy->photos();
        foreach ($photos as $photo) {
            $photoDestination = $proxy->photoDestination($photo);
            $this->assertEquals("$destination/{$photo->id}.jpg", $photoDestination);
        }

        return $proxy;
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testDownload($destination, $quantity, $history)
    {
        $proxy = $this->testConnect($destination, $quantity, $history);

        $photos = $proxy->photos();
        foreach ($photos as $photo) {
            $photoSource = $proxy->photoSource($photo);
            $photoDestination = $proxy->photoDestination($photo);

            $permissions = fileperms($destination);
            chmod($destination, 0000);
            $download = $proxy->download($photo);
            chmod($destination, $permissions);
            $this->assertEquals(Unsplash::DOWNLOAD_FAILED, $download);

            $download = $proxy->download($photo);
            $this->assertEquals(Unsplash::DOWNLOAD_SUCCESS, $download);
            $this->assertFileExists($photoDestination);

            $download = $proxy->download($photo);
            if (is_string($history)) {
                $this->assertEquals(Unsplash::DOWNLOAD_HISTORY, $download);
            } else {
                $this->assertEquals(Unsplash::DOWNLOAD_SUCCESS, $download);
            }
        }

        $files = scandir($destination);
        $this->assertCount($quantity + 3, $files);
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
