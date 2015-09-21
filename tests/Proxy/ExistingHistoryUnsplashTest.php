<?php namespace Tests\Proxy;

class ExistingHistoryUnsplashTest extends AbstractUnsplashTest
{

    public function setUp() {
        parent::setUp();

        touch($this->destination().'/existing_history.txt');
    }

    public function history()
    {
        return $this->destination().'/existing_history.txt';
    }
}
