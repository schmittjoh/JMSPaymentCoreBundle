<?php

namespace JMS\Payment\CoreBundle\Tests\Functional\TestPlugin\Plugin;

use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;

class TestPlugin extends AbstractPlugin
{
    public function processes($paymentSystemName)
    {
        return 'test_plugin' === $paymentSystemName;
    }
}