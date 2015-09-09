<?php namespace Simondubois\UnsplashDownloader\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Download extends Command
{

    protected function configure()
    {
        $this
            ->setName('download')
            ->setDescription('Download unsplash photos')
            ->addOption(
                'destination',
                'd',
                InputOption::VALUE_REQUIRED,
                'If defined, download photos into the specified directory',
                ''
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $destination = $this->destination($input->getOption('destination'));
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("Save photos to {$destination}");
        }
    }



    //
    // Handle option "destination"
    //

    private function destination($option)
    {
        if (substr($option, 0, 1) === '/') {
            $destination = $this->absoluteDestination($option);
        } else {
            $destination = $this->relativeDestination($option);
        }

        $destination = $this->validDestination($destination);

        return $destination;
    }

    private function absoluteDestination($option)
    {
        return $option;
    }

    private function relativeDestination($option)
    {
        return getcwd().'/'.$option;
    }

    private function validDestination($destination)
    {
        $validDestination = realpath($destination);

        if ($validDestination === false) {
            throw new Exception("The requested validDestination path ({$destination}) does not exists.");
        }

        if (is_dir($validDestination) === false) {
            throw new Exception("The requested validDestination path ({$validDestination}) is not a directory.");
        }

        if (is_writable($validDestination) === false) {
            throw new Exception("The requested validDestination path ({$validDestination}) is not writable.");
        }

        return $validDestination;
    }

}