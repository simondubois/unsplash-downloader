<?php namespace Simondubois\UnsplashDownloader;

/**
 * A task to download photos from Unsplash. Steps are
 * - list photos
 * - download photos
 */
class Task
{

    //
    // Constants
    //

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
     * Category ID
     * @var int
     */
    private $category;

    /**
     * True if the task should only download featured photos
     * @var bool
     */
    private $featured;



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
     * Get category attribute
     * @return int Category ID
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Get featured attribute
     * @return bool True if the task should only download featured photos
     */
    public function getFeatured()
    {
        return $this->featured;
    }

    /**
     * Get history path attribute
     * @return string Path to file
     */
    public function getHistory()
    {
        return $this->history->getPath();
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
     * Set category attribute
     * @param int $category Number of photos to download
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * Set featured attribute
     * @param bool $featured True if the task should only download featured photos
     */
    public function setFeatured($featured)
    {
        $this->featured = $featured;
    }

    /**
     * Set path attribute in history instance
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
    public function download()
    {
        $unsplash = new Unsplash();

        $photos = $this->getPhotos($unsplash);
        $success = $this->downloadAllPhotos($photos);
        $this->history->save();

        return $success;
    }

    /**
     * List categories
     * @return bool True if the execution is successful
     */
    public function categories()
    {
        $unsplash = new Unsplash();

        return $this->listCategories($unsplash);
    }

    /**
     * Request APi to get photos to downloads
     * @param Unsplash $unsplash Proxy to Unsplash API
     * @return string[] Photo download links indexed by ID
     */
    public function getPhotos(Unsplash $unsplash) {
        $this->notify('Get photo list from unsplash... ');

        if ($this->featured) {
            $photos = $unsplash->featuredPhotos($this->quantity);
        } elseif (is_int($this->category)) {
            $photos = $unsplash->photosInCategory($this->quantity, $this->category);
        } else {
            $photos = $unsplash->allPhotos($this->quantity);
        }
        $this->notify('success.'.PHP_EOL, 'info');

        return $photos;
    }

    /**
     * Download all photos
     * @param string[] $photos Photo download links indexed by ID
     * @return boolean True if all downloads are successful
     */
    public function downloadAllPhotos($photos)
    {
        $success = true;

        foreach ($photos as $id => $source) {
            if ($this->downloadOnePhoto($id, $source) === false) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Download one photo
     * @param  string $id Photo id
     * @param  string $source Photo downlaod url
     */
    public function downloadOnePhoto($id, $source)
    {
        $destination = $this->destination.'/'.$id.'.jpg';
        $this->notify('Download photo from '.$source.' to '.$destination.'... ');

        if ($this->history->has($id)) {
            $this->notify('ignored (in history).'.PHP_EOL, self::NOTIFY_COMMENT);
            return true;
        }

        $status = $this->copyFile($source, $destination);

        if ($status === false) {
            $this->notify('failed.'.PHP_EOL, self::NOTIFY_ERROR);
            return false;
        }

        $this->history->put($id);

        $this->notify('success.'.PHP_EOL, self::NOTIFY_INFO);
        return true;
    }

    /**
     * List all categories returned by API
     * @param Unsplash $unsplash Proxy to Unsplash API
     * @return boolean True on success
     */
    public function listCategories(Unsplash $unsplash)
    {
        $this->notify('Unsplash categories :'.PHP_EOL);

        $categories = $unsplash->allCategories();
        foreach ($categories as $id => $name) {
            $this->notify(sprintf("\t%s => %s%s", $id, $name, PHP_EOL));
        }

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
