<?php namespace Tests\Command;

use Tests\TestBase;
use Simondubois\UnsplashDownloader\Application;
use Simondubois\UnsplashDownloader\Command\Download;
use Symfony\Component\Console\Tester\CommandTester;

abstract class DownloadTest extends TestBase
{
    public function commandTester()
    {
        $application = new Application();
        $application->add(new Download());

        $command = $application->find('download');

        return new CommandTester($command);
    }

    public function validParameterProvider()
    {
        $destination = $this->destination();

        return [
            [$destination, $this->quantity(), null],
            [$destination, $this->quantity(), $destination.'/new_history.txt'],
            [$destination, $this->quantity(), $destination.'/existing_history.txt'],
        ];
    }

    /**
     * @dataProvider validParameterProvider
     */
    public function testValidParameters($destination) {
        $commandTester = $this->commandTester();
        $commandTester->execute($parameters);
        $this->assert(0, $commandTester->getStatusCode());

        $files = scandir($destination);
        $this->assertCount($quantity + 3, $files);

        if (is_string($this->history())) {
            $this->assertFileExists($this->history());
        }
    }
}
