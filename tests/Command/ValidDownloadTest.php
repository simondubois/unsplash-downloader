<?php namespace Tests\Command;

class ValidDownloadTest extends AbstractDownloadTest
{

    public function validParameterProvider()
    {
        $destination = $this->destination();

        return [
            [$destination, $this->quantity(), null],
            [$destination, $this->quantity(), $destination.'/new_history.txt'],
            [$destination, $this->quantity(), $destination.'/existing_history.txt'],
        ];
    }

    /**
     * @dataProvider validParameterProvider
     */
    public function testValidParameters($destination, $quantity, $history) {
        if ($history === 'existing_history.txt') {
            touch($destination.'/existing_history.txt');
        }

        $commandTester = $this->commandTester();
        $parameters = $this->parameters($destination, $quantity, $history);

        $commandTester->execute($parameters);
        $this->assertEquals(0, $commandTester->getStatusCode());

        $files = scandir($destination);
        if (is_string($history)) {
            $this->assertCount($quantity + 3, $files);
        } else {
            $this->assertCount($quantity + 2, $files);
        }

        if (is_string($history)) {
            $this->assertFileExists($history);
        }
    }
}