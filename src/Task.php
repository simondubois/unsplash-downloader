<?php namespace Simondubois\UnsplashDownloader;

use Crew\Unsplash\ArrayObject;
use Crew\Unsplash\Photo;

/**
 * A task to download photos from Unsplash. Steps are
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
    const NOTIFY_COMMENT = 'comment';
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
     * @var History
     */
    private $history;

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

    /**
     * Unsplash application ID from https://unsplash.com/developers
     * @var string
     */
    private $unsplashApplicationId;

    /**
     * Unsplash application secret from https://unsplash.com/developers
     * @var string
     */
    private $unsplashSecret;



    //
    // Getters
    //

    /**
     * Get history proxy attribute. Instantiate it if null
     * @return History Instance
     */
    public function getHistoryInstance()
    {
        if (is_null($this->history)) {
            $this->history = new History();
        }

        return $this->history;
    }

    /**
     * Get notification callback attribute
     * @return callable function ($message, $level = null) {}
     */
    public function getNotificationCallback()
    {
        return $this->notificationCallback;
    }

    /**
     * Get destination attribute
     * @return string Path to folder
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * Get quantity attribute
     * @return int Number of photos to download
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Get history path attribute
     * @return string Path to file
     */
    public function getHistory()
    {
        return $this->history->getPath();
    }

    /**
     * Set credential attribute in unsplash instance
     * @return array Array with two indexes : 'applicationId' & 'secret'
     */
    public function getCredentials()
    {
        return [
            'applicationId' => $this->unsplashApplicationId,
            'secret' => $this->unsplashSecret,
        ];
    }



    //
    // Setters
    //

    /**
     * Task constructor (set non scalar attributes)
     */
    public function __construct()
    {
        $this->history = $this->getHistoryInstance();
        $this->notificationCallback = function($message, $level = null) {};
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
     * Set path attribute in history instance
     * @param string $history Path to file
     */
    public function setHistory($history)
    {
        $this->history->load($history);
    }

    /**
     * Set credential attribute in unsplash instance
     * @param string $applicationId Unsplash application ID
     * @param string $secret Unsplash secret
     */
    public function setCredentials($applicationId, $secret)
    {
        $this->unsplashApplicationId = $applicationId;
        $this->unsplashSecret = $secret;
    }



    //
    // Notification
    //

    /**
     * Call the notification callback when a notification arised
     * @param  string $message Message text
     * @param  string|null $level Message context
     */
    public function notify($message, $level = null)
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
        $unsplash = new Unsplash($this->unsplashApplicationId, $this->unsplashSecret);

        if ($this->connect($unsplash) === false) {
            return false;
        }

        $photos = $this->getPhotos($unsplash);
        $success = $this->downloadAllPhotos($photos);
        $this->history->save();

        return $success;
    }

    /**
     * Connect to API
     * @param Unsplash $unsplash Proxy to Unsplash API
     * @return boolean True if the connection is successful
     */
    public function connect(Unsplash $unsplash)
    {
        $this->notify('Connect to unsplash... ');

        if ($unsplash->initHttpClient()) {
            $this->notify('success.'.PHP_EOL, self::NOTIFY_INFO);
            return true;
        }

        $this->notify('failed.'.PHP_EOL, self::NOTIFY_ERROR);
        throw new \Exception(
            'Can not connect to unsplash (check your Internet connection)',
            self::ERROR_CONNECTION
        );
    }

    /**
     * Request APi to get photos to downloads
     * @param Unsplash $unsplash Proxy to Unsplash API
     * @return ArrayObject Photos to download
     */
    public function getPhotos(Unsplash $unsplash) {
        $this->notify('Get photo list from unsplash... ');
        $photos = $unsplash->allPhotos($this->quantity);
        $this->notify('success.'.PHP_EOL, 'info');

        return $photos;
    }

    /**
     * Download all photos
     * @param ArrayObject $photos Photos to download
     * @return boolean True if all downloads are successful
     */
    public function downloadAllPhotos(ArrayObject $photos)
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
    public function downloadOnePhoto(Photo $photo)
    {
        $source      = $photo->links['download'];
        $destination = $this->destination.'/'.$photo->id.'.jpg';
        $this->notify('Download photo from '.$source.' to '.$destination.'... ');

        if ($this->history->has($photo->id)) {
            $this->notify('ignored (in history).'.PHP_EOL, self::NOTIFY_COMMENT);
            return true;
        }

        $status = $this->copyFile($source, $destination);

        if ($status === false) {
            $this->notify('failed.'.PHP_EOL, self::NOTIFY_ERROR);
            return false;
        }

        $this->history->put($photo->id);

        $this->notify('success.'.PHP_EOL, self::NOTIFY_INFO);
        return true;
    }

    /**
     * Download file from source to destination
     * @param  string $source      URL to download the file from
     * @param  string $destination Path to download the file to
     * @return bool                True if the copy is successful
     * @codeCoverageIgnore
     */
    public function copyFile($source, $destination) {
        return @copy($source, $destination);
    }

}
