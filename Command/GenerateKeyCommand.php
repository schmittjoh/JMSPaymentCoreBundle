<?php

namespace JMS\Payment\CoreBundle\Command;

use Defuse\Crypto\Key;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateKeyCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('jms_payment_core:generate-key')
            ->setDescription('Generate an encryption key')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(Key::createNewRandomKey()->saveToAsciiSafeString());
    }
}
