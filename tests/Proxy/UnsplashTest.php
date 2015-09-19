<?php namespace Tests\Proxy;

use Tests\TestBase;
use Simondubois\UnsplashDownloader\Proxy\Unsplash;

class UnsplashTest extends TestBase
{
    /**
     * @dataProvider validParameterProvider
     */
    public function testConstruct($destination, $quantity, $history)
    {
        $proxy = new Unsplash($destination, $quantity, $history);
        $this->assertInstanceOf('Simondubois\UnsplashDownloader\Proxy\Unsplash', $proxy);

        return $proxy;
    }

    /**
     * @dataProvider validParameterProvider
     */
    public function testConnection($destination, $quantity, $history)
    {
        $proxy = $this->testConstruct($destination, $quantity, $history);

        $connection = $proxy->isConnectionSuccessful();
        $this->assertTrue($connection);

        return $proxy;
    }

    /**
     * @dataProvider validParameterProvider
     */
    public function testPhotos($destination, $quantity, $history)
    {
        $proxy = $this->testConnection($destination, $quantity, $history);

        $photos = $proxy->photos();
        $this->assertCount($quantity, $photos);
        $this->assertContainsOnlyInstancesOf('Crew\Unsplash\Photo', $photos);

        return $photos;
    }

    /**
     * @dataProvider validParameterProvider
     */
    public function testPhotoSource($destination, $quantity, $history)
    {
        $proxy = $this->testConnection($destination, $quantity, $history);

        $photos = $proxy->photos();
        foreach ($photos as $photo) {
            $photoSource = $proxy->photoSource($photo);
            $this->assertEquals($photo->links['download'], $photoSource);
        }

        return $proxy;
    }

    /**
     * @dataProvider validParameterProvider
     */
    public function testPhotoDestination($destination, $quantity, $history)
    {
        $proxy = $this->testConnection($destination, $quantity, $history);

        $photos = $proxy->photos();
        foreach ($photos as $photo) {
            $photoDestination = $proxy->photoDestination($photo);
            $this->assertEquals($destination.'/'.$photo->id.'.jpg', $photoDestination);
        }

        return $proxy;
    }

    /**
     * @dataProvider validParameterProvider
     */
    public function testDownload($destination, $quantity, $history)
    {
        $proxy = $this->testConnection($destination, $quantity, $history);

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
}
