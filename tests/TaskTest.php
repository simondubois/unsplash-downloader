<?php namespace Tests;

use Crew\Unsplash\ArrayObject;
use Exception;
use PHPUnit_Framework_TestCase;
use Simondubois\UnsplashDownloader\Task;

class TaskTest extends PHPUnit_Framework_TestCase
{

    /**
     * Mock History class and stub has() & put() methods
     * @param  mixed $has Value returned by has() method, null for no call to has() method.
     * @param  mixed $put Value returned by put() method, null for no call to put() method.
     * @return object Mocked history
     */
    private function mockHistory($has, $put) {
        $methods = [
            'has' => $has,
            'put' => $put,
        ];

        $history = $this->getMock('Simondubois\UnsplashDownloader\History', array_keys($methods));

        foreach ($methods as $key => $value) {
            $history->expects(is_null($value) ? $this->never() : $this->once())
                ->method($key)
                ->willReturn($value);
        }

        return $history;
    }

    /**
     * Mock Task class for downloadOnePhoto() method tests
     * @param  boolean $hasHistory Value returned by History::has() method, null for no call to History::has() method.
     * @param  null|bool $putHistory Value returned by History::put() method, null for no call to History::put() method.
     * @param  string $notificationStatus Status to pass to notify() method
     * @param  null|bool $copyFile Value returned by copyFile() method, null for no call to History::copyFile() method.
     * @return object Mocked task
     */
    private function mockTaskForDownloadOnePhoto($hasHistory, $putHistory, $notificationStatus, $copyFile) {
        $task = $this->getMock(
            'Simondubois\UnsplashDownloader\Task', ['getHistoryInstance', 'notify', 'copyFile'], [], '', false
        );

        $task->expects($this->once())
            ->method('getHistoryInstance')
            ->willReturn($this->mockHistory($hasHistory, $putHistory));

        $task->expects($this->exactly(2))->method('notify')->withConsecutive(
            [$this->stringContains('http://www.example.com'), $this->identicalTo(null)],
            [$this->anything(), $this->identicalTo($notificationStatus)]
        );

        $task->expects(is_null($copyFile) ? $this->never() : $this->once())
            ->method('copyFile')
            ->with($this->identicalTo('http://www.example.com'), $this->identicalTo('destination/0123456789.jpg'))
            ->willReturn($copyFile);

        return $task;
    }

