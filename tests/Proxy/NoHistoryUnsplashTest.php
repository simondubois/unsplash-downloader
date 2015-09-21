<?php namespace Tests\Proxy;

class NoHistoryUnsplashTest extends AbstractUnsplashTest
{
    public function history()
    {
        return null;
    }
}
