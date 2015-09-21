<?php namespace Tests\Proxy;

class NewHistoryUnsplashTest extends AbstractUnsplashTest
{
    public function history()
    {
        return $this->destination().'/new_history.txt';
    }
}
