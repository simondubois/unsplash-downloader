<?php namespace Tests\Command;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

class ValidDownloadTest extends AbstractDownloadTest
{
    /**
     * @dataProvider validParameterProvider
     */
    public function testValidParameters($destination, $quantity, $history) {
        if (strstr($history, 'existing') === true) {
            // vfsStream::create([$history => '']);
            vfsStreamWrapper::createFile($history);
        }

        $commandTester = $this->commandTester();
        $parameters    = $this->parameters($destination, $quantity, $history);

        $commandTester->execute($parameters);
        $this->assertEquals(0, $commandTester->getStatusCode());

        if (is_string($history)) {
            $this->assertCount($quantity + 1, vfsStreamWrapper::getRoot()->getChildren());
        } else {
            $this->assertCount($quantity, vfsStreamWrapper::getRoot()->getChildren());
        }

        if (is_string($history)) {
            $this->assertFileExists($history);
        }
    }
}