<?php

namespace JMS\Payment\CoreBundle\Tests\Validator;

use JMS\Payment\CoreBundle\Util\Legacy;
use JMS\Payment\CoreBundle\Validator\CardScheme;
use JMS\Payment\CoreBundle\Validator\CardSchemeValidator;

class CardSchemeValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected $context;
    protected $validator;

    protected function setUp()
    {
        $this->context = Legacy::isOldPathExecutionContext()
            ? $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            : $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
        ;

        $this->context = $this->context
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->validator = new CardSchemeValidator();
        $this->validator->initialize($this->context);
    }

    protected function tearDown()
    {
        $this->context = null;
        $this->validator = null;
    }

    public function testNullIsValid()
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate(null, new CardScheme(array('schemes' => array())));
    }

    public function testEmptyStringIsValid()
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate('', new CardScheme(array('schemes' => array())));
    }

    /**
     * @dataProvider getValidNumbers
     */
    public function testValidNumbers($scheme, $number)
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate($number, new CardScheme(array('schemes' => array($scheme))));
    }

    public function getValidNumbers()
    {
        return array(
            array('VISA', '42424242424242424242'),
            array('AMEX', '378282246310005'),
            array('AMEX', '371449635398431'),
            array('AMEX', '378734493671000'),
            array('DINERS', '30569309025904'),
            array('DISCOVER', '6011111111111117'),
            array('DISCOVER', '6011000990139424'),
            array('JCB', '3530111333300000'),
            array('JCB', '3566002020360505'),
            array('MASTERCARD', '5555555555554444'),
            array('MASTERCARD', '5105105105105100'),
            array('VISA', '4111111111111111'),
            array('VISA', '4012888888881881'),
            array('VISA', '4222222222222'),
        );
    }
}
