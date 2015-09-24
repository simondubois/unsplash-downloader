<?php namespace Tests\Proxy;

use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use Simondubois\UnsplashDownloader\Proxy\Unsplash;
use Tests\AbstractTest;

// $permissions = $destination->getPermissions();
// $destination->chmod(0);
// var_dump($destination->isWritable());
// $download = $proxy->download($photo);
// $destination->chmod($permissions);
// $this->assertEquals(Unsplash::DOWNLOAD_FAILED, $download);

// $download = $proxy->download($photo);
// if (is_string($history)) {
//     $this->assertEquals(Unsplash::DOWNLOAD_HISTORY, $download);
// } else {
//     $this->assertEquals(Unsplash::DOWNLOAD_SUCCESS, $download);
// }

class ValidUnsplashTest extends AbstractTest
{

    /**
     * @dataProvider validParameterProvider
     */
    public function testConstruct($destination, $quantity, $history)
    {
        $proxy = new Unsplash($destination, $quantity, $history);

        $this->assertInstanceOf('Simondubois\UnsplashDownloader\Proxy\Unsplash', $proxy);
    }

    /**
     * @dataProvider validParameterProvider
     */
    public function testConnection($destination, $quantity, $history)
    {
        $proxy      = new Unsplash($destination, $quantity, $history);
        $connection = $proxy->isConnectionSuccessful();

        $this->assertTrue($connection);
    }

    /**
     * @dataProvider validParameterProvider
     */
    public function testPhotos($destination, $quantity, $history)
    {
        $proxy = $this->mockProxy([$destination, $quantity, $history]);
        $proxy->isConnectionSuccessful();
        $photos = $proxy->photos();

        $this->assertCount($quantity, $photos);
        $this->assertContainsOnlyInstancesOf('Crew\Unsplash\Photo', $photos);
    }

    /**
     * @dataProvider validParameterProvider
     */
    public function testPhotoSource($destination, $quantity, $history)
    {
        $proxy = $this->mockProxy([$destination, $quantity, $history]);
        $proxy->isConnectionSuccessful();
        $photos = $proxy->photos();

        foreach ($photos as $photo) {
            $photoSource = $proxy->photoSource($photo);

            $this->assertEquals($photo->links['download'], $photoSource);
        }
    }

    /**
     * @dataProvider validParameterProvider
     */
    public function testPhotoDestination($destination, $quantity, $history)
    {
        $proxy = $this->mockProxy([$destination, $quantity, $history]);
        $proxy->isConnectionSuccessful();
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
        $proxy = $this->mockProxy([$destination, $quantity, $history]);

        $destination = new vfsStreamDirectory(substr($destination, 6));
        $photos      = $proxy->photos();

        foreach ($photos as $photo) {
            $download = $proxy->download($photo);

            $this->assertEquals(Unsplash::DOWNLOAD_SUCCESS, $download);
            $this->assertFileExists($proxy->photoDestination($photo));

            $destination->addChild(new vfsStreamFile($proxy->photoDestination($photo)));
        }

        if (strstr($history, 'existing') === true) {
            $this->assertCount($quantity + 1, $destination->getChildren());
        } else {
            $this->assertCount($quantity, $destination->getChildren());
        }
    }

    /**
     * @dataProvider validParameterProvider
     */
    public function testDestruct($destination, $quantity, $history)
    {
        $proxy = $this->mockProxy([$destination, $quantity, $history]);

        $photos = $proxy->photos();
        foreach ($photos as $photo) {
            $proxy->download($photo);
        }

        $proxy->__destruct();

        if (is_null($history)) {
            return;
        }

        $historyList = [];
        foreach ($photos as $photo) {
            $historyList[] = $photo->id;
        }

        $this->assertFileExists($history);
        $this->assertEquals(implode(PHP_EOL, $historyList), file_get_contents($history));
    }
}
