<?php namespace Tests;

use Crew\Unsplash\Photo;
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

    /**
     * Mock the Unsplash proxy
     * @param  array $arguments                        Array of argument for class constructor
     * @param  array $customMethods                    Array of methods to mock
     * @return PHPUnit_Framework_MockObject_MockObject Mocked Unsplash proxy instance
     */
    protected function mockProxy($arguments, $customMethods = [])
    {
        $methods = $customMethods + $this->defaultMockProxyMethods($arguments);

        $proxy = $this->getMockBuilder('Simondubois\UnsplashDownloader\Proxy\Unsplash')
            ->setMethods(array_keys($methods))
            ->setConstructorArgs($arguments)
            ->getMock();

        foreach ($methods as $name => $callback) {
            $proxy->method($name)
                ->will($this->returnCallback($callback));
        }

        return $proxy;
    }

    protected function defaultMockProxyMethods($arguments)
    {
        return [
            'isConnectionSuccessful' => function() {
                return true;
            },
            'photos' => function() use ($arguments) {
                $photos = [];
                for ($i = 1; $i <= $arguments[1]; ++$i) {
                    $photos[] = new Photo([
                        'id' => 'photo'.$i,
                        'links' => ['download' => 'http://url/to/photo'.$i],
                    ]);
                }
                return $photos;
            },
            'isDownloadSuccessful' => function($source, $destination) {
                return touch($destination);
            },
        ];
    }
}
