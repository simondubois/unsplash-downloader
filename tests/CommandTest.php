<?php namespace Tests;

use Exception;
use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Simondubois\UnsplashDownloader\Command;
use Simondubois\UnsplashDownloader\Task;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CommandTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test Simondubois\UnsplashDownloader\Command::verboseOutput()
     */
    public function testVerboseOutput() {
        // Instantiate command
        $command = new Command();
        $command->output = new BufferedOutput();
        $message = 'This is a message'.PHP_EOL.'split on many'.PHP_EOL.'lines';

        // Output for verbosity : normal
        $command->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $command->verboseOutput($message);
        $this->assertEmpty($command->output->fetch());
        $command->verboseOutput($message, 'info');
        $this->assertEmpty($command->output->fetch());

        // Output for verbosity : verbose
        $command->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $command->verboseOutput($message);
        $this->assertEquals($message, $command->output->fetch());
        $command->verboseOutput($message, 'info', OutputInterface::OUTPUT_RAW);
        $this->assertEquals('<info>'.$message.'</info>', $command->output->fetch());
        $command->verboseOutput($message.PHP_EOL, 'info', OutputInterface::OUTPUT_RAW);
        $this->assertEquals('<info>'.$message.'</info>'.PHP_EOL, $command->output->fetch());
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::output()
     */
    public function testOutput() {
        // Instantiate command
        $command = new Command();
        $command->output = new BufferedOutput();
        $message = 'This is a message'.PHP_EOL.'split on many'.PHP_EOL.'lines';

        // Output for verbosity : quiet
        $command->output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        $command->output($message);
        $this->assertEmpty($command->output->fetch());
        $command->output($message, 'info');
        $this->assertEmpty($command->output->fetch());

        // Output for verbosity : normal
        $command->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $command->output($message);
        $this->assertEquals($message, $command->output->fetch());
        $command->output($message, 'info', OutputInterface::OUTPUT_RAW);
        $this->assertEquals('<info>'.$message.'</info>', $command->output->fetch());
        $command->output($message.PHP_EOL, 'info', OutputInterface::OUTPUT_RAW);
        $this->assertEquals('<info>'.$message.'</info>'.PHP_EOL, $command->output->fetch());
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::execute()
     */
    public function testExecute() {
        // Mock task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['execute']);
        $task->expects($this->once())->method('execute');

        // Mock command
        $command = $this->getMock(
            'Simondubois\UnsplashDownloader\Command',
            ['task', 'parameters', 'loadApiCredentials']
        );
        $command->expects($this->once())->method('task')->willReturn($task);
        $command->expects($this->once())->method('parameters')
            ->with($this->identicalTo($task), $this->anything());
        $command->expects($this->once())->method('loadApiCredentials')->with($this->identicalTo($task));

        // Execute command
        $input = new ArrayInput([], $command->getDefinition());
        $output = new BufferedOutput();
        $command->execute($input, $output);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::execute()
     */
    public function testTask() {
        // Instantiate command
        $command = new Command();

        // Test method
        $this->assertInstanceOf('Simondubois\UnsplashDownloader\Task', $command->task());
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::parameters()
     */
    public function testParameters() {
        // Instantiate command
        $command = new Command();
        $command->output = new BufferedOutput();
        $command->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        // Instiantiate file system
        $root = vfsStream::setup('test')->url();
        $destination = $root.'/destination';
        mkdir($destination);
        $history = $root.'/history';

        // Assert attribute assignation (with history)
        $options = [
            'destination' => $destination,
            'quantity' => '10',
            'history' => $history,
            'featured' => false,
        ];

        // Instantiate task (with history)
        $task = $this->getMock(
            'Simondubois\UnsplashDownloader\Task',
            ['setDestination', 'setQuantity', 'setHistory', 'setFeatured']
        );
        $task->expects($this->once())->method('setDestination')->with($this->identicalTo($destination));
        $task->expects($this->once())->method('setQuantity')->with($this->identicalTo(10));
        $task->expects($this->once())->method('setHistory')->with($this->identicalTo($history));
        $task->expects($this->once())->method('setFeatured')->with($this->identicalTo(false));
        $command->parameters($task, $options);

        // Assert output content (with history)
        $output = $command->output->fetch();
        $this->assertContains($options['destination'], $output);
        $this->assertContains($options['quantity'], $output);
        $this->assertContains($options['history'], $output);
        $this->assertContains('featured and not featured', $output);

        // Assert attribute assignation (without history)
        $options = [
            'destination' => $destination,
            'quantity' => '10',
            'history' => null,
            'featured' => true,
        ];

        // Instantiate task (without history)
        $task = $this->getMock(
            'Simondubois\UnsplashDownloader\Task',
            ['setDestination', 'setQuantity', 'setHistory', 'setFeatured']
        );
        $task->expects($this->once())->method('setDestination')->with($this->identicalTo($destination));
        $task->expects($this->once())->method('setQuantity')->with($this->identicalTo(10));
        $task->expects($this->once())->method('setHistory')->with($this->identicalTo(null));
        $task->expects($this->once())->method('setFeatured')->with($this->identicalTo(true));

        // Assert output content (without history)
        $command->parameters($task, $options);
        $output = $command->output->fetch();
        $this->assertContains($options['destination'], $output);
        $this->assertContains($options['quantity'], $output);
        $this->assertContains('Do not use history.', $output);
        $this->assertContains('only featured', $output);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::loadApiCredentials()
     *     & Simondubois\UnsplashDownloader\Command::validApiCredentials()
     */
    public function testNoCredentialsApiCredentials() {
        // Instantiate command
        $command = new Command();
        $command->output = new BufferedOutput();
        $command->apiCrendentialsPath = vfsStream::setup('test')->url().'/unsplash.ini';

        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['setCredentials']);
        $task->expects($this->never())->method('setCredentials');
        // Assert no credentials
        $exceptionCode = null;
        try {
            $command->loadApiCredentials($task);
        } catch (Exception $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Command::ERROR_NO_CREDENTIALS, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::loadApiCredentials()
     *     & Simondubois\UnsplashDownloader\Command::validApiCredentials()
     */
    public function testIncorrectCredentialsApiCredentials() {
        // Instantiate command
        $command = new Command();
        $command->output = new BufferedOutput();
        $command->apiCrendentialsPath = vfsStream::setup('test')->url().'/unsplash.ini';
        touch($command->apiCrendentialsPath);

        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['setCredentials']);
        $task->expects($this->never())->method('setCredentials');

        // Assert incorrect credentials
        $exceptionCode = null;
        try {
            $command->loadApiCredentials($task);
        } catch (Exception $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Command::ERROR_INCORRECT_CREDENTIALS, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::loadApiCredentials()
     *     & Simondubois\UnsplashDownloader\Command::validApiCredentials()
     */
    public function testSuccessfulApiCredentials() {
        // Instantiate command
        $command = new Command();
        $command->output = new BufferedOutput();
        $command->apiCrendentialsPath = vfsStream::setup('test')->url().'/unsplash.ini';
        $credentials = 'applicationId = "your-application-id"'.PHP_EOL.'secret = "your-secret"'.PHP_EOL;
        file_put_contents($command->apiCrendentialsPath, $credentials);

        // Instantiate task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['setCredentials']);
        $task->expects($this->once())
            ->method('setCredentials')
            ->with($this->identicalTo('your-application-id'), $this->identicalTo('your-secret'));

        // Assert no credentials
        $command->loadApiCredentials($task);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::destination()
     */
    public function testFileDestination() {
        // Instantiate command
        $command = new Command();

        // Instiantiate file system
        $root = vfsStream::setup('test')->url();
        $existingFile = $root.'/existingFile';
        touch($existingFile);

        // Invalid destination : existing file
        $exceptionCode = null;
        try {
            $command->destination($existingFile);
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Command::ERROR_DESTINATION_NOTDIR, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::destination()
     */
    public function testMissingFolderDestination() {
        // Instantiate command
        $command = new Command();

        // Instiantiate file system
        $root = vfsStream::setup('test')->url();
        $missingFolder = $root.'/missingFolder';

        // Invalid destination : missing folder
        $exceptionCode = null;
        try {
            $command->destination($missingFolder);
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Command::ERROR_DESTINATION_NOTDIR, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::destination()
     */
    public function testUnwritableDestination() {
        // Instantiate command
        $command = new Command();

        // Instiantiate file system
        $root = vfsStream::setup('test')->url();
        $unwritableFolder = $root.'/unwritableFolder';
        mkdir($unwritableFolder, 0000);

        // Asert destination
        $exceptionCode = null;
        try {
            $command->destination($unwritableFolder);
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Command::ERROR_DESTINATION_UNWRITABLE, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::destination()
     */
    public function testSuccessfulDestination() {
        // Instantiate command
        $command = new Command();

        // Instiantiate file system
        $root = vfsStream::setup('test')->url();
        $existingFolder = $root.'/existingFolder';
        mkdir($existingFolder);

        // Valid destination
        $this->assertEquals($existingFolder, $command->destination($existingFolder));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::quantity()
     */
    public function testNotNumericQuantity() {
        // Instantiate command
        $command = new Command();

        // Invalid quantity : not numeric
        $exceptionCode = null;
        try {
            $command->quantity('abc');
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Command::ERROR_QUANTITY_NOTNUMERIC, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::quantity()
     */
    public function testNotPositivQuantity() {
        // Instantiate command
        $command = new Command();

        // Invalid quantity : not positive
        $exceptionCode = null;
        try {
            $command->quantity('-1');
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Command::ERROR_QUANTITY_NOTPOSITIVE, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::quantity()
     */
    public function testTooHighQuantity() {
        // Instantiate command
        $command = new Command();

        // Invalid quantity : too high
        $exceptionCode = null;
        try {
            $command->quantity('101');
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Command::ERROR_QUANTITY_TOOHIGH, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::quantity()
     */
    public function testValidQuantity() {
        // Instantiate command
        $command = new Command();

        // Valid quantities
        $this->assertEquals(1, $command->quantity('1'));
        $this->assertEquals(10, $command->quantity('10'));
        $this->assertEquals(100, $command->quantity('100'));
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::history()
     */
    public function testNotFileHistory() {
        // Instantiate command
        $command = new Command();

        // Instiantiate file system
        $root = vfsStream::setup('test')->url();
        $existingFolder = $root.'/existingFolder';
        mkdir($existingFolder);

        // Invalid history : not file
        $exceptionCode = null;
        try {
            $command->history($existingFolder);
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Command::ERROR_HISTORY_NOTFILE, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::history()
     */
    public function testUnwritableHistory() {
        // Instantiate command
        $command = new Command();

        // Instiantiate file system
        $root = vfsStream::setup('test')->url();
        $unwritableFolder = $root.'/unwritableFolder';
        mkdir($unwritableFolder, 0000);
        $unwritableFile = $unwritableFolder.'/unwritableFile';

        // Invalid history : not writable
        $exceptionCode = null;
        try {
            $command->history($unwritableFile);
        } catch (InvalidArgumentException $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Command::ERROR_HISTORY_NOTRW, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::history()
     */
    public function testValidHistory() {
        // Instantiate command
        $command = new Command();

        // Instiantiate file system
        $root = vfsStream::setup('test')->url();
        $existingFile = $root.'/existingFile';
        $missingFile = $root.'/missingFile';
        touch($existingFile);

        // Valid history
        $this->assertNull($command->history(null));
        $this->assertEquals($existingFile, $command->history($existingFile));
        $this->assertEquals($missingFile, $command->history($missingFile));
    }

}
