<?php namespace Simondubois\UnsplashDownloader\Command;

use Exception;
use InvalidArgumentException;
use Simondubois\UnsplashDownloader\Proxy\Unsplash;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A download command handle the whole process to download photos.
 * The steps are :
 *  - check option validity (destination, count and history).
 *  - create a proxy (to deal with Unsplash API).
 *  - connect proxy to API.
 *  - get list of photos.
 *  - download each photo.
 */
class Download extends Command
{
    const ERROR_CONNECTION             = 1;
    const ERROR_DESTINATION_NOTDIR     = 2;
    const ERROR_DESTINATION_UNWRITABLE = 3;
    const ERROR_QUANTITY_NOTNUMERIC    = 4;
    const ERROR_QUANTITY_NOTPOSITIVE   = 5;
    const ERROR_QUANTITY_TOOHIGH       = 6;
    const ERROR_HISTORY_NOTFILE        = 7;
    const ERROR_HISTORY_NOTRW          = 8;

    /**
     * Output instance.
     * Stored to simplify method calls.
     * @var OutputInterface
     */
    public $output;



    //
    // Handle command setup
    //

    /**
     * Output text only if run with verbose attribute
     * @param  string  $text    Text to output
     * @param  boolean $newLine Shall the method append a new line character to the text
     * @return void
     */
    public function verboseOutput($text, $newLine = true) {
        if ($this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            return;
        }

        if ($newLine === true) {
            $this->output->writeln($text);
            return;
        }

        $this->output->write($text);
    }

    /**
     * Configure the Symfony command
     * @return void
     */
    protected function configure()
    {
        $this->setName('unsplash-downloader');
        $this->setDescription('Download unsplash photos');
        $this->configureOptions();
    }

    /**
     * Set command options
     * @return void
     */
    private function configureOptions()
    {
        $this->addOption(
            'destination', null, InputOption::VALUE_REQUIRED, 'Directory where to download photos', getcwd()
        );
        $this->addOption(
            'quantity', null, InputOption::VALUE_REQUIRED, 'Number of photos to download', '10'
        );
        $this->addOption(
            'history',
            null,
            InputOption::VALUE_REQUIRED,
            'Filename to use as download history.
                When photos are downloaded, their IDs will be stored into the file.
                Then any further download is going to ignore photos that have their ID in the history.
                Usefull to delete unwanted pictures and prevent the CLI to download them again.'
        );
    }



    //
    // Handle command operations
    //

    /**
     * Process the download based on provided options
     * @param  InputInterface  $input  Command input
     * @param  OutputInterface $output Command output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->parameters($input, $destination, $quantity, $history);
        $proxy = new Unsplash($destination, $quantity, $history);
        $this->connect($proxy);
        $this->downloadAllPhotos($proxy);
    }

    /**
     * Check & validate the parameters
     * @param  InputInterface $input    Command input
     * @param  string $destination      Validated destination parameter
     * @param  string $quantity         Validated quantity parameter
     * @param  string|null $history     Validated history parameter
     * @return void
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

    /**
     * Connect proxy to API
     * @param  Unsplash $proxy Unsplash proxy
     * @return void
     */
    public function connect($proxy)
    {
        $this->verboseOutput('Connect to unsplash... ', false);
        $connection = $proxy->isConnectionSuccessful();

        if ($connection === false) {
            $this->verboseOutput('<error>failed</error>.');
        } else {
            $this->verboseOutput('<info>success</info>.');
        }

        if ($connection === false) {
            throw new Exception(
                'Can not connect to unsplash (check your Internet connection',
                self::ERROR_CONNECTION
            );
        }
    }

    /**
     * Download all photos
     * @param  Unsplash $proxy Unsplash proxy
     * @return void
     */
    public function downloadAllPhotos($proxy)
    {
        $this->verboseOutput('Get photo list from unsplash... ', false);

        $photos = $proxy->photos();
        $this->verboseOutput('<info>success</info>.');

        foreach ($photos as $photo) {
            $this->downloadOnePhoto($proxy, $photo);
        }
    }

