<?php namespace Tests;

use Exception;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Simondubois\UnsplashDownloader\History;

class HistoryTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test Simondubois\UnsplashDownloader\Task::getPath()
     */
    public function testGetPath() {
        // Instantiate history
        $history = new History();

        // Assert default value
        $this->assertNull($history->getPath());

        // Assert custom value
        $path = 'history';
        $history->load($path);
        $this->assertEquals($path, $history->getPath());
    }

    /**
     * Test Simondubois\UnsplashDownloader\Task::load()
     *     & Simondubois\UnsplashDownloader\Task::loadContent()
     *     & Simondubois\UnsplashDownloader\Task::getContent()
     *     & Simondubois\UnsplashDownloader\Task::has()
     *     & Simondubois\UnsplashDownloader\Task::put()
     *     & Simondubois\UnsplashDownloader\Task::save()
     *     & Simondubois\UnsplashDownloader\Task::saveContent()
     */
    public function testLoadAndSave() {
        // Instantiate file system
        $root = vfsStream::setup('test')->url();
        $missingFile = $root.'/missingFile';
        $existingFile = $root.'/existingFile';
        $content = ['abc', 'def', '', 'ghi'];
        file_put_contents($existingFile, implode(PHP_EOL, $content));

        // Assert save before load
        $history = new History();
        $this->assertFalse($history->save());

        // Assert missing file
        $this->assertFalse($history->load($missingFile));
        $this->assertEmpty($history->getContent());

        // Assert successful load
        $this->assertTrue($history->load($existingFile));
        $this->assertEquals($content, $history->getContent());
        $this->assertTrue($history->has('abc'));
        $this->assertTrue($history->has(''));

        // Assert put & has
        $this->assertFalse($history->has('jkl'));
        $history->put('jkl');
        $content[] = 'jkl';
        $this->assertTrue($history->has('jkl'));

        // Assert save
        $this->assertTrue($history->save());
        $this->assertEquals($content, $history->getContent());
        $this->assertEquals(implode(PHP_EOL, $content), file_get_contents($existingFile));

        // Assert second load
        try {
            $history->load($existingFile);
        } catch (Exception $exception) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

}
