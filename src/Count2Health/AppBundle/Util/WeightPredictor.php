<?php

namespace Count2Health\AppBundle\Util;

use JMS\DiExtraBundle\Annotation as DI;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use Count2Health\UserBundle\Entity\User;
use Count2Health\AppBundle\FatSecret\Weight;
use Count2Health\AppBundle\Exception\InvalidWeightException;
use Count2Health\AppBundle\Exception\InvalidDateException;

/**
 * @DI\Service("weight_predictor")
 */
class WeightPredictor
{

    private $stats;

    /**
     * @DI\InjectParams({
     *     "stats" = @DI\Inject("user_stats"),
     *     "weight" = @DI\Inject("fatsecret.weight")
     * })
     */
    public function __construct(UserStats $stats, Weight $weight)
    {
        $this->stats = $stats;
        $this->weight = $weight;
    }

    public function predictDate(Mass $weight, User $user)
    {
        $date = new \DateTime('today',
                new \DateTimeZone($user->getSetting()->getTimeZone()));

        $weightPerDay = $this->stats
            ->getDailyCalorieDeficit($date, $user) / 3500.0;

        $trend = $this->weight
            ->calculateTrend($date, $user);

        $days = round(($trend->toUnit('lb') - $weight->toUnit('lb')) / $weightPerDay);

        if ($days < 0) {
            if ($weight < $trend) {
                $message = "Weight is less than current weight, but calorie " .
                    "deficit is negative.";
            }
            else {
                $message = "Weight is greater than current weight, but calorie " .
                    "deficit is positive.";
            }
            throw new InvalidWeightException($message);
        }

        $goalDate = clone $date;
        $goalDate->add(new \DateInterval("P{$days}D"));

        return $goalDate;
    }

    public function predictWeight(\DateTime $date, User $user)
    {
        $today = new \DateTime('today',
                new \DateTimeZone($user->getSetting()->getTimeZone()));

        if ($date <= $today) {
            // Cannot pass a date in the past
            throw new InvalidDateException("The given date must be in the " .
                    "future.");
        }

        $weightPerDay = $this->stats
            ->getDailyCalorieDeficit($today, $user) / 3500.0;

        $trend = $this->weight
            ->calculateTrend($today, $user);

        $interval = $date->diff($today);
        $days = $interval->days;

        $weightDelta = $days * $weightPerDay;

        return new Mass($trend->toUnit('lb') - $weightDelta, 'lb');
    }

}
