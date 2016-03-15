<?php namespace Simondubois\UnsplashDownloader;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to check parameters validity and call a download task. Steps are :
 *  - check option validity (destination, count, history, featured, categories, category).
 *  - create a task (to deal with Unsplash API).
 *  - execute the task.
 */
class Command extends SymfonyCommand
{

    //
    // Constants & Attributes
    //
    const DESCRIPTION_DESTINATION = 'Directory where to download photos.';
    const DESCRIPTION_QUANTITY = 'Number of photos to download.';
    const DESCRIPTION_HISTORY = 'Filename to use as download history.
                When photos are downloaded, their IDs will be stored into the file.
                Then any further download is going to ignore photos that have their ID in the history.
                Usefull to delete unwanted pictures and prevent the CLI to download them again.';
    const DESCRIPTION_FEATURED = 'Download only featured photos (incompatible with --category).';
    const DESCRIPTION_CATEGORIES = 'Print out categories and quit (no download).';
    const DESCRIPTION_CATEGORY = 'Only download photos for the given category ID (incompatible with the --featured).';
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
        $this->addOption('destination', null, InputOption::VALUE_REQUIRED, self::DESCRIPTION_DESTINATION, getcwd());
        $this->addOption('quantity', null, InputOption::VALUE_REQUIRED, self::DESCRIPTION_QUANTITY, '10');
        $this->addOption('history', null, InputOption::VALUE_REQUIRED, self::DESCRIPTION_HISTORY);
        $this->addOption('featured', null, InputOption::VALUE_NONE, self::DESCRIPTION_FEATURED);
        $this->addOption('categories', null, InputOption::VALUE_NONE, self::DESCRIPTION_CATEGORIES);
        $this->addOption('category', null, InputOption::VALUE_REQUIRED, self::DESCRIPTION_CATEGORY);
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
     * Check & validate the parameters
     * @param  Validate $validate Validate instance
     * @param  Task $task Download task
     * @param  array $options Command options
     */
    public function parameters(Validate $validate, Task $task, $options)
    {
        $this->destinationParameter($validate, $task, $options['destination']);
        $this->quantityParameter($validate, $task, $options['quantity']);
        $this->historyParameter($validate, $task, $options['history']);
        $this->featuredParameter($task, $options['featured']);
        $this->categoryParameter($validate, $task, $options['category']);
    }

    /**
     * Check & validate the destination parameter
     * @param  Validate $validate Validate instance
     * @param  Task $task Download task
     * @param  string $option Option value
     */
    public function destinationParameter(Validate $validate, Task $task, $option)
    {
        $destination = $validate->destination($option);

        $task->setDestination($destination);

        $this->verboseOutput('Download photos to '.$destination.'.'.PHP_EOL);
    }

    /**
     * Check & validate the quantity parameter
     * @param  Validate $validate Validate instance
     * @param  Task $task Download task
     * @param  string $option Option value
     */
    public function quantityParameter(Validate $validate, Task $task, $option)
    {
        $quantity = $validate->quantity($option);

        $task->setQuantity($quantity);

        $this->verboseOutput('Download the last '.$quantity.' photos.'.PHP_EOL);
    }

    /**
     * Check & validate the history parameter
     * @param  Validate $validate Validate instance
     * @param  Task $task Download task
     * @param  string $option Option value
     */
    public function historyParameter(Validate $validate, Task $task, $option)
    {
        $history = $validate->history($option);

        $task->setHistory($history);

        if (is_null($history)) {
            $this->verboseOutput('Do not use history.'.PHP_EOL);
            return;
        }

        $this->verboseOutput('Use '.$history.' as history.'.PHP_EOL);
    }

    /**
     * Check & validate the featured parameter
     * @param  Task $task Download task
     * @param  bool $option Option value
     */
    public function featuredParameter(Task $task, $option)
    {
        $task->setFeatured($option);

        if ($option === true) {
            $this->verboseOutput('Download only featured photos.');
        }

        $this->verboseOutput('Download featured and not featured photos.');
    }

    /**
     * Check & validate the category parameter
     * @param  Validate $validate Validate instance
     * @param  Task $task Download task
     * @param  string $option Option value
     */
    public function categoryParameter(Validate $validate, Task $task, $option)
    {
        $category = $validate->category($option);

        $task->setCategory($category);

        if (is_int($category) && $task->getFeatured() !== true) {
            $this->verboseOutput('Download only photos for category ID '.$option.'.'.PHP_EOL);
        }
    }

}
