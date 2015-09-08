<?php namespace Simondubois\UnsplashDownloader\Command;

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
                '.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $destination = getcwd().'/'.$input->getOption('destination');
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("Save photos to {$destination}");
        }
    }
}