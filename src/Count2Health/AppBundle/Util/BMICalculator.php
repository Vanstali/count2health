<?php

namespace Count2Health\AppBundle\Util;

use JMS\DiExtraBundle\Annotation as DI;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use Count2Health\UserBundle\Entity\User;

/**
 * @DI\Service("bmi_calculator")
 */
class BMICalculator
{
    public function calculateBMI(Mass $weight, User $user)
    {
        if (null == $user->getPersonalDetails()) {
            throw new \InvalidArgumentException('The user settings are required '.
            'to calculate BMI.');
        }

        $height = $user->getPersonalDetails()->getHeight();

        return $weight->toUnit('kg') / pow($height->toUnit('m'), 2);
    }

    public function calculateWeight($bmi, User $user)
    {
        if (null == $user->getPersonalDetails()) {
            throw new \InvalidArgumentException('The user settings are required '.
            'to calculate BMI.');
        }

        $height = $user->getPersonalDetails()->getHeight();

        return new Mass($bmi * pow($height->toUnit('m'), 2), 'kg');
    }
}
