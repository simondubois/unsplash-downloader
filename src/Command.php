<?php namespace Simondubois\UnsplashDownloader;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to check parameters validity and call a download task. Steps are :
 *  - check option validity (destination, count, history, featured, categories, category).
 *  - load credentials (from local unsplash.ini file).
 *  - create a task (to deal with Unsplash API).
 *  - execute the task.
 */
class Command extends SymfonyCommand
{

    //
    // Attributes
    //

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
        $this->addOption('quantity', null, InputOption::VALUE_REQUIRED, 'Number of photos to download.', '10');
        $this->addOption(
            'history',
            null,
            InputOption::VALUE_REQUIRED,
            'Filename to use as download history.
                When photos are downloaded, their IDs will be stored into the file.
                Then any further download is going to ignore photos that have their ID in the history.
                Usefull to delete unwanted pictures and prevent the CLI to download them again.'
        );
        $this->addOption(
            'featured', null, InputOption::VALUE_NONE, 'Download only featured photos (incompatible with --category).'
        );
        $this->addOption('categories', null, InputOption::VALUE_NONE, 'Print out categories and quit (no download).');
        $this->addOption(
            'category',
            null,
            InputOption::VALUE_REQUIRED,
            'Only download photos for the given category ID (incompatible with the --featured).'
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

        $validate = new Validate();

        $task = $this->task();
        $this->loadApiCredentials($validate, $task);

        if ($input->getOption('categories')) {
            $task->categories();
            return;
        }

        $this->parameters($validate, $task, $input->getOptions());
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
     * Load & validate API credentials
     * @param  Validate $validate Validate instance
     * @param  Task $task Download task
     */
    public function loadApiCredentials(Validate $validate, Task $task)
    {
        $this->verboseOutput('Load credentials from unsplash.ini :'.PHP_EOL);

        $credentials = @parse_ini_file($this->apiCrendentialsPath);
        $validate->apiCredentials($credentials, $this->apiCrendentialsPath);

        $task->setCredentials($credentials['applicationId'], $credentials['secret']);
        $this->verboseOutput("\tApplication ID\t: ".$credentials['applicationId'].PHP_EOL);
        $this->verboseOutput("\tSecret\t\t: ".$credentials['secret'].PHP_EOL);
    }

    /**
     * Check & validate the parameters
     * @param  Validate $validate Validate instance
     * @param  Task $task Download task
     * @param  array $options Command options
     */
    public function parameters(Validate $validate, Task $task, $options)
    {
        $destination = $validate->destination($options['destination']);
        $task->setDestination($destination);
        $this->verboseOutput('Download photos to '.$destination.'.'.PHP_EOL);

        $quantity = $validate->quantity($options['quantity']);
        $task->setQuantity($quantity);
        $this->verboseOutput('Download the last '.$quantity.' photos.'.PHP_EOL);

        $history = $validate->history($options['history']);
        $task->setHistory($history);
        $this->verboseOutput((is_null($history) ? 'Do not use history.' : 'Use '.$history.' as history.').PHP_EOL);

        $task->setFeatured($options['featured']);
        $this->verboseOutput(($options['featured'] ?
            'Download only featured photos.' : 'Download featured and not featured photos.'
        ).PHP_EOL);

        $category = $validate->category($options['category']);
        $task->setCategory($category);
        if (is_int($category) && $options['featured'] === false) {
            $this->verboseOutput('Download only photos for category ID '.$options['category'].'.'.PHP_EOL);
        }
    }

}
