<?php namespace Tests;

use Crew\Unsplash\ArrayObject;
use Crew\Unsplash\Photo;
use Exception;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Simondubois\UnsplashDownloader\Task;

class TaskTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test Simondubois\UnsplashDownloader\Task::getNotificationCallback()
     *     & Simondubois\UnsplashDownloader\Task::setNotificationCallback()
     */
    public function testNotificationCallback() {
        // Instantiate task & custom value
        $task = new Task();
        $notificationCallback = function($message, $level = null) {
            return $level.':'.$message;
        };

        // Assert default value
        $this->assertTrue(is_callable($task->getNotificationCallback()));

        // Assert custom value
        $task->setNotificationCallback($notificationCallback);
        $notificationCallback = $task->getNotificationCallback();
        $this->assertTrue(is_callable($notificationCallback));
        $this->assertEquals('level:message', call_user_func($notificationCallback, 'message', 'level'));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::getDestination()
     *     & Simondubois\UnsplashDownloader\Task::setDestination()
     */
    public function testDestination() {
        // Instantiate task & custom value
        $task = new Task();
        $destination = 'destination';

        // Assert default value
        $this->assertNull($task->getDestination());

        // Assert custom value
        $task->setDestination($destination);
        $this->assertEquals($destination, $task->getDestination());
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::getQuantity()
     *     & Simondubois\UnsplashDownloader\Task::setQuantity()
     */
    public function testQuantity() {
        // Instantiate task & custom value
        $task = new Task();
        $quantity = 10;

        // Assert default value
        $this->assertNull($task->getQuantity());

        // Assert custom value
        $task->setQuantity($quantity);
        $this->assertEquals($quantity, $task->getQuantity());
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::getHistory()
     *     & Simondubois\UnsplashDownloader\Task::setHistory()
     */
    public function testHistory() {
        // Instantiate task & custom value
        $task = new Task();
        $history = 'history';

        // Assert default value
        $this->assertNull($task->getHistory());

        // Assert custom value
        $task->setHistory($history);
        $this->assertEquals($history, $task->getHistory());
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::notify()
     */
    public function testNotify() {
        // Instantiate task
        $task = new Task();

        // Callback
        $callback = $this->getMock('stdClass', ['callback']);
        $callback->expects($this->once())
            ->method('callback')
            ->with($this->identicalTo('message'), $this->identicalTo('level'));

        // Assert
        $task->setNotificationCallback([$callback, 'callback']);
        $task->notify('message', 'level');
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::execute()
     */
    public function testExecute() {
        // Assert connect error
        $history = $this->getMock('Simondubois\UnsplashDownloader\History');
        $task = $this->getMockBuilder('Simondubois\UnsplashDownloader\Task')
            ->setMethods(['getHistoryInstance', 'connect', 'getPhotos', 'downloadAllPhotos'])
            ->disableOriginalConstructor()
            ->getMock();
        $task->expects($this->once())->method('getHistoryInstance')->willReturn($history);
        $task->expects($this->once())->method('connect')->willReturn(false);
        $task->expects($this->never())->method('getPhotos');
        $task->expects($this->never())->method('downloadAllPhotos');
        $task->__construct();
        $task->execute();

        // Assert download error
        $history = $this->getMock('Simondubois\UnsplashDownloader\History');
        $task = $this->getMockBuilder('Simondubois\UnsplashDownloader\Task')
            ->setMethods(['getHistoryInstance', 'connect', 'getPhotos', 'downloadAllPhotos'])
            ->disableOriginalConstructor()
            ->getMock();
        $task->expects($this->once())->method('getHistoryInstance')->willReturn($history);
        $photos = new ArrayObject([], []);
        $task->expects($this->once())->method('connect')->willReturn(true);
        $task->expects($this->once())->method('getPhotos')->willReturn($photos);
        $task->expects($this->once())
            ->method('downloadAllPhotos')
            ->with($this->identicalTo($photos))
            ->willReturn(false);
        $task->__construct();
        $task->execute();

        // Assert success
        $history = $this->getMock('Simondubois\UnsplashDownloader\History');
        $task = $this->getMockBuilder('Simondubois\UnsplashDownloader\Task')
            ->setMethods(['getHistoryInstance', 'connect', 'getPhotos', 'downloadAllPhotos'])
            ->disableOriginalConstructor()
            ->getMock();
        $task->expects($this->once())->method('getHistoryInstance')->willReturn($history);
        $task->expects($this->once())->method('connect')->willReturn(true);
        $task->expects($this->once())->method('getPhotos')->willReturn($photos);
        $task->expects($this->once())
            ->method('downloadAllPhotos')
            ->with($this->identicalTo($photos))
            ->willReturn(false);
        $task->__construct();
        $task->execute();
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::connect()
     */
    public function testFailedConnect() {
        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['notify']);
        $task->expects($this->exactly(2))->method('notify')->withConsecutive(
            [$this->anything(), $this->identicalTo(null)],
            [$this->anything(), $this->identicalTo(Task::NOTIFY_ERROR)]
        );

        // Instantiate proxy
        $unsplash = $this->getMock('Simondubois\UnsplashDownloader\Unsplash', ['initHttpClient']);
        $unsplash->expects($this->once())->method('initHttpClient')->willReturn(false);

        // Assert exception
        $exceptionCode = null;
        try {
            $task->connect($unsplash);
        } catch (Exception $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Task::ERROR_CONNECTION, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::connect()
     */
    public function testSuccessfulConnect() {
        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['notify']);
        $task->expects($this->exactly(2))->method('notify')->withConsecutive(
            [$this->anything(), $this->identicalTo(null)],
            [$this->anything(), $this->identicalTo(Task::NOTIFY_INFO)]
        );

        // Instantiate proxy
        $unsplash = $this->getMock('Simondubois\UnsplashDownloader\Unsplash', ['initHttpClient']);
        $unsplash->expects($this->once())->method('initHttpClient')->willReturn(true);

        // Assert return value
        $this->assertTrue($task->connect($unsplash));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::getPhotos()
     */
    public function testGetPhotos() {
        // Prepare data
        $quantity = 10;
        $photos = new ArrayObject(array_fill(0, $quantity, 'photo'), []);

        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['notify']);
        $task->expects($this->exactly(2))->method('notify')->withConsecutive(
            [$this->anything(), $this->identicalTo(null)],
            [$this->anything(), $this->identicalTo(Task::NOTIFY_INFO)]
        );
        $task->setQuantity($quantity);

        // Instantiate proxy
        $unsplash = $this->getMock('Simondubois\UnsplashDownloader\Unsplash', ['allPhotos']);
        $unsplash->expects($this->once())
            ->method('allPhotos')
            ->with($this->identicalTo($quantity))
            ->willReturn($photos);

        // Assert return value
        $this->assertEquals($photos, $task->getPhotos($unsplash));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::downloadAllPhotos()
     */
    public function testFailedDownloadAllPhotos() {
        // Prepare data
        $quantity = 10;
        $photo = new Photo([
            'id' => 1,
            'links' => ['download' => 'http://example.com']
        ]);
        $photos = new ArrayObject(array_fill(0, $quantity, $photo), []);

        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['downloadOnePhoto']);
        $task->expects($this->exactly($quantity))
            ->method('downloadOnePhoto')
            ->with($this->identicalTo($photo))
            ->willReturn(false);
        $task->setQuantity($quantity);

        // Assert return value
        $this->assertEquals(false, $task->downloadAllPhotos($photos));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::downloadAllPhotos()
     */
    public function testSuccessfulDownloadAllPhotos() {
        // Prepare data
        $quantity = 10;
        $photo = new Photo([
            'id' => 1,
            'links' => ['download' => 'http://example.com']
        ]);
        $photos = new ArrayObject(array_fill(0, $quantity, $photo), []);

        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['downloadOnePhoto']);
        $task->expects($this->exactly($quantity))
            ->method('downloadOnePhoto')
            ->with($this->identicalTo($photo))
            ->willReturn(true);
        $task->setQuantity($quantity);

        // Assert return value
        $this->assertEquals(true, $task->downloadAllPhotos($photos));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::downloadOnePhoto()
     */
    public function testDownloadOnePhotoInHistory() {
        // Prepare data
        $destination = 'destination';
        $photoId = 1;
        $photoSource = 'http://example.com';
        $photoDestination = $destination.'/'.$photoId.'.jpg';
        $photo = new Photo([
            'id' => $photoId,
            'links' => ['download' => $photoSource]
        ]);

        // Initiate history
        $history = $this->getMock('Simondubois\UnsplashDownloader\History', ['has', 'put']);
        $history->expects($this->once())->method('has')->willReturn(true);
        $history->expects($this->never())->method('put');

        // Initiate task
        $task = $this->getMockBuilder('Simondubois\UnsplashDownloader\Task')
            ->setMethods(['getHistoryInstance', 'notify'])
            ->disableOriginalConstructor()
            ->getMock();
        $task->expects($this->once())
            ->method('getHistoryInstance')
            ->willReturn($history);
        $task->expects($this->exactly(2))
            ->method('notify')
            ->withConsecutive(
                [$this->stringContains($photoSource), $this->identicalTo(null)],
                [$this->anything(), $this->identicalTo(Task::NOTIFY_COMMENT)]
            );

        // Assert download photo in history
        $task->__construct();
        $task->setDestination($destination);
        $this->assertEquals(true, $task->downloadOnePhoto($photo));
    }
    /**
     * Test Simondubois\UnsplashDownloader\Task::downloadOnePhoto()
     */
    public function testDownloadOnePhotoFailed() {
        // Prepare data
        $destination = 'destination';
        $photoId = 1;
        $photoSource = 'http://example.com';
        $photoDestination = $destination.'/'.$photoId.'.jpg';
        $photo = new Photo([
            'id' => $photoId,
            'links' => ['download' => $photoSource]
        ]);

        // Initiate history
        $history = $this->getMock('Simondubois\UnsplashDownloader\History', ['has', 'put']);
        $history->expects($this->once())->method('has')->willReturn(false);
        $history->expects($this->never())->method('put');

        // Initiate task
        $task = $this->getMockBuilder('Simondubois\UnsplashDownloader\Task')
            ->setMethods(['getHistoryInstance', 'notify', 'copyFile'])
            ->disableOriginalConstructor()
            ->getMock();
        $task->expects($this->once())
            ->method('getHistoryInstance')
            ->willReturn($history);
        $task->expects($this->exactly(2))
            ->method('notify')
            ->withConsecutive(
                [$this->stringContains($photoSource), $this->identicalTo(null)],
                [$this->anything(), $this->identicalTo(Task::NOTIFY_ERROR)]
            );
        $task->expects($this->once())
            ->method('copyFile')
            ->with($this->identicalTo($photoSource), $this->identicalTo($photoDestination))
            ->willReturn(false);

        // Assert failed download
        $task->__construct();
        $task->setDestination($destination);
        $this->assertEquals(false, $task->downloadOnePhoto($photo));
    }
    /**
     * Test Simondubois\UnsplashDownloader\Task::downloadOnePhoto()
     */
    public function testSuccessfulDownloadOnePhoto() {
        // Prepare data
        $destination = 'destination';
        $photoId = 1;
        $photoSource = 'http://example.com';
        $photoDestination = $destination.'/'.$photoId.'.jpg';
        $photo = new Photo([
            'id' => $photoId,
            'links' => ['download' => $photoSource]
        ]);

        // Initiate history
        $history = $this->getMock('Simondubois\UnsplashDownloader\History', ['has', 'put']);
        $history->expects($this->once())->method('has')->willReturn(false);
        $history->expects($this->once())->method('put');

        // Initiate task
        $task = $this->getMockBuilder('Simondubois\UnsplashDownloader\Task')
            ->setMethods(['getHistoryInstance', 'notify', 'copyFile'])
            ->disableOriginalConstructor()
            ->getMock();
        $task->expects($this->once())
            ->method('getHistoryInstance')
            ->willReturn($history);
        $task->expects($this->exactly(2))
            ->method('notify')
            ->withConsecutive(
                [$this->stringContains($photoSource), $this->identicalTo(null)],
                [$this->anything(), $this->identicalTo(Task::NOTIFY_INFO)]
            );
        $task->expects($this->once())
            ->method('copyFile')
            ->with($this->identicalTo($photoSource), $this->identicalTo($photoDestination))
            ->willReturn(true);

        // Assert successful download
        $task->__construct();
        $task->setDestination($destination);
        $this->assertEquals(true, $task->downloadOnePhoto($photo));
    }
}
