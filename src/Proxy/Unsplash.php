<?php namespace Simondubois\UnsplashDownloader\Proxy;

use Crew\Unsplash\HttpClient;
use Crew\Unsplash\Photo;

class Unsplash
{

    private $output;
    private $destination;
    private $quantity;

    public function __construct($destination, $quantity)
    {
        $this->destination = $destination;
        $this->quantity = $quantity;

        $this->connect();
    }

    private function connect()
    {
        HttpClient::init([
            'applicationId' => '797a14e918f07f3559643a10f7c9e0de9d8a94262cd0ea0eb4b12c6d0993ed50',
            'secret' => 'e23d524da1e336251c8559ccc1214bad54a208f04057d984fd7f008e9039c869',
            'callbackUrl' => 'https://github.com/simondubois/unsplash-downloader',
        ]);
    }

    public function photos() {
        return Photo::all(1, $this->quantity);
    }

    public function download(Photo $photo) {
        $source = $photo->links['download'];
    }

}
