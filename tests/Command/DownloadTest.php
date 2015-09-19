<?php namespace Tests\Command;

use Tests\TestBase;
use Simondubois\UnsplashDownloader\Application;
use Simondubois\UnsplashDownloader\Command\Download;
use Symfony\Component\Console\Tester\CommandTester;

class DownloadTest extends TestBase
{
    /**
     * @dataProvider validParameterProvider
     */
    public function testValidParameters($destination, $quantity, $history) {
        $application = new Application();
        $application->add(new Download());

        $command = $application->find('download');
        $parameters = [
            '--destination' => $destination,
            '--quantity' => $quantity,
            '--history' => $history,
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($parameters);
        $this->assert(0, $commandTester->getStatusCode());

        $files = scandir($destination);
        $this->assertCount($quantity + 3, $files);
    }
}
