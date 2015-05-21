<?php

namespace Count2Health\AppBundle\Tests\Util;

use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use PhpUnitsOfMeasure\PhysicalQuantity\Length;
use Count2Health\AppBundle\Util\BMICalculator;

class BMICalculatorTest extends \PHPUnit_Framework_TestCase
{

    public function testCalculateBMI()
    {
        $user = $this->getMockBuilder('Count2Health\UserBundle\Entity\User')
            ->getMock();

        $setting = $this->getMockBuilder(
                'Count2Health\AppBundle\Entity\Setting')
            ->getMock();

        $setting
            ->expects($this->once())
            ->method('getHeight')
            ->will($this->returnValue(new Length(72, 'in')))
            ;

        $user
            ->expects($this->exactly(2))
            ->method('getSetting')
            ->will($this->returnValue($setting))
            ;

        $weight = new Mass(160, 'lb');

        $calculator = new BMICalculator;
        $bmi = $calculator->calculateBMI($weight, $user);

        $this->assertEquals(21.700, $bmi, '', 0.001);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCalculateBMIWithoutSetting()
    {
        $user = $this->getMockBuilder('Count2Health\UserBundle\Entity\User')
            ->getMock();

        $user
            ->expects($this->once())
            ->method('getSetting')
            ;

        $weight = new Mass(160, 'lb');

        $calculator = new BMICalculator;
        $bmi = $calculator->calculateBMI($weight, $user);
    }

    public function testCalculateWeight()
    {
        $user = $this->getMockBuilder('Count2Health\UserBundle\Entity\User')
            ->getMock();

        $setting = $this->getMockBuilder(
                'Count2Health\AppBundle\Entity\Setting')
            ->getMock();

        $setting
            ->expects($this->once())
            ->method('getHeight')
            ->will($this->returnValue(new Length(72, 'in')))
            ;

        $user
            ->expects($this->exactly(2))
            ->method('getSetting')
            ->will($this->returnValue($setting))
            ;

        $bmi = 21.7;

        $calculator = new BMICalculator;
        $weight = $calculator->calculateWeight($bmi, $user);

        $this->assertEquals(72.576, $weight->toNativeUnit(), '', 0.001);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCalculateWeightWithoutSetting()
    {
        $user = $this->getMockBuilder('Count2Health\UserBundle\Entity\User')
            ->getMock();

        $user
            ->expects($this->once())
            ->method('getSetting')
            ;

        $bmi = 21.7;

        $calculator = new BMICalculator;
        $weight = $calculator->calculateWeight($bmi, $user);
    }

}