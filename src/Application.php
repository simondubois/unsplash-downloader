<?php namespace Simondubois\UnsplashDownloader;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;

/**
 * An Application is the container for a collection of commands.
 * It is the main entry point of a Console application.
 * This class is optimized for a standard CLI environment.
 * @codeCoverageIgnore
 */
class Application extends SymfonyApplication
{
    /**
     * Gets the name of the command based on input.
     * @param InputInterface $input The input interface
     * @return string The command name
     */
    public function getCommandName(InputInterface $input)
    {
        // This should return the name of your command.
        return 'unsplash-downloader';
    }

    /**
     * Gets the InputDefinition related to this Application.
     * Overridden so that the application doesn't expect the command
     * name to be the first argument.
     * @return \Symfony\Component\Console\Input\InputDefinition The InputDefinition instance
    */
    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        // clear out the normal first argument, which is the command name
        $inputDefinition->setArguments();

        return $inputDefinition;
    }
}
