<?php namespace Simondubois\UnsplashDownloader;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to check parameters validity and call a download task.
 * The steps are :
 *  - check option validity (destination, count and history).
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

    /**
     * Output instance.
     * Stored to simplify method calls and to be used in callbacks.
     * @var OutputInterface
     */
    public $output;



    //
    // Helpers
    //

    /**
     * Output text only if run with verbose attribute
     * @param  string  $message Text to output
     * @param  string|null $context Context of the message
     * @return void
     */
    public function verboseOutput($message, $context = null) {
        if ($this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            return;
        }

        if (is_string($context)) {
            $pattern = '^(.+)('.PHP_EOL.'?)$';
            $replacement = sprintf("<%s>$1</%s>$2", $context, $message, $context);
            $subject = $message;

            $message = preg_replace($pattern, $replacement, $subject);
        }

        $this->output->write($message);
    }



    /**
     * Output text only if run with verbose attribute
     * @param  string  $message Text to output
     * @param  string|null $context Context of the message
     * @return void
     */
    public function output($message, $context = null) {
        if ($this->output->getVerbosity() < OutputInterface::VERBOSITY_NORMAL) {
            return;
        }

        if (is_string($context)) {
            $message = sprintf("<%s>%s</%s>", $context, $message, $context);
        }

        $this->output->write($message);
    }



    //
    // Handle command setup
    //

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

        $task = new Task();

        $task->setNotificationCallback([$this, 'output']);

        $this->parameters($task, $input);
        $task->execute();
    }

    /**
     * Check & validate the parameters
     * @param  Task $task Download task
     * @param  InputInterface $input Command input
     * @return void
     */
    protected function parameters(Task $task, InputInterface $input)
    {
        $destination = $this->destination($input->getOption('destination'));
        $task->setDestination($destination);
        $this->verboseOutput('Download photos to '.$destination.'.'.PHP_EOL);

        $quantity = $this->quantity($input->getOption('quantity'));
        $task->setQuantity($quantity);
        $this->verboseOutput('Download the last '.$quantity.' photos.'.PHP_EOL);

        $history = $this->history($input->getOption('history'));
        $task->setHistory($history);
        if (is_string($history)) {
            $this->verboseOutput('Use '.$history.' as history.'.PHP_EOL);
        } else {
            $this->verboseOutput('Do not use history.'.PHP_EOL);
        }
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
