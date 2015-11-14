<?php namespace Tests;

use Exception;
use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Simondubois\UnsplashDownloader\Command;
use Simondubois\UnsplashDownloader\Task;
use Simondubois\UnsplashDownloader\Validate;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CommandTest extends PHPUnit_Framework_TestCase
{

    /**
     * Mock Validate class
     * @param  array $options Options to be validated
     * @return object Mocked validate
     */
    private function mockValidate($options) {
        $validate = $this->getMock('Simondubois\UnsplashDownloader\Validate', array_keys($options));

        unset($options['featured']);

        foreach ($options as $key => $value) {
            $validate->expects($this->once())
                ->method($key)
                ->with($this->identicalTo($value))
                ->willReturn(is_numeric($value) ? intval($value) : $value);
        }

        return $validate;
    }

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
    public function testCategoriesExecute() {
        // Mock task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['categories', 'download']);
        $task->expects($this->once())->method('categories');
        $task->expects($this->never())->method('download');

        // Mock command
        $command = $this->getMock(
            'Simondubois\UnsplashDownloader\Command',
            ['task', 'loadApiCredentials', 'parameters']
        );
        $command->expects($this->once())->method('task')->willReturn($task);
        $command->expects($this->once())->method('loadApiCredentials')
            ->with($this->equalTo(new Validate()), $this->identicalTo($task));
        $command->expects($this->never())->method('parameters');

        // Execute command
        $input = new ArrayInput(['--categories' => true], $command->getDefinition());
        $output = new BufferedOutput();
        $command->execute($input, $output);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::execute()
     */
    public function testDownloadExecute() {
        // Mock task
        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['categories', 'download']);
        $task->expects($this->never())->method('categories');
        $task->expects($this->once())->method('download');

        // Mock command
        $command = $this->getMock(
            'Simondubois\UnsplashDownloader\Command',
            ['task', 'loadApiCredentials', 'parameters']
        );
        $command->expects($this->once())->method('task')->willReturn($task);
        $command->expects($this->once())->method('loadApiCredentials')
            ->with($this->equalTo(new Validate()), $this->identicalTo($task));
        $command->expects($this->once())->method('parameters')
            ->with($this->equalTo(new Validate()), $this->identicalTo($task), $this->anything());

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

        // Assert attribute assignation (default values)
        $options = [
            'destination' => getcwd(),
            'quantity' => '10',
            'history' => null,
            'featured' => false,
            'category' => '123',
        ];

        // Instantiate task (default values)
        $task = $this->getMock(
            'Simondubois\UnsplashDownloader\Task',
            ['setDestination', 'setQuantity', 'setHistory', 'setFeatured', 'setCategory']
        );
        $task->expects($this->once())->method('setDestination')->with($this->identicalTo($options['destination']));
        $task->expects($this->once())->method('setQuantity')->with($this->identicalTo(intval($options['quantity'])));
        $task->expects($this->once())->method('setHistory')->with($this->identicalTo($options['history']));
        $task->expects($this->once())->method('setFeatured')->with($this->identicalTo($options['featured']));
        $task->expects($this->once())->method('setCategory')->with($this->identicalTo(intval($options['category'])));
        $command->parameters($this->mockValidate($options), $task, $options);

        // Assert output content (default values)
        $output = $command->output->fetch();
        $this->assertContains(getcwd(), $output);
        $this->assertContains($options['quantity'], $output);
        $this->assertContains('Do not use history.', $output);
        $this->assertContains('featured and not featured', $output);

        // Assert attribute assignation (custom values)
        $options = [
            'destination' => $destination,
            'quantity' => '100',
            'history' => $history,
            'featured' => true,
            'category' => null,
        ];

        // Instantiate task (custom values)
        $task = $this->getMock(
            'Simondubois\UnsplashDownloader\Task',
            ['setDestination', 'setQuantity', 'setHistory', 'setFeatured', 'setCategory']
        );
        $task->expects($this->once())->method('setDestination')->with($this->identicalTo($options['destination']));
        $task->expects($this->once())->method('setQuantity')->with($this->identicalTo(intval($options['quantity'])));
        $task->expects($this->once())->method('setHistory')->with($this->identicalTo($options['history']));
        $task->expects($this->once())->method('setFeatured')->with($this->identicalTo($options['featured']));
        $task->expects($this->once())->method('setCategory')->with($this->identicalTo($options['category']));
        $command->parameters($this->mockValidate($options), $task, $options);

        // Assert output content (custom values)
        $output = $command->output->fetch();
        $this->assertContains($options['destination'], $output);
        $this->assertContains($options['quantity'], $output);
        $this->assertContains($options['history'], $output);
        $this->assertContains('only featured', $output);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::loadApiCredentials()
     */
    public function testNoCredentialsApiCredentials() {
        // Instantiate command
        $command = new Command();
        $command->output = new BufferedOutput();
        $command->apiCrendentialsPath = vfsStream::setup('test')->url().'/unsplash.ini';

        $validate = $this->getMock('Simondubois\UnsplashDownloader\Validate', ['apiCredentials']);
        $validate->expects($this->once())->method('apiCredentials')
            ->with($this->identicalTo(false), $this->identicalTo($command->apiCrendentialsPath))
            ->will($this->throwException(new InvalidArgumentException('', Validate::ERROR_NO_CREDENTIALS)));

        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['setCredentials']);
        $task->expects($this->never())->method('setCredentials');

        $exceptionCode = null;
        try {
            $command->loadApiCredentials($validate, $task);
        } catch (Exception $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Validate::ERROR_NO_CREDENTIALS, $exceptionCode);
    }

    /**
     * Test Simondubois\UnsplashDownloader\Command::loadApiCredentials()
     */
    public function testIncorrectCredentialsApiCredentials() {
        $command = new Command();
        $command->output = new BufferedOutput();
        $command->apiCrendentialsPath = vfsStream::setup('test')->url().'/unsplash.ini';
        touch($command->apiCrendentialsPath);

        $validate = $this->getMock('Simondubois\UnsplashDownloader\Validate', ['apiCredentials']);
        $validate->expects($this->once())->method('apiCredentials')
            ->with($this->identicalTo([]), $this->identicalTo($command->apiCrendentialsPath))
            ->will($this->throwException(new InvalidArgumentException('', Validate::ERROR_INCORRECT_CREDENTIALS)));

        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['setCredentials']);
        $task->expects($this->never())->method('setCredentials');

        $exceptionCode = null;
        try {
            $command->loadApiCredentials($validate, $task);
        } catch (Exception $exception) {
            $exceptionCode = $exception->getCode();
        }
        $this->assertEquals(Validate::ERROR_INCORRECT_CREDENTIALS, $exceptionCode);
    }


    /**
     * Test Simondubois\UnsplashDownloader\Command::loadApiCredentials()
     */
    public function testSuccessfulApiCredentials() {
        $command = new Command();
        $command->output = new BufferedOutput();
        $command->apiCrendentialsPath = vfsStream::setup('test')->url().'/unsplash.ini';
        $credentialsArray = ['applicationId' => 'your-application-id', 'secret' => 'your-secret'];
        $credentialsString = 'applicationId = "your-application-id"'.PHP_EOL.'secret = "your-secret"'.PHP_EOL;
        file_put_contents($command->apiCrendentialsPath, $credentialsString);

        $validate = $this->getMock('Simondubois\UnsplashDownloader\Validate', ['apiCredentials']);
        $validate->expects($this->once())->method('apiCredentials')
            ->with($this->identicalTo($credentialsArray), $this->identicalTo($command->apiCrendentialsPath))
            ->willReturn($credentialsArray);

        $task = $this->getMock('Simondubois\UnsplashDownloader\Task', ['setCredentials']);
        $task->expects($this->once())
            ->method('setCredentials')
            ->with($credentialsArray['applicationId'], $credentialsArray['secret']);

        $command->loadApiCredentials($validate, $task);
    }

}
