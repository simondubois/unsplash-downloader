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

    /**
     * Output text only if run with verbose attribute
     * @param  string  $text    Text to output
     * @param  boolean $newLine Shall the method append a new line character to the text
     * @return void             No return
     */
    protected function verboseOutput($text, $newLine = true) {
        if ($this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            return;
        }

        if ($newLine === true) {
            $this->output->writeln($text);
            return;
        }

        $this->output->write($text);
    }

    protected function configure()
    {
        $this->setName('download');
        $this->setDescription('Download unsplash photos');
        $this->configureOptions();
    }

    private function configureOptions()
    {
        $this->addOption(
            'destination',
            null,
            InputOption::VALUE_REQUIRED,
            'If defined, download photos into the specified directory',
            ''
        );
        $this->addOption(
            'quantity',
            null,
            InputOption::VALUE_REQUIRED,
            'If defined, number of photos to downaload',
            '10'
        );
        $this->addOption(
            'history',
            null,
            InputOption::VALUE_REQUIRED,
            'If defined, filename will be used to record download history. '
                .'When photos are downloaded, their IDs will be stored into the file. '
                .'Then any further download is going to ignore photos that have their ID in the history. '
                .'Usefull to delete unwanted pictures and prevent the programm to download them again.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->parameters($input, $destination, $quantity, $history);

        $proxy = $this->connect($destination, $quantity, $history);

        $this->downloadAllPhotos($proxy);
    }

    /**
     * Check & validate the parameters
     * @param  InputInterface $input    Command input
     * @param  string &$destination     Validated destination parameter
     * @param  string &$quantity        Validated quantity parameter
     * @param  string|null &$history    Validated history parameter
     * @return void                     No return
     */
    protected function parameters($input, &$destination, &$quantity, &$history)
    {
        $destination = $this->destination($input->getOption('destination'));
        $this->verboseOutput('Download photos to '.$destination.'.');

        $quantity = $this->quantity($input->getOption('quantity'));
        $this->verboseOutput('Download the last '.$quantity.' photos.');

        $history = $this->history($input->getOption('history'));
        if (is_string($history)) {
            $this->verboseOutput('Use '.$history.' as history.');
        } else {
            $this->verboseOutput('Do not use history.');
        }
    }

    protected function connect($destination, $quantity, $history)
    {
        $proxy = new Unsplash($destination, $quantity, $history);

        $this->verboseOutput('Connect to unsplash... ', false);
        $connection = $proxy->isConnectionSuccessful();

        if ($connection === false) {
            $this->verboseOutput('<error>failed</error>.');
        } else {
            $this->verboseOutput('<info>success</info>.');
        }

        if ($connection === false) {
            throw new Exception('Can not connect to unsplash (check your Internet connection');
        }

        return $proxy;
    }

    /**
     * Download all photos
     * @param  Unsplash $proxy Unsplash proxy
     * @return void            No return
     */
    protected function downloadAllPhotos($proxy)
    {
        $this->verboseOutput('Get photo list from unsplash... ', false);

        $photos = $proxy->photos();
        $this->verboseOutput('<info>success</info>.');

        foreach ($photos as $photo) {
            $this->downloadOnePhoto($proxy, $photo);
        }
    }

    protected function downloadOnePhoto($proxy, $photo)
    {
        $source      = $proxy->photoSource($photo);
        $destination = $proxy->photoDestination($photo);

        $this->verboseOutput('Download photo from '.$source.' to '.$destination.'... ', false);

        $status = $proxy->download($photo);
        if ($status === Unsplash::DOWNLOAD_SUCCESS) {
            $this->verboseOutput('<info>success</info>.');
        } elseif ($status === Unsplash::DOWNLOAD_HISTORY) {
            $this->verboseOutput('<comment>ignored (in history)</comment>.');
        } elseif ($status === Unsplash::DOWNLOAD_FAILED) {
            $this->verboseOutput('<error>failed</error>.');
        }
    }



    //
    // Handle parameter "destination"
    //

    /**
     * Check validity of the destination parameter
     * @param  string $destination Parameter value
     * @return string              Validated and formatted destination value
     */
    private function destination($destination)
    {
        if ($destination === false) {
            throw new InvalidArgumentException('The given destination path ('.$destination.') does not exists.');
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
