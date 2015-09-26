<?php namespace Tests\Command;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use Symfony\Component\Console\Output\OutputInterface;
use Simondubois\UnsplashDownloader\Command\Download;
use Symfony\Component\Console\Output\BufferedOutput;

class ValidDownloadTest extends AbstractDownloadTest
{
    /**
     * @dataProvider validParameterProvider
     */
    public function testValidParameters($destination, $quantity, $history) {
        if (strstr($history, 'existing') !== false) {
            touch($history);
        }

        $commandTester = $this->commandTester();
        $parameters    = $this->parameters($destination, $quantity, $history, true);

        $commandTester->execute($parameters);
        $this->assertEquals(0, $commandTester->getStatusCode());

        if (is_string($history)) {
            $this->assertCount($quantity + 3, scandir($destination));
        } else {
            $this->assertCount($quantity + 2, scandir($destination));
        }

        if (is_string($history)) {
            $this->assertFileExists($history);
        }
    }

    public function testVerboseOutput() {
        $command = new Download();

        $output          = new BufferedOutput();
        $command->output = $output;

        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $command->verboseOutput('test');
        $this->assertEquals("test\n", $output->fetch());

        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $command->verboseOutput('test', false);
        $this->assertEquals('test', $output->fetch());

        $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $command->verboseOutput('test', false);
        $this->assertEquals('', $output->fetch());
    }
}