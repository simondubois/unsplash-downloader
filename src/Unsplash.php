<?php namespace Simondubois\UnsplashDownloader;

use Crew\Unsplash\ArrayObject;
use Crew\Unsplash\Connection;
use Crew\Unsplash\HttpClient;
use Crew\Unsplash\Photo;

/**
 * A proxy to deal with the Unsplah API :
 * - connect to the server.
 * - list photos
 * @codeCoverageIgnore
 */
class Unsplash
{

    /**
     * Unsplash application ID from https://unsplash.com/developers
     * @var string
     */
    private $applicationId;

    /**
     * Unsplash application secret from https://unsplash.com/developers
     * @var string
     */
    private $secret;

    /**
     * Unsplash constructor (set credentials)
     * @param string $applicationId Unsplash application ID
     * @param string $secret Unsplash secret
     */
    public function __construct($applicationId, $secret)
    {
        $this->applicationId = $applicationId;
        $this->secret = $secret;
    }

    /**
     * Initialize API connection
     * @return bool True if the connection is successful
     */
    public function initHttpClient()
    {
        HttpClient::init([
            'applicationId' => $this->applicationId,
            'secret' => $this->secret,
        ]);

        return HttpClient::$connection instanceof Connection;
    }

    /**
     * Request APi to get some photos
     * @param  int $quantity Number of photos to return
     * @return ArrayObject Photos to download
     */
    public function allPhotos($quantity)
    {
        return Photo::all($quantity);
    }
}
