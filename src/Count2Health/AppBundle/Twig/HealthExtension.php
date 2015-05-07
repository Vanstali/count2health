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
                );
    }

    public function weightFilter(PhysicalQuantityInterface $unit, User $user, $round = 2)
    {
        return round($unit->toUnit($user->getSetting()->getWeightUnits()), $round) .
            ' ' . $user->getSetting()->getWeightUnits();
    }

    public function heightFilter(PhysicalQuantityInterface $unit, User $user)
    {
        return round($unit->toUnit($user->getSetting()->getHeightUnits()), 1) .
            ' ' . $user->getSetting()->getHeightUnits();
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
}
