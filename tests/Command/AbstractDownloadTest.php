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

        $this->assertEquals('unsplash-downloader', $application->getCommandName(new ArgvInput(null)));
        $command = $application->find('unsplash-downloader');

        return new CommandTester($command);
    }

    public function parameters($destination, $quantity, $history, $verbose = false)
    {
        $parameters = [];

        $parameters = $this->parametersDestination($parameters, $destination);
        $parameters = $this->parametersQuantity($parameters, $quantity);
        $parameters = $this->parametersHistory($parameters, $history);
        $parameters = $this->parametersVerbose($parameters, $verbose);

        return $parameters;
    }

    private function parametersDestination($parameters, $destination)
    {
        if (is_string($destination)) {
            $parameters['--destination'] = $destination;
        }

        return $parameters;
    }

    private function parametersQuantity($parameters, $quantity)
    {
        if (is_numeric($quantity)) {
            $parameters['--quantity'] = $quantity;
        }

        return $parameters;
    }

    private function parametersHistory($parameters, $history)
    {
        if (is_string($history)) {
            $parameters['--history'] = $history;
        }

        return $parameters;
    }

    private function parametersVerbose($parameters, $verbose)
    {
        if ($verbose === true) {
            $parameters['--verbose'] = true;
        }

        return $parameters;
    }
}
