<?php

namespace JMS\Payment\CoreBundle\Command;

use Doctrine\ORM\Tools\Pagination\Paginator;
use JMS\Payment\CoreBundle\Cryptography\DefusePhpEncryptionService;
use JMS\Payment\CoreBundle\Cryptography\MCryptEncryptionService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReencryptDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('jms_payment_core:reencrypt-data')
            ->setDescription('Re-encrypt encrypted database data')
            ->addArgument(
                'src',
                InputArgument::REQUIRED,
                'The cryptography provider with which data currently in the database was encrypted'
            )
            ->addArgument(
                'src-secret',
                InputArgument::REQUIRED,
                'The current encryption key'
            )
            ->addArgument(
                'dest',
                InputArgument::REQUIRED,
                'The new cryptography provider to use for encrypting data'
            )
            ->addArgument(
                'dest-secret',
                InputArgument::REQUIRED,
                'The new encryption key'
            )
            ->addOption(
                'src-mcrypt-cipher',
                null,
                InputOption::VALUE_OPTIONAL,
                'The mcrypt cipher for the src provider',
                'rijndael-256'
            )
            ->addOption(
                'src-mcrypt-mode',
                null,
                InputOption::VALUE_OPTIONAL,
                'The mcrypt mode for the src provider',
                'ctr'
            )
            ->addOption(
                'dest-mcrypt-cipher',
                null,
                InputOption::VALUE_OPTIONAL,
                'The mcrypt cipher for the dest provider',
                'rijndael-256'
            )
            ->addOption(
                'dest-mcrypt-mode',
                null,
                InputOption::VALUE_OPTIONAL,
                'The mcrypt mode for the dest provider',
                'ctr'
            )
            ->addOption(
                'em',
                null,
                InputOption::VALUE_OPTIONAL,
                'The entity manager to use',
                'default'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $providers = $this->getProviders($input);

        $em = $this->getContainer()->get('doctrine')->getManager($input->getOption('em'));

        $query = $em->createQuery('SELECT pi from JMSPaymentCoreBundle:PaymentInstruction pi')
            ->setFirstResult(0)
            ->setMaxResults(128);

        $paginator = new Paginator($query, $fetchJoinCollection = false);

        foreach ($paginator as $pi) {
            var_dump($pi->getExtendedData());
        }
    }

    private function getProviders(InputInterface $input)
    {
        $supportedProviders = array(
            'mcrypt' => MCryptEncryptionService::class,
            'defuse_php_encryption' => DefusePhpEncryptionService::class,
        );

        foreach ([$input->getArgument('src'), $input->getArgument('dest')] as $provider) {
            if (!array_key_exists($provider, $supportedProviders)) {
                throw new \InvalidArgumentException("Unsupported cryptography provider: $provider");
            }
        }

        $providers = array();

        foreach (array('src', 'dest') as $providerType) {
            foreach (($options = $input->getOptions()) as $key => $value) {
                $options[str_replace("$providerType-", '', $key)] = $value;
            }

            foreach ($supportedProviders as $name => $class) {
                if ($name !== $input->getArgument($providerType)) {
                    continue;
                }

                switch ($input->getArgument($providerType)) {
                    case 'mcrypt':
                        $providers[$providerType] = new MCryptEncryptionService(
                            $input->getArgument("$providerType-secret"),
                            $options['mcrypt-cipher'],
                            $options['mcrypt-mode']
                        );
                        break;
                    case 'defuse_php_encryption':
                        $providers[$providerType] = new DefusePhpEncryptionService($secret);
                        break;
                }
            }
        }

        return $providers;
    }
}
