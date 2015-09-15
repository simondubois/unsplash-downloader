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
            ->addOption(
                'history',
                null,
                InputOption::VALUE_REQUIRED,
                'If defined, filename will be used to record download history. '
                    .'When photos are downloaded, their IDs will be stored into the file. '
                    .'Then any further download is going to ignore photos that have their ID in the history. '
                    .'Usefull to delete unwanted pictures and prevent the programm to download them again.'
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

        $history = $this->history($input->getOption('history'));
        if ($output->isVerbose()) {
            if (is_string($history)) {
                $output->writeln("Use {$history} as history.");
            } else {
                $output->writeln("Do not use history.");
            }
        }

        $this->download($output, $destination, $quantity, $history);
    }

    protected function download(OutputInterface $output, $destination, $quantity, $history)
    {
        $proxy = new Unsplash($destination, $quantity, $history);

        foreach ($proxy->photos() as $photo) {
            if ($output->isVerbose()) {
                $source = $proxy->photoSource($photo);
                $destination = $proxy->photoDestination($photo);

                $output->write("Download photo from {$source} to {$destination}... ");
            }

            $status = $proxy->download($photo);
            if ($status === Unsplash::DOWNLOAD_SUCCESS) {
                $output->writeln("<info>success</info>.");
            } elseif ($status === Unsplash::DOWNLOAD_HISTORY) {
                $output->writeln("<comment>ignored (in history)</comment>.");
            } elseif ($status === Unsplash::DOWNLOAD_FAILED) {
                $output->writeln("<error>failed</error>.");
            }
        }
    }



    //
    // Handle option "destination"
    //

    private function destination($option)
    {
        $destination = $this->resolvedPath($option);
        $destination = realpath($destination);

        if ($destination === false) {
            throw new Exception("The given destination path ({$option}) does not exists.");
        }

        if (is_dir($destination) === false) {
            throw new Exception("The given destination path ({$destination}) is not a directory.");
        }

        if (is_writable($destination) === false) {
            throw new Exception("The given destination path ({$destination}) is not writable.");
        }

        return $destination;
    }



    //
    // Handle option "quantity"
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



    //
    // Handle option "history"
    //

    private function history($option)
    {
        if (is_null($option)) {
            return $option;
        }

        $history = $this->resolvedPath($option);

        if (is_dir($history) === true) {
            throw new Exception("The given history path ({$history}) is not a file.");
        }

        $handle = @fopen($history, 'a+');

        if ($handle === false) {
            throw new Exception("The given history path ({$option}) can not be opened for read & write.");
        }

        fclose($handle);

        return $history;
    }




    //
    // Helpers
    //

    private function resolvedPath($path)
    {
        if (substr($path, 0, 1) !== '/') {
            $path = getcwd().'/'.$path;
        }

        return $path;
    }

}