    /**
     * Download one photo
     * @param  Unsplash $proxy Unsplash proxy
     * @param  Photo $photo    Photo instance
     * @return void
     */
    protected function downloadOnePhoto($proxy, $photo)
    {
        $source      = $proxy->photoSource($photo);
        $destination = $proxy->photoDestination($photo);

        $this->output->write('Download photo from '.$source.' to '.$destination.'... ', false);

        $status = $proxy->download($photo);
        if ($status === Unsplash::DOWNLOAD_SUCCESS) {
            $this->output->writeln('<info>success</info>.');
        } elseif ($status === Unsplash::DOWNLOAD_SKIPPED) {
            $this->output->writeln('<comment>ignored (in history)</comment>.');
        } elseif ($status === Unsplash::DOWNLOAD_FAILED) {
            $this->output->writeln('<error>failed</error>.');
        }
    }



    //
    // Handle parameter validations
    //

    /**
     * Check validity of the destination parameter
     * @param  string $destination Parameter value
     * @return string              Validated and formatted destination value
     */
    public function destination($destination)
    {
        if (is_dir($destination) === false) {
            throw new InvalidArgumentException(
                'The given destination path ('.$destination.') is not a directory.',
                self::ERROR_DESTINATION_NOTDIR
            );
        }

        if (is_writable($destination) === false) {
            throw new InvalidArgumentException(
                'The given destination path ('.$destination.') is not writable.',
                self::ERROR_DESTINATION_UNWRITABLE
            );
        }

        return $destination;
    }

    /**
     * Check validity of the quantity parameter
     * @param  string $parameter Parameter value
     * @return int               Validated and formatted quantity value
     */
    public function quantity($parameter)
    {
        $quantity = $this->quantityFormat($parameter);

        $this->quantityValidation($quantity);

        return $quantity;
    }

    /**
     * Format the quantity to integer
     * @param  string $parameter Parameter value
     * @return int               Formatted quantity value
     */
    private function quantityFormat($parameter)
    {
        if (is_numeric($parameter) === false) {
            throw new InvalidArgumentException(
                'The given quantity ('.$parameter.') is not numeric.',
                self::ERROR_QUANTITY_NOTNUMERIC
            );
        }

        return intval($parameter);
    }

    /**
     * Check the quantity value
     * @return int quantity Formatted quantity value
     * @return void
     */
    private function quantityValidation($quantity)
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException(
                'The given quantity ('.$quantity.') is not positive.',
                self::ERROR_QUANTITY_NOTPOSITIVE
            );
        }

        if ($quantity >= 100) {
            throw new InvalidArgumentException(
                'The given quantity ('.$quantity.') is too high (should be lower than 100).',
                self::ERROR_QUANTITY_TOOHIGH
            );
        }
    }

    /**
     * Check validity of the history parameter
     * @param  string $history Parameter value
     * @return null|string     Validated and formatted history value
     */
    public function history($history)
    {
        if (is_null($history)) {
            return null;
        }

        $this->historyValidationType($history);
        $this->historyValidationAccess($history);

        return $history;
    }

    /**
     * Check if history is not a dir
     * @param  string $history Parameter value
     * @return void
     */
    private function historyValidationType($history)
    {
        if (is_dir($history) === true) {
            throw new InvalidArgumentException(
                'The given history path ('.$history.') is not a file.',
                self::ERROR_HISTORY_NOTFILE
            );
        }
    }

    /**
     * Check if history is accessible
     * @param  string $history Parameter value
     * @return void
     */
    private function historyValidationAccess($history)
    {
        $handle = @fopen($history, 'a+');

        if ($handle === false) {
            throw new InvalidArgumentException(
                'The given history path ('.$history.') can not be created or opened for read & write.',
                self::ERROR_HISTORY_NOTRW
            );
        }

        fclose($handle);
    }
}