    /**
     * Mock Unsplash class for getPhotos() tests
     * @return object Mocked proxy
     */
    public function mockUnsplashForGetPhotos() {
        $unsplash = $this->getMock(
            'Simondubois\UnsplashDownloader\Unsplash',
            ['allPhotos', 'photosInCategory', 'featuredPhotos']
        );

        return $unsplash;
    }

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
     * Test Simondubois\UnsplashDownloader\Task::getCategory()
     *     & Simondubois\UnsplashDownloader\Task::setCategory()
     */
    public function testCategory() {
        // Instantiate task & custom value
        $task = new Task();
        $category = 1;

        // Assert default value
        $this->assertNull($task->getCategory());

        // Assert custom value
        $task->setCategory($category);
        $this->assertEquals($category, $task->getCategory());
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
     * Test Simondubois\UnsplashDownloader\Task::getFeatured()
     *     & Simondubois\UnsplashDownloader\Task::setFeatured()
     */
    public function testFeatured() {
        // Instantiate task & custom value
        $task = new Task();
        $featured = true;

        // Assert default value
        $this->assertNull($task->getFeatured());

        // Assert custom value
        $task->setFeatured($featured);
        $this->assertEquals($featured, $task->getFeatured());
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
     * Test Simondubois\UnsplashDownloader\Task::download()
     */
    public function testDownloadError() {
        $history = $this->getMock('Simondubois\UnsplashDownloader\History');
        $task = $this->getMock(
            'Simondubois\UnsplashDownloader\Task',
            ['getHistoryInstance', 'getPhotos', 'downloadAllPhotos'],
            [],
            '',
            false
        );
        $task->expects($this->once())->method('getHistoryInstance')->willReturn($history);
        $photos = new ArrayObject([], []);
        $task->expects($this->once())->method('getPhotos')->willReturn($photos);
        $task->expects($this->once())
            ->method('downloadAllPhotos')
            ->with($this->identicalTo($photos))
            ->willReturn(false);
        $task->__construct();
        $task->download();
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::download()
     */
    public function testDownloadSuccess() {
        $history = $this->getMock('Simondubois\UnsplashDownloader\History');
        $task = $this->getMock(
            'Simondubois\UnsplashDownloader\Task',
            ['getHistoryInstance', 'getPhotos', 'downloadAllPhotos'],
            [],
            '',
            false
        );
        $photos = new ArrayObject([], []);
        $task->expects($this->once())->method('getHistoryInstance')->willReturn($history);
        $task->expects($this->once())->method('getPhotos')->willReturn($photos);
        $task->expects($this->once())
            ->method('downloadAllPhotos')
            ->with($this->identicalTo($photos))
            ->willReturn(false);
        $task->__construct();
        $task->download();
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::categories()
     */
    public function testCategories() {
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['listCategories']);
        $task->expects($this->once())->method('listCategories')->willReturn(true);
        $this->assertTrue($task->categories());
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::getPhotos()
     */
    public function testGetAllPhotos() {
        $quantity = 10;
        $photos = ['0123456789' => 'http://www.example.com'];

        // Instantiate proxy
        $unsplash = $this->mockUnsplashForGetPhotos();
        $unsplash->expects($this->once())
            ->method('allPhotos')
            ->with($this->identicalTo($quantity))
            ->willReturn($photos);
        $unsplash->expects($this->never())->method('photosInCategory');
        $unsplash->expects($this->never())->method('featuredPhotos');

        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['notify']);
        $task->expects($this->exactly(2))->method('notify')->withConsecutive(
            [$this->identicalTo('Get photo list from unsplash... '), $this->identicalTo(null)],
            [$this->identicalTo('success.'.PHP_EOL), $this->identicalTo(Task::NOTIFY_INFO)]
        );
        $task->setQuantity($quantity);
        $this->assertEquals($photos, $task->getPhotos($unsplash));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::getPhotos()
     */
    public function testGetPhotosInCategory() {
        $quantity = 10;
        $photos = ['0123456789' => 'http://www.example.com'];

        // Instantiate proxy
        $unsplash = $this->mockUnsplashForGetPhotos();
        $unsplash->expects($this->never())->method('allPhotos');
        $unsplash->expects($this->once())
            ->method('photosInCategory')
            ->with($this->identicalTo($quantity), $this->identicalTo(123))
            ->willReturn($photos);
        $unsplash->expects($this->never())->method('featuredPhotos');

        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['notify']);
        $task->expects($this->exactly(2))->method('notify')->withConsecutive(
            [$this->identicalTo('Get photo list from unsplash... '), $this->identicalTo(null)],
            [$this->identicalTo('success.'.PHP_EOL), $this->identicalTo(Task::NOTIFY_INFO)]
        );
        $task->setQuantity($quantity);
        $task->setCategory(123);
        $this->assertEquals($photos, $task->getPhotos($unsplash));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::getPhotos()
     */
    public function testGetFeaturedPhotos() {
        $quantity = 10;
        $photos = ['0123456789' => 'http://www.example.com'];

        // Instantiate proxy
        $unsplash = $this->mockUnsplashForGetPhotos();
        $unsplash->expects($this->never())->method('allPhotos');
        $unsplash->expects($this->never())->method('photosInCategory');
        $unsplash->expects($this->once())->method('featuredPhotos')
            ->with($this->identicalTo($quantity))->willReturn($photos);

        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['notify']);
        $task->expects($this->exactly(2))->method('notify')->withConsecutive(
            [$this->identicalTo('Get photo list from unsplash... '), $this->identicalTo(null)],
            [$this->identicalTo('success.'.PHP_EOL), $this->identicalTo(Task::NOTIFY_INFO)]
        );
        $task->setQuantity($quantity);
        $task->setFeatured(true);
        $this->assertEquals($photos, $task->getPhotos($unsplash));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::downloadAllPhotos()
     */
    public function testFailedDownloadAllPhotos() {
        // Prepare data
        $quantity = 10;
        $url = 'http://www.example.com';
        $photos = array_fill(0, $quantity, $url);

        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['downloadOnePhoto']);
        $task->expects($this->exactly($quantity))->method('downloadOnePhoto')->withConsecutive(
            [$this->identicalTo(0), $this->identicalTo($url)], [$this->identicalTo(1), $this->identicalTo($url)],
            [$this->identicalTo(2), $this->identicalTo($url)], [$this->identicalTo(3), $this->identicalTo($url)],
            [$this->identicalTo(4), $this->identicalTo($url)], [$this->identicalTo(5), $this->identicalTo($url)],
            [$this->identicalTo(6), $this->identicalTo($url)], [$this->identicalTo(7), $this->identicalTo($url)],
            [$this->identicalTo(8), $this->identicalTo($url)], [$this->identicalTo(9), $this->identicalTo($url)]
        )->willReturn(false);
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
        $url = 'http://www.example.com';
        $photos = array_fill(0, $quantity, $url);

        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['downloadOnePhoto']);
        $task->expects($this->exactly($quantity))->method('downloadOnePhoto')->withConsecutive(
                [$this->identicalTo(0), $this->identicalTo($url)], [$this->identicalTo(1), $this->identicalTo($url)],
                [$this->identicalTo(2), $this->identicalTo($url)], [$this->identicalTo(3), $this->identicalTo($url)],
                [$this->identicalTo(4), $this->identicalTo($url)], [$this->identicalTo(5), $this->identicalTo($url)],
                [$this->identicalTo(6), $this->identicalTo($url)], [$this->identicalTo(7), $this->identicalTo($url)],
                [$this->identicalTo(8), $this->identicalTo($url)], [$this->identicalTo(9), $this->identicalTo($url)]
            )->willReturn(true);
        $task->setQuantity($quantity);

        // Assert return value
        $this->assertEquals(true, $task->downloadAllPhotos($photos));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::downloadOnePhoto()
     */
    public function testDownloadOnePhotoInHistory() {
        // Initiate task
        $task = $this->mockTaskForDownloadOnePhoto(true, null, Task::NOTIFY_COMMENT, null);

        // Assert download photo in history
        $task->__construct();
        $this->assertEquals(true, $task->downloadOnePhoto('0123456789', 'http://www.example.com'));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::downloadOnePhoto()
     */
    public function testFailedDownloadOnePhoto() {
        // Initiate task
        $task = $this->mockTaskForDownloadOnePhoto(false, null, Task::NOTIFY_ERROR, false);

        // Assert failed download
        $task->__construct();
        $task->setDestination('destination');
        $this->assertEquals(false, $task->downloadOnePhoto('0123456789', 'http://www.example.com'));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::downloadOnePhoto()
     */
    public function testSuccessfulDownloadOnePhoto() {
        // Initiate task
        $task = $this->mockTaskForDownloadOnePhoto(false, true, Task::NOTIFY_INFO, true);

        // Assert successful download
        $task->__construct();
        $task->setDestination('destination');
        $this->assertEquals(true, $task->downloadOnePhoto('0123456789', 'http://www.example.com'));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::listCategories()
     */
    public function testListCategories() {
        // Initiate custom values
        $categories = [1 => 'First category', 2 => 'Second category'];

        // Instantiate proxy
        $unsplash = $this->getMock('Simondubois\UnsplashDownloader\Unsplash', ['allCategories']);
        $unsplash->expects($this->once())->method('allCategories')->willReturn($categories);

        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['notify']);
        $task->expects($this->exactly(3))->method('notify')->withConsecutive(
            [$this->identicalTo('Unsplash categories :'.PHP_EOL)],
            [$this->identicalTo("\t1 => First category".PHP_EOL)],
            [$this->identicalTo("\t2 => Second category".PHP_EOL)]
        );
        $task->listCategories($unsplash);
    }
}
