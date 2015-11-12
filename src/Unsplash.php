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
     * Initialize API connection
     * @return bool True if the connection is successful
     */
    public function initHttpClient()
    {
        HttpClient::init([
            'applicationId' => '797a14e918f07f3559643a10f7c9e0de9d8a94262cd0ea0eb4b12c6d0993ed50',
            'secret' => 'e23d524da1e336251c8559ccc1214bad54a208f04057d984fd7f008e9039c869',
            'callbackUrl' => 'https://github.com/simondubois/unsplash-downloader',
        ]);

        return HttpClient::$connection instanceof Connection;
    }

    /**
     * Request APi to get some photos
     * @param  int $quantity Number of photos to return
     * @return ArrayObject Photos to download
     */
    public function allPhotos($quantity) {
        return Photo::all($quantity);
    }
}
