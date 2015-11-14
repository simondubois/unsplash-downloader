<?php namespace Simondubois\UnsplashDownloader;

use InvalidArgumentException;
use Exception;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to check parameters validity and call a download task. Steps are :
 *  - check option validity (destination, count, history, featured, categories).
 *  - load credentials (from local unsplash.ini file).
 *  - create a task (to deal with Unsplash API).
 *  - execute the task.
 */
class Command extends SymfonyCommand
{

    //
    // Constants & attributes
    //

    /**
     * Error codes
     */
    const ERROR_DESTINATION_NOTDIR     = 1;
    const ERROR_DESTINATION_UNWRITABLE = 2;
    const ERROR_QUANTITY_NOTNUMERIC    = 3;
    const ERROR_QUANTITY_NOTPOSITIVE   = 4;
    const ERROR_QUANTITY_TOOHIGH       = 5;
    const ERROR_HISTORY_NOTFILE        = 6;
    const ERROR_HISTORY_NOTRW          = 7;
    const ERROR_NO_CREDENTIALS         = 8;
    const ERROR_INCORRECT_CREDENTIALS  = 9;

    /**
     * Output instance.
     * Stored to simplify method calls and to be used in callbacks.
     * @var OutputInterface
     */
    public $output;

    /**
     * Path to INI file where to find API credentials.
     * File unsplash.ini in the CWD by default.
     * @var string
     */
    public $apiCrendentialsPath = 'unsplash.ini';



    //
    // Helpers
    //

    /**
     * Output text only if run with verbose attribute
     * @param  string  $message Text to output
     * @param  string|null $context Context of the message
     * @param  int $type Symfony output type
     */
    public function verboseOutput($message, $context = null, $type = OutputInterface::OUTPUT_NORMAL) {
        if ($this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            return;
        }

        if (is_string($context)) {
            $append = '';

            if (substr($message, -1) === PHP_EOL) {
                $message = substr($message, 0, -1);
                $append = PHP_EOL;
            }

            $message = sprintf('<%s>%s</%s>%s', $context, $message, $context, $append);
        }

        $this->output->write($message, false, $type);
    }



    /**
     * Output text only if run with verbose attribute
     * @param  string  $message Text to output
     * @param  string|null $context Context of the message
     * @param  int $type Symfony output type
     */
    public function output($message, $context = null, $type = OutputInterface::OUTPUT_NORMAL) {
        if ($this->output->getVerbosity() < OutputInterface::VERBOSITY_NORMAL) {
            return;
        }

        if (is_string($context)) {
            $append = '';

            if (substr($message, -1) === PHP_EOL) {
                $message = substr($message, 0, -1);
                $append = PHP_EOL;
            }

            $message = sprintf('<%s>%s</%s>%s', $context, $message, $context, $append);
        }

        $this->output->write($message, false, $type);
    }



    //
    // Handle command setup
    //

    /**
     * Configure the Symfony command
     */
    protected function configure()
    {
        $this->setName('unsplash-downloader');
        $this->setDescription('Download unsplash photos');
        $this->configureOptions();
    }

    /**
     * Set command options
     */
    private function configureOptions()
    {
        $this->addOption(
            'destination', null, InputOption::VALUE_REQUIRED, 'Directory where to download photos.', getcwd()
        );
        $this->addOption(
            'quantity', null, InputOption::VALUE_REQUIRED, 'Number of photos to download.', '10'
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
        $this->addOption('featured', null, InputOption::VALUE_NONE, 'Download only featured photos.');
        $this->addOption(
            'categories',
            null,
            InputOption::VALUE_NONE,
            'List categories and quit (no download will be performed).'
        );
    }



    //
    // Handle command operations
    //

    /**
     * Process the download based on provided options
     * @param  InputInterface  $input  Command input
     * @param  OutputInterface $output Command output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $task = $this->task();
        $this->loadApiCredentials($task);

        if ($input->getOption('categories')) {
            $task->categories();
            return;
        }

        $this->parameters($task, $input->getOptions());
        $task->download();
    }

    /**
     * Instantiate a new task
     * @return Task Task instance
     */
    public function task() {
        $task = new Task();
        $task->setNotificationCallback([$this, 'output']);

        return $task;
    }

    /**
     * Check & validate the parameters
     * @param  Task $task Download task
     * @param  array $options Command options
     */
    public function parameters(Task $task, $options)
    {
        $destination = $this->destination($options['destination']);
        $task->setDestination($destination);
        $this->verboseOutput('Download photos to '.$destination.'.'.PHP_EOL);

        $quantity = $this->quantity($options['quantity']);
        $task->setQuantity($quantity);
        $this->verboseOutput('Download the last '.$quantity.' photos.'.PHP_EOL);

        $history = $this->history($options['history']);
        $task->setHistory($history);
        if (is_string($history)) {
            $this->verboseOutput('Use '.$history.' as history.'.PHP_EOL);
        } else {
            $this->verboseOutput('Do not use history.'.PHP_EOL);
        }

        $featured = $options['featured'];
        $task->setFeatured($featured);
        if ($featured) {
            $this->verboseOutput('Download only featured photos.'.PHP_EOL);
        } else {
            $this->verboseOutput('Download featured and not featured photos.'.PHP_EOL);
        }
    }

    /**
     * Load API credentials
     * @param  Task $task Download task
     */
    public function loadApiCredentials(Task $task)
    {
        $this->verboseOutput('Load credentials from unsplash.ini :'.PHP_EOL);
        $credentials = @parse_ini_file($this->apiCrendentialsPath);

        if ($credentials === false) {
            throw new Exception(
                'The credentials file has not been found.'.PHP_EOL
                    .'Please create the file '.$this->apiCrendentialsPath.' with the following content :'.PHP_EOL
                    .'applicationId = "your-application-id"'.PHP_EOL
                    .'secret = "your-secret"'.PHP_EOL
                    .'Register to https://unsplash.com/developers to get your gredentials.',
                static::ERROR_NO_CREDENTIALS
            );
        }

        $this->validApiCredentials($task, $credentials);
    }

    /**
     * Valid loaded credentials and assign credentials to the task
     * @param  Task $task Download task
     * @param  array $credentials Loaded credentials
     */
    private function validApiCredentials(Task $task, $credentials) {
        if (!isset($credentials['applicationId']) || !isset($credentials['secret'])) {
            throw new Exception(
                'The credentials file is not correct : '
                    .'please check that both applicationId and secret are correctly defined.',
                static::ERROR_INCORRECT_CREDENTIALS
            );
        }

        $this->verboseOutput("\tApplication ID\t: ".$credentials['applicationId'].PHP_EOL);
        $this->verboseOutput("\tSecret\t\t: ".$credentials['secret'].PHP_EOL);
        $task->setCredentials($credentials['applicationId'], $credentials['secret']);
    }


    //
    // Destination parameter
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



    //
    // Quantity parameter
    //

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
     * @param int $quantity Formatted quantity value
     */
    private function quantityValidation($quantity)
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException(
                'The given quantity ('.$quantity.') is not positive.',
                self::ERROR_QUANTITY_NOTPOSITIVE
            );
        }

        if ($quantity > 100) {
            throw new InvalidArgumentException(
                'The given quantity ('.$quantity.') is too high (should not be greater than 100).',
                self::ERROR_QUANTITY_TOOHIGH
            );
        }
    }



    //
    // History parameter
    //

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
