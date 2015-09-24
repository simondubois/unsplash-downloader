<?php namespace Tests;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_TestCase;

abstract class AbstractTest extends PHPUnit_Framework_TestCase
{
    public function validParameterProvider()
    {
        $root = new vfsStreamDirectory('test');

        return [
            [$root->url(), 1, null],
            [$root->url(), 1, $root->url().'/new_history.txt'],
            [$root->url(), 1, $root->url().'/existing_history.txt'],
        ];
    }

    public function setUp()
    {
        vfsStream::setup('test');
    }

    protected function mockProxy($arguments, $methods)
    {
        $proxy = $this->getMockBuilder('Simondubois\UnsplashDownloader\Proxy\Unsplash')
            ->setMethods(array_keys($methods))
            ->setConstructorArgs($arguments)
            ->getMock();

        foreach ($methods as $name => $will) {
            $proxy->method($name)->will($will);
        }

        return $proxy;
    }
}
