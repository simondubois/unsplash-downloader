<?php namespace Simondubois\UnsplashDownloader\Command;

use Exception;
use InvalidArgumentException;
use Simondubois\UnsplashDownloader\Proxy\Unsplash;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Download extends Command
{

    private $output;

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
        $this->output = $output;

        $this->parameters($input, $destination, $quantity, $history);

        $proxy = $this->connect($destination, $quantity, $history);

        $this->processDownload($proxy);
    }

    protected function parameters($input, &$destination, &$quantity, &$history)
    {
        $destination = $this->destination($input->getOption('destination'));
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln('Download photos to '.$destination.'.');
        }

        $quantity = $this->quantity($input->getOption('quantity'));
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln('Download the last '.$quantity.' photos.');
        }

        $history = $this->history($input->getOption('history'));
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            if (is_string($history)) {
                $this->output->writeln('Use '.$history.' as history.');
            } else {
                $this->output->writeln('Do not use history.');
            }
        }
    }

    protected function connect($destination, $quantity, $history)
    {
        $proxy = new Unsplash($destination, $quantity, $history);

        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->write('Connect to unsplash... ');
        }

        $connection = $proxy->isConnectionSuccessful();

        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            if ($connection === false) {
                $this->output->writeln('<error>failed</error>.');
            } else {
                $this->output->writeln('<info>success</info>.');
            }
        }

        if ($connection === false) {
            throw new Exception('Can not connect to unsplash (check your Internet connection');
        }

        return $proxy;
    }

    protected function processDownload($proxy)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->write('Get photo list from unsplash... ');
        }
        $photos = $proxy->photos();
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln('<info>success</info>.');
        }

        foreach ($photos as $photo) {
            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $source      = $proxy->photoSource($photo);
                $destination = $proxy->photoDestination($photo);

                $this->output->write('Download photo from '.$source.' to '.$destination.'... ');
            }

            $status = $proxy->download($photo);
            if ($status === Unsplash::DOWNLOAD_SUCCESS) {
                $this->output->writeln('<info>success</info>.');
            } elseif ($status === Unsplash::DOWNLOAD_HISTORY) {
                $this->output->writeln('<comment>ignored (in history)</comment>.');
            } elseif ($status === Unsplash::DOWNLOAD_FAILED) {
                $this->output->writeln('<error>failed</error>.');
            }
        }
    }



    //
    // Handle parameter "destination"
    //

    /**
     * Check validity of the destination parameter
     * @param  string $parameter Parameter value
     * @return string            Validated and formatted destination value
     */
    private function destination($parameter)
    {
        if (strstr($parameter, '://') === false) {
            $destination = realpath($parameter);
        } else {
            $destination = $parameter;
        }

        if ($destination === false) {
            throw new InvalidArgumentException('The given destination path ('.$parameter.') does not exists.');
        }

        if (is_dir($destination) === false) {
            throw new InvalidArgumentException('The given destination path ('.$destination.') is not a directory.');
        }

        if (is_writable($destination) === false) {
            throw new InvalidArgumentException('The given destination path ('.$destination.') is not writable.');
        }

        return $destination;
    }



    //
    // Handle parameter "quantity"
    //

    private function quantity($parameter)
    {
        if (is_numeric($parameter) === false) {
            throw new InvalidArgumentException('The given quantity ('.$parameter.') is not numeric.');
        }

        $quantity = intval($parameter);

        if ($quantity < 0) {
            throw new InvalidArgumentException('The given quantity ('.$parameter.') is not positive.');
        }

        if ($quantity >= 100) {
            throw new InvalidArgumentException(
                'The given quantity ('.$parameter.') is too high (should be lower than 100).'
            );
        }

        return $quantity;
    }



    //
    // Handle parameter "history"
    //

    /**
     * Check validity of the history parameter
     * @param  string $history Parameter value
     * @return null|string     Validated and formatted history value
     */
    private function history($history)
    {
        if (is_null($history)) {
            return null;
        }

        if (is_dir($history) === true) {
            throw new InvalidArgumentException('The given history path ('.$history.') is not a file.');
        }

        $handle = @fopen($history, 'a+');

        if ($handle === false) {
            throw new InvalidArgumentException(
                'The given history path ('.$history.') can not be opened for read & write.'
            );
        }

        fclose($handle);

        return $history;
    }
}
