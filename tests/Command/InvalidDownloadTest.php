<?php namespace Tests\Command;

use org\bovigo\vfs\vfsStreamWrapper;
use Simondubois\UnsplashDownloader\Command\Download;
use Simondubois\UnsplashDownloader\Proxy\Unsplash;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class InvalidDownloadTest extends AbstractDownloadTest
{
    /**
     * @expectedException Exception
     * @expectedExceptionCode Simondubois\UnsplashDownloader\Command\Download::ERROR_CONNECTION
     */
    public function testFailedConnexion() {
        $command = new Download();
        $command->output = new BufferedOutput();

        $root  = vfsStreamWrapper::getRoot();
        $proxy = $this->mockProxy([$root->url(), 1, null], [
            'isConnectionSuccessful' => function() {
                return false;
            },
        ]);

        $command->connect($proxy);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionCode Simondubois\UnsplashDownloader\Command\Download::ERROR_DESTINATION_NOTDIR
     */
    public function testMissingDestination() {
        $command = new Download();
        $root    = vfsStreamWrapper::getRoot();

        $command->destination($root->url().'/missingfolder');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionCode Simondubois\UnsplashDownloader\Command\Download::ERROR_DESTINATION_UNWRITABLE
     */
    public function testUnwritableDestination() {
        $command = new Download();
        $root    = vfsStreamWrapper::getRoot();
        $root->chmod(0);

        $command->destination($root->url());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionCode Simondubois\UnsplashDownloader\Command\Download::ERROR_QUANTITY_NOTNUMERIC
     */
    public function testNotnumericQuantity() {
        $command = new Download();

        $command->quantity('text');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionCode Simondubois\UnsplashDownloader\Command\Download::ERROR_QUANTITY_NOTPOSITIVE
     */
    public function testNotpositiveQuantity() {
        $command = new Download();

        $command->quantity(-10);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionCode Simondubois\UnsplashDownloader\Command\Download::ERROR_QUANTITY_TOOHIGH
     */
    public function testToohighQuantity() {
        $command = new Download();

        $command->quantity(1000);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionCode Simondubois\UnsplashDownloader\Command\Download::ERROR_HISTORY_NOTFILE
     */
    public function testNotfileHistory() {
        $command = new Download();
        $root    = vfsStreamWrapper::getRoot();

        $command->history($root->url());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionCode Simondubois\UnsplashDownloader\Command\Download::ERROR_HISTORY_NOTRW
     */
    public function testNotrwHistory() {
        $command = new Download();
        $root    = vfsStreamWrapper::getRoot();
        $file    = $root->url().'/file';
        touch($file);
        chmod($file, 0);

        $command->history($file);
    }

    public function testSkippedDownload() {
        $command = new Download();
        $command->output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);

        $root  = vfsStreamWrapper::getRoot();
        $proxy = $this->mockProxy([$root->url(), 1, null], [
            'download' => function() {
                return Unsplash::DOWNLOAD_SKIPPED;
            },
        ]);

        $command->downloadAllPhotos($proxy);
        $this->assertContains('ignored (in history)', $command->output->fetch());
    }

    public function testFailedDownload() {
        $command = new Download();
        $command->output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);

        $root  = vfsStreamWrapper::getRoot();
        $proxy = $this->mockProxy([$root->url(), 1, null], [
            'download' => function() {
                return Unsplash::DOWNLOAD_FAILED;
            },
        ]);

        $command->downloadAllPhotos($proxy);
        $this->assertContains('failed', $command->output->fetch());
    }
}