<?php

namespace Count2Health\AppBundle\Twig;

use JMS\DiExtraBundle\Annotation as DI;
use PhpUnitsOfMeasure\PhysicalQuantityInterface;
use Count2Health\UserBundle\Entity\User;

/**
 * @DI\Service
 * @DI\Tag("twig.extension")
 */
class HealthExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
                new \Twig_SimpleFilter('weight', array($this, 'weightFilter')),
                new \Twig_SimpleFilter('height', array($this, 'heightFilter')),
                new \Twig_SimpleFilter('duration', array($this, 'durationFilter')),
                new \Twig_SimpleFilter('format_percent', array($this, 'formatPercentFilter')),
                new \Twig_SimpleFilter('tzabbr', array($this, 'getTimeZoneAbbreviationFilter')),
                );
    }

    public function weightFilter(PhysicalQuantityInterface $unit, User $user, $round = 2)
    {
        return round($unit->toUnit($user->getPersonalDetails()->getWeightUnits()), $round) .
            ' ' . $user->getPersonalDetails()->getWeightUnits();
    }

    public function heightFilter(PhysicalQuantityInterface $unit, User $user)
    {
        $heightUnits = $user->getPersonalDetails()->getHeightUnits();

        if ($heightUnits == 'inch') {
            $height = $user->getPersonalDetails()->getHeight()->toUnit('in');

            $feet = floor($height / 12);
            $inches = fmod($height, 12);

            return $feet . '\' ' .
                round($inches, 1) . '"';
        }
        elseif ($heightUnits == 'cm') {
            return round($user->getPersonalDetails()->getHeight()->toUnit('cm')) . ' cm';
        }
    }

    public function durationFilter($minutes)
    {
        $minutes = intval($minutes);

        $duration = array();

        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $duration[] = "{$hours}h";

            $minutes = fmod($minutes, 60);
        }

        if ($minutes > 0) {
            $duration[] = "{$minutes}m";
        }

        return implode(' ', $duration);
    }

    public function formatPercentFilter($percent, $decimals = 1)
    {
        return number_format(round($percent * 100, $decimals), $decimals) . '%';
    }

    public function getName()
    {
        return 'health_extension';
    }

    public function getTimeZoneAbbreviationFilter($tz)
    {
        if ($tz instanceof \DateTimeZone) {
        $timezone_id = $tz->getName();
        }
        else {
            $timezone_id = $tz;
        }

        $abb_list = \DateTimeZone::listAbbreviations();

        $abb_array = array();
        foreach ($abb_list as $abb_key => $abb_val)
        {
            foreach ($abb_val as $key => $value)
            {
                $value['abb'] = $abb_key;
                array_push($abb_array, $value);
            }
        }

        foreach ($abb_array as $key => $value)
        {
            if($value['timezone_id'] == $timezone_id) {
                return strtoupper($value['abb']);
            }
        }
    }
}
