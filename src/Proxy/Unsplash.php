<?php namespace Simondubois\UnsplashDownloader\Proxy;

use Crew\Unsplash\Connection;
use Crew\Unsplash\HttpClient;
use Crew\Unsplash\Photo;

class Unsplash
{

    const DOWNLOAD_SUCCESS = 0;
    const DOWNLOAD_HISTORY = 1;
    const DOWNLOAD_FAILED  = 2;

    private $destination;
    private $quantity;
    private $historyPath;
    private $historyList = [];

    public function __construct($destination, $quantity, $history)
    {
        $this->destination = $destination;
        $this->quantity    = $quantity;

        if (is_string($history)) {
            $this->historyPath = $history;

            if (is_file($this->historyPath)) {
                $this->historyList = file($this->historyPath, FILE_IGNORE_NEW_LINES);
            }
        }
    }

    public function __destruct()
    {
        if (is_string($this->historyPath)) {
            file_put_contents($this->historyPath, implode(PHP_EOL, $this->historyList));
        }
    }

    public function isConnectionSuccessful()
    {
        HttpClient::init([
            'applicationId' => '797a14e918f07f3559643a10f7c9e0de9d8a94262cd0ea0eb4b12c6d0993ed50',
            'secret' => 'e23d524da1e336251c8559ccc1214bad54a208f04057d984fd7f008e9039c869',
            'callbackUrl' => 'https://github.com/simondubois/unsplash-downloader',
        ]);

        return HttpClient::$connection instanceof Connection;
    }

    public function photos() {
        return Photo::all(1, $this->quantity);
    }

    public function download(Photo $photo) {
        if (in_array($photo->id, $this->historyList)) {
            return self::DOWNLOAD_HISTORY;
        }

        $source      = $this->photoSource($photo);
        $destination = $this->photoDestination($photo);

        $ret = $this->isDownloadSuccessful($source, $destination);
        if ($ret === false) {
            return self::DOWNLOAD_FAILED;
        }

        if (is_string($this->historyPath)) {
            $this->historyList[] = $photo->id;
        }

        return self::DOWNLOAD_SUCCESS;
    }

    /**
     * Download photo from the source to the destination
     * @param  string $source      URL to download the photo from
     * @param  string $destination Path to download the photo to
     * @return bool                True on success, false on error
     */
    public function isDownloadSuccessful($source, $destination) {
        return @copy($source, $destination);
    }

    /**
     * Get the download source URL for the given photo
     * @param  Photo  $photo Photo to process
     * @return string        Source url
     */
    public function photoSource(Photo $photo) {
        return $photo->links['download'];
    }

    /**
     * Get the download destination path for the given photo
     * @param  Photo  $photo Photo to process
     * @return string        Destination path
     */
    public function photoDestination(Photo $photo) {
        return $this->destination.'/'.$photo->id.'.jpg';
    }
}
