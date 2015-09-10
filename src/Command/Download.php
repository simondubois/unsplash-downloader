<?php namespace Simondubois\UnsplashDownloader\Command;

use Exception;
use Simondubois\UnsplashDownloader\Proxy\Unsplash;
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
                null,
                InputOption::VALUE_REQUIRED,
                'If defined, download photos into the specified directory',
                ''
            )
            ->addOption(
                'quantity',
                null,
                InputOption::VALUE_REQUIRED,
                'If defined, number of photos to downaload',
                '10'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $destination = $this->destination($input->getOption('destination'));
        if ($output->isVerbose()) {
            $output->writeln("Download photos to {$destination}.");
        }

        $quantity = $this->quantity($input->getOption('quantity'));
        if ($output->isVerbose()) {
            $output->writeln("Download the last {$quantity} photos.");
        }

        $this->download($output, $destination, $quantity);
    }

    protected function download(OutputInterface $output, $destination, $quantity)
    {
        $proxy = new Unsplash($destination, $quantity);

        foreach ($proxy->photos() as $photo) {
            if ($output->isVerbose()) {
                $output->writeln("Download photo.");
            }

            $proxy->download($photo);
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

        $validDestination = $this->validDestination($destination);

        return $validDestination;
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
            throw new Exception("The given destination path ({$destination}) does not exists.");
        }

        if (is_dir($validDestination) === false) {
            throw new Exception("The given destination path ({$validDestination}) is not a directory.");
        }

        if (is_writable($validDestination) === false) {
            throw new Exception("The given destination path ({$validDestination}) is not writable.");
        }

        return $validDestination;
    }



    //
    // Handle option "destination"
    //

    private function quantity($option)
    {
        if (is_numeric($option) === false) {
            throw new Exception("The given quantity ({$option}) is not numeric.");
        }

        $quantity = intval($option);

        if ($quantity < 0) {
            throw new Exception("The given quantity ({$option}) is not positive.");
        }

        if ($quantity >= 100) {
            throw new Exception("The given quantity ({$option}) is too high (should be lower than 100).");
        }

        return $quantity;
    }

}
