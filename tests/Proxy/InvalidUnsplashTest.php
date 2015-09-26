<?php namespace Tests\Proxy;

use org\bovigo\vfs\vfsStreamWrapper;
use Simondubois\UnsplashDownloader\Proxy\Unsplash;
use Tests\AbstractTest;

class InvalidUnsplashTest extends AbstractTest
{
    public function testFailedDownload()
    {
        $root = vfsStreamWrapper::getRoot();

        $proxy = $this->mockProxy([$root->url(), 1, null], [
            'isDownloadSuccessful' => function() {
                return false;
            },
        ]);
        $photos = $proxy->photos();
        $photo  = reset($photos);

        $this->assertEquals(Unsplash::DOWNLOAD_FAILED, $proxy->download($photo));
    }

    public function testFileSkipedDownload()
    {
        $root = vfsStreamWrapper::getRoot();
        file_put_contents($root->url().'/existing_history.txt', 'photo1');

        $proxy  = $this->mockProxy([$root->url(), 1, $root->url().'/existing_history.txt']);
        $photos = $proxy->photos();
        $photo  = reset($photos);

        $this->assertEquals(Unsplash::DOWNLOAD_SKIPPED, $proxy->download($photo));
    }

    public function testDynamicSkipedDownload()
    {
        $root = vfsStreamWrapper::getRoot();
        file_put_contents($root->url().'/existing_history.txt', 'photo0');

        $proxy  = $this->mockProxy([$root->url(), 1, $root->url().'/existing_history.txt']);
        $photos = $proxy->photos();
        $photo  = reset($photos);

        $this->assertEquals(Unsplash::DOWNLOAD_SUCCESS, $proxy->download($photo));
        $this->assertEquals(Unsplash::DOWNLOAD_SKIPPED, $proxy->download($photo));
    }
}
