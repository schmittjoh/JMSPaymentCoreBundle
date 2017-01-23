<?php

namespace JMS\Payment\CoreBundle\Tests\Command;

use JMS\Payment\CoreBundle\Command\ReencryptDataCommand;
use JMS\Payment\CoreBundle\Tests\Functional\BaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ReencryptDataCommandTest extends BaseTestCase
{
    public function setUp()
    {
        self::bootKernel();

        $application = new Application(self::$kernel);
        $application->add(new ReencryptDataCommand());

        $this->command = $application->find('jms_payment_core:reencrypt-data');

        parent::setUp();
    }

    /**
     * @runInSeparateProcess
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage foo
     */
    public function testUnsupportedSourceProvider()
    {
        $this->execute(array(
            'src'         => 'foo',
            'src-secret'  => 'foo-secret',
            'dest'        => 'defuse_php_encryption',
            'dest-secret' => 'bar-secret',
        ));
    }

    /**
     * @runInSeparateProcess
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage bar
     */
    public function testUnsupportedDestProvider()
    {
        $this->execute(array(
            'src'         => 'mcrypt',
            'src-secret'  => 'foo-secret',
            'dest'        => 'bar',
            'dest-secret' => 'bar-secret',
        ));
    }

    /**
     * @runInSeparateProcess
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The cipher "foo" is not supported.
     */
    public function testMcryptSrcCipher()
    {
        $this->execute(array(
            'src'                  => 'mcrypt',
            'src-secret'           => 'foo-secret',
            '--src-mcrypt-cipher'  => 'foo',
            'dest'                 => 'mcrypt',
            'dest-secret'          => 'bar-secret',
        ));
    }

    /**
     * @runInSeparateProcess
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The cipher "bar" is not supported.
     */
    public function testMcryptDestCipher()
    {
        $this->execute(array(
            'src'                  => 'mcrypt',
            'src-secret'           => 'foo-secret',
            'dest'                 => 'mcrypt',
            'dest-secret'          => 'bar-secret',
            '--dest-mcrypt-cipher' => 'bar',
        ));
    }

    /**
     * @runInSeparateProcess
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The mode "foo" is not supported.
     */
    public function testMcryptSrcMode()
    {
        $this->execute(array(
            'src'               => 'mcrypt',
            'src-secret'        => 'foo-secret',
            '--src-mcrypt-mode' => 'foo',
            'dest'              => 'mcrypt',
            'dest-secret'       => 'bar-secret',
        ));
    }

    /**
     * @runInSeparateProcess
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The mode "bar" is not supported.
     */
    public function testMcryptDestMode()
    {
        $this->execute(array(
            'src'                => 'mcrypt',
            'src-secret'         => 'foo-secret',
            'dest'               => 'mcrypt',
            'dest-secret'        => 'bar-secret',
            '--dest-mcrypt-mode' => 'bar',
        ));
    }

    private function execute(array $input)
    {
        $commandTester = new CommandTester($this->command);

        $commandTester->execute(array_merge(array(
            'command' => $this->command->getName(),
        ), $input));

        return $commandTester->getDisplay();
    }
}
