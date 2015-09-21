<?php namespace Tests\Proxy;

use Tests\AbstractTest;
use Simondubois\UnsplashDownloader\Proxy\Unsplash;

abstract class AbstractUnsplashTest extends AbstractTest
{
    /**
     * Get value for the history parameter
     * @return string History filename
     */
    abstract public function history();

    public function testConstruct()
    {
        $proxy = new Unsplash($this->destination(), $this->quantity(), $this->history());
        $this->assertInstanceOf('Simondubois\UnsplashDownloader\Proxy\Unsplash', $proxy);

        return $proxy;
    }

    /**
     * @depends testConstruct
     */
    public function testConnection($proxy)
    {
        $connection = $proxy->isConnectionSuccessful();
        $this->assertTrue($connection);

        return $proxy;
    }

    /**
     * @depends testConnection
     */
    public function testPhotos($proxy)
    {
        $photos = $proxy->photos();
        $this->assertCount($this->quantity(), $photos);
        $this->assertContainsOnlyInstancesOf('Crew\Unsplash\Photo', $photos);

        return $photos;
    }

    /**
     * @depends testConnection
     * @depends testPhotos
     */
    public function testPhotoSource($proxy, $photos)
    {
        foreach ($photos as $photo) {
            $photoSource = $proxy->photoSource($photo);
            $this->assertEquals($photo->links['download'], $photoSource);
        }

        return $proxy;
    }

    /**
     * @depends testConnection
     * @depends testPhotos
     */
    public function testPhotoDestination($proxy, $photos)
    {
        foreach ($photos as $photo) {
            $photoDestination = $proxy->photoDestination($photo);
            $this->assertEquals($this->destination().'/'.$photo->id.'.jpg', $photoDestination);
        }

        return $proxy;
    }

    /**
     * @depends testConnection
     * @depends testPhotos
     */
    public function testDownload($proxy, $photos)
    {
        foreach ($photos as $photo) {
            $permissions = fileperms($this->destination());
            chmod($this->destination(), 0000);
            $download = $proxy->download($photo);
            chmod($this->destination(), $permissions);
            $this->assertEquals(Unsplash::DOWNLOAD_FAILED, $download);

            $download = $proxy->download($photo);
            $this->assertEquals(Unsplash::DOWNLOAD_SUCCESS, $download);
            $this->assertFileExists($proxy->photoDestination($photo));

            $download = $proxy->download($photo);
            if (is_string($this->history())) {
                $this->assertEquals(Unsplash::DOWNLOAD_HISTORY, $download);
            } else {
                $this->assertEquals(Unsplash::DOWNLOAD_SUCCESS, $download);
            }
        }

        $files = scandir($this->destination());
        if ($this->history() === $this->destination().'/existing_history.txt') {
            $this->assertCount($this->quantity() + 3, $files);
        } else {
            $this->assertCount($this->quantity() + 2, $files);
        }
    }

    /**
     * @depends testConnection
     * @depends testPhotos
     */
    public function testDestruct($proxy, $photos)
    {
        $proxy->__destruct();

        $history = $this->history();
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
