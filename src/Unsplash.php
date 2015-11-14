<?php namespace Simondubois\UnsplashDownloader;

use Crew\Unsplash\ArrayObject;
use Crew\Unsplash\Connection;
use Crew\Unsplash\CuratedBatch;
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
     * Request APi to get last photos
     * @param  int $quantity Number of photos to return
     * @return array<string, string> Photo download links indexed by ID
     */
    public function allPhotos($quantity)
    {
        $photos = [];

        foreach (Photo::all($quantity) as $photo) {
            $photos[$photo->id] = $photo->links['download'];
        };

        return $photos;
    }

    /**
     * Request APi to get featured photos
     * @param  int $quantity Number of photos to return
     * @return array<string, string> Photo download links indexed by ID
     */
    public function featuredPhotos($quantity)
    {
        $photos = [];

        // process currated batches
        foreach (CuratedBatch::all(1, 100) as $batchInfo) {
            $batch = CuratedBatch::find($batchInfo->id);

            // process photos
            foreach ($batch->photos(1, 100) as $photo) {
                $photos[$photo->id] = $photo->links['download'];

                // quit if $quantity photos have been found
                if (count($photos) >= $quantity) {
                    break 2;
                }
            }
        }

        return $photos;
    }
}
