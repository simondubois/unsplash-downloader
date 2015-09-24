<?php namespace Tests\Command;

use Tests\AbstractTest;
use Simondubois\UnsplashDownloader\Application;
use Simondubois\UnsplashDownloader\Command\Download;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Tester\CommandTester;

abstract class AbstractDownloadTest extends AbstractTest
{
    public function commandTester()
    {
        $application = new Application();
        $application->add(new Download());

        $this->assertEquals('download', $application->getCommandName(new ArgvInput(null)));
        $command = $application->find('download');

        return new CommandTester($command);
    }

    public function parameters($destination, $quantity, $history)
    {
        $parameters = [];

        if (is_string($destination)) {
            $parameters['--destination'] = $destination;
        }

        if (is_numeric($quantity)) {
            $parameters['--quantity'] = $quantity;
        }

        if (is_string($history)) {
            $parameters['--history'] = $history;
        }

        return $parameters;
    }
}
