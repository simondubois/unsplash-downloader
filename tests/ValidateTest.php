<?php namespace Tests;

use Exception;
use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Simondubois\UnsplashDownloader\Validate;

class ValidateTest extends PHPUnit_Framework_TestCase
{

    //
    // API credentiels
    //

    /**
     * Test Simondubois\UnsplashDownloader\Validate::apiCredentials()
     */
    public function testNoFileApiCredentials() {
        $validate = new Validate();

        // Assert no API credentials
        $exceptionCode = null;
        try {
            $validate->apiCredentials(false, '');
        } catch (Exception $exception) {
            $exceptionCode = $exception->getCode();
        }

        $this->assertEquals(Validate::ERROR_NO_CREDENTIALS, $exceptionCode);
    }


    /**
     * Test Simondubois\UnsplashDownloader\Validate::apiCredentials()
     */
    public function testIncorrectApiCredentials() {
        $validate = new Validate();

        // Assert incorrect API credentials
        $exceptionCode = null;
        try {
            $validate->apiCredentials([], '');
        } catch (Exception $exception) {
            $exceptionCode = $exception->getCode();
        }

        $this->assertEquals(Validate::ERROR_INCORRECT_CREDENTIALS, $exceptionCode);
    }


    /**
     * Test Simondubois\UnsplashDownloader\Validate::apiCredentials()
     */
    public function testValidApiCredentials() {
        $validate = new Validate();
        $validate->apiCredentials(['applicationId' => 'your-application-id', 'secret' => 'your-secret'], '');
    }


    /**
     * Test Simondubois\UnsplashDownloader\Validate::quantity()
     */
    public function testNotNumericQuantity() {
        $validate = new Validate();

        $exceptionCode = null;
        try {
            $validate->quantity('abc');
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }

        $this->assertEquals(Validate::ERROR_QUANTITY_NOTNUMERIC, $exceptionCode);
    }


    /**
     * Test Simondubois\UnsplashDownloader\Validate::quantity()
     */
    public function testNotPositiveQuantity() {
        $validate = new Validate();

        $exceptionCode = null;
        try {
            $validate->quantity('-1');
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }

        $this->assertEquals(Validate::ERROR_QUANTITY_NOTPOSITIVE, $exceptionCode);
    }


    /**
     * Test Simondubois\UnsplashDownloader\Validate::quantity()
     */
    public function testTooHighQuantity() {
        $validate = new Validate();

        $exceptionCode = null;
        try {
            $validate->quantity('101');
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }

        $this->assertEquals(Validate::ERROR_QUANTITY_TOOHIGH, $exceptionCode);
    }


    /**
     * Test Simondubois\UnsplashDownloader\Validate::quantity()
     */
    public function testValidQuantity() {
        $validate = new Validate();

        $this->assertEquals(1, $validate->quantity('1'));
        $this->assertEquals(10, $validate->quantity('10'));
        $this->assertEquals(100, $validate->quantity('100'));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Validate::destination()
     */
    public function testNotDirectoryDestination() {
        $validate = new Validate();

        $root = vfsStream::setup('test')->url();
        $existingFile = $root.'/existingFile';
        touch($existingFile);

        $exceptionCode = null;
        try {
            $validate->destination($existingFile);
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }

        $this->assertEquals(Validate::ERROR_DESTINATION_NOTDIR, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Validate::destination()
     */
    public function testMissingFolderDestination() {
        $validate = new Validate();

        $root = vfsStream::setup('test')->url();
        $missingFolder = $root.'/missingFolder';

        $exceptionCode = null;
        try {
            $validate->destination($missingFolder);
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }

        $this->assertEquals(Validate::ERROR_DESTINATION_NOTDIR, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Validate::destination()
     */
    public function testUnwritableDestination() {
        $validate = new Validate();

        $root = vfsStream::setup('test')->url();
        $unwritableFolder = $root.'/unwritableFolder';
        mkdir($unwritableFolder, 0000);

        $exceptionCode = null;
        try {
            $validate->destination($unwritableFolder);
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }

        $this->assertEquals(Validate::ERROR_DESTINATION_UNWRITABLE, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Validate::destination()
     */
    public function testSuccessfulDestination() {
        $validate = new Validate();

        $root = vfsStream::setup('test')->url();
        $existingFolder = $root.'/existingFolder';
        mkdir($existingFolder);

        $this->assertEquals($existingFolder, $validate->destination($existingFolder));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Validate::history()
     */
    public function testNotFileHistory() {
        $validate = new Validate();

        $root = vfsStream::setup('test')->url();
        $existingFolder = $root.'/existingFolder';
        mkdir($existingFolder);

        $exceptionCode = null;
        try {
            $validate->history($existingFolder);
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Validate::ERROR_HISTORY_NOTFILE, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Validate::history()
     */
    public function testUnwritableHistory() {
        $validate = new Validate();

        $root = vfsStream::setup('test')->url();
        $unwritableFolder = $root.'/unwritableFolder';
        mkdir($unwritableFolder, 0000);
        $unwritableFile = $unwritableFolder.'/unwritableFile';

        $exceptionCode = null;
        try {
            $validate->history($unwritableFile);
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }

        $this->assertEquals(Validate::ERROR_HISTORY_NOTRW, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Validate::history()
     */
    public function testValidHistory() {
        $validate = new Validate();

        $root = vfsStream::setup('test')->url();
        $existingFile = $root.'/existingFile';
        $missingFile = $root.'/missingFile';
        touch($existingFile);

        $this->assertNull($validate->history(null));
        $this->assertEquals($existingFile, $validate->history($existingFile));
        $this->assertEquals($missingFile, $validate->history($missingFile));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Validate::category()
     */
    public function testValidCategory() {
        $validate = new Validate();

        $this->assertNull($validate->category(null));
        $this->assertEquals(1, $validate->category('1'));
        $this->assertEquals(0, $validate->category('abc'));
    }

}
