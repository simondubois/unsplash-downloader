<?php namespace Simondubois\UnsplashDownloader;

use Crew\Unsplash\Photo;
use Crew\Unsplash\ArrayObject;

/**
 * A task to download photos
 * - connect to the server
 * - list photos
 * - download photos
 */
class Task
{

    //
    // Constants
    //

    /**
     * Error codes
     */
    const ERROR_CONNECTION = 1;

    /**
     * Notification types
     */
    const NOTIFY_INFO = 'info';
    const NOTIFY_ERROR = 'error';

    /**
     * Download status
     */
    const DOWNLOAD_SUCCESS = 0;
    const DOWNLOAD_SKIPPED = 1;
    const DOWNLOAD_FAILED  = 2;



    //
    // Attributes
    //

    /**
     * History proxy
     * @var Simondubois\UnsplashDownloader\Proxy\History
     */
    private $history;

    /**
     * Unsplash proxy
     * @var Simondubois\UnsplashDownloader\Proxy\Unsplash
     */
    private $unsplash;

    /**
     * Callback to call when notification arised : function ($message, $level = null) {};
     * @var callable
     */
    private $notificationCallback;

    /**
     * Path where to download photos
     * @var string
     */
    private $destination;

    /**
     * Number of photos to download
     * @var int
     */
    private $quantity;



    //
    // Setters
    //

    /**
     * Task constructor (set non scalar attributes)
     */
    public function __construct()
    {
        $this->history = new History();
        $this->unsplash = new Unsplash();
        $this->notificationCallback = function ($message, $level = null) {};
    }

    /**
     * Set notification callback attribute
     * @param callable $notificationCallback function ($message, $level = null) {}
     */
    public function setNotificationCallback($notificationCallback)
    {
        $this->notificationCallback = $notificationCallback;
    }

    /**
     * Set destination attribute
     * @param string $destination Path to folder
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    /**
     * Set quantity attribute
     * @param int $quantity Number of photos to download
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * Set history attribute
     * @param string $history Path to file
     */
    public function setHistory($history)
    {
        $this->history->load($history);
    }



    //
    // Notification
    //

    /**
     * Call the notification callback when a notification arised
     * @param  string $message Message text
     * @param  string $level Message context
     */
    private function notify($message, $level = null)
    {
        $callback = $this->notificationCallback;
        call_user_func($callback, $message, $level);
    }



    //
    //  Execution
    //

    /**
     * Find photos and download them
     * @return bool True if the execution is successful
     */
    public function execute()
    {
        if ($this->connect() === false) {
            return false;
        }

        $photos = $this->getPhotos();
        $success = $this->downloadAllPhotos($photos);
        $this->history->save();

        return $success;
    }

    /**
     * Connect to API
     * @return boolean True if the connection is successful
     */
    private function connect()
    {
        $this->notify('Connect to unsplash... ');

        if ($this->unsplash->initHttpClient()) {
            $this->notify('success.'.PHP_EOL, self::NOTIFY_INFO);
            return true;
        }

        $this->notify('failed.'.PHP_EOL, self::NOTIFY_ERROR);
        throw new \Exception(
            'Can not connect to unsplash (check your Internet connection)',
            self::ERROR_CONNECTION
        );

        return false;
    }

    /**
     * Request APi to get photos to downloads
     * @return Crew\Unsplash\ArrayObject Photos to download
     */
    private function getPhotos() {
        $this->notify('Get photo list from unsplash... ');
        $photos = $this->unsplash->allPhotos($this->quantity);
        $this->notify('success.'.PHP_EOL, 'info');

        return $photos;
    }

    /**
     * Download all photos
     * @param Crew\Unsplash\ArrayObject $photos Photos to download
     * @return boolean True if all downloads are successful
     */
    private function downloadAllPhotos(ArrayObject $photos)
    {
        $success = true;

        foreach ($photos as $photo) {
            if ($this->downloadOnePhoto($photo) === false) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Download one photo
     * @param  Photo $photo Photo instance
     */
    private function downloadOnePhoto(Photo $photo)
    {
        if ($this->history->has($photo->id)) {
            $this->notify('ignored (in history).'.PHP_EOL, 'comment');
            return true;
        }

        $source      = $photo->links['download'];
        $destination = $this->destination.'/'.$photo->id.'.jpg';

        $this->notify('Download photo from '.$source.' to '.$destination.'... ');

        $status = $this->copyFile($source, $destination);
        if ($status === false) {
            $this->notify('failed.'.PHP_EOL, 'error');
            return false;
        }

        $this->history->put($photo->id);

        $this->notify('success.'.PHP_EOL, 'info');
        return true;
    }

    /**
     * Download file from source to destination
     * @param  string $source      URL to download the file from
     * @param  string $destination Path to download the file to
     * @return bool                True if the copy is successful
     */
    public function copyFile($source, $destination) {
        return @copy($source, $destination);
    }

}
