<?php namespace Tests\Proxy;

use PHPUnit_Framework_TestCase;
use Simondubois\UnsplashDownloader\Proxy\Unsplash;

class UnsplashTest extends PHPUnit_Framework_TestCase
{

    //
    // PREPARE TEST
    //

    public static function getDownloadPath() {
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


    public function parameterProvider() {
        $downloadPath = $this->getDownloadPath();

        return [
            'no history' => [$downloadPath, 1, null],
            'new history' => [$downloadPath, 1, $downloadPath.'/new_history.txt'],
            'existing history' => [$downloadPath, 1, $downloadPath.'/existing_history.txt'],
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
            $permissions = fileperms($destination);
            chmod($destination, 0000);
            $download = $proxy->download($photo);
            chmod($destination, $permissions);
            $this->assertEquals(Unsplash::DOWNLOAD_FAILED, $download);

            $download = $proxy->download($photo);
            $this->assertEquals(Unsplash::DOWNLOAD_SUCCESS, $download);
            $this->assertFileExists($proxy->photoDestination($photo));

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
