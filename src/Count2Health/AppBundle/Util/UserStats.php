<?php

namespace Count2Health\AppBundle\Util;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityManager;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use Count2Health\UserBundle\Entity\User;
use Count2Health\AppBundle\FatSecret\FoodEntries;
use Count2Health\AppBundle\FatSecret\Weight;
use Count2Health\AppBundle\FatSecret\ExerciseEntries;
use Count2Health\AppBundle\FatSecret;

/**
 * @DI\Service("user_stats")
 */
class UserStats
{
    private $entityManager;
    private $fatSecretWeight;
    private $fatSecretFoodEntries;
    private $fatSecret;

    /**
     * @DI\InjectParams({
     *     "entityManager" = @DI\Inject("doctrine.orm.entity_manager"),
     *     "fatSecret" = @DI\Inject("fatsecret"),
     *     "weight" = @DI\Inject("fatsecret.weight"),
     *     "foodEntries" = @DI\Inject("fatsecret.food_entries"),
     *     "exerciseEntries" = @DI\Inject("fatsecret.exercise_entries")
     * })
     */
    public function __construct(EntityManager $entityManager,
            FatSecret $fatSecret,
            Weight $weight,
            FoodEntries $foodEntries,
            ExerciseEntries $exerciseEntries)
    {
        $this->entityManager = $entityManager;
        $this->fatSecret = $fatSecret;
        $this->fatSecretWeight = $weight;
        $this->fatSecretFoodEntries = $foodEntries;
        $this->fatSecretExerciseEntries = $exerciseEntries;
    }

    public function getWeightLossPerWeek(User $user)
    {
        $tz = $user->getDateTimeZone();
        $today = new \DateTime('today', $tz);

        $weights = $this->fatSecretWeight
            ->getEntries($today, $user, 31, true);
        $numWeights = count($weights);
        $weights = array_reverse($weights);

        if ($numWeights < 2) {
            return;
        }

        $startWeight = $weights[0];
        $endWeight = $weights[$numWeights - 1];

        $startTrend = $this->fatSecretWeight
    ->calculateTrend($this->fatSecret->dateIntToDateTime(
                $startWeight->date_int, $user),
            $user);
        $endTrend = $this->fatSecretWeight
    ->calculateTrend($this->fatSecret->dateIntToDateTime(
                $endWeight->date_int, $user),
            $user);

        $rate = floatval($startTrend->toUnit('kg')
        - $endTrend->toUnit('kg'))
    / ($numWeights - 1) * 7;

        return new Mass($rate, 'kg');
    }

    public function getLastWeekWeightLoss(User $user)
    {
        $tz = $user->getDateTimeZone();
        $today = new \DateTime('today', $tz);

        $weights = $this->fatSecretWeight
            ->getEntries($today, $user, 8, true);
        $numWeights = count($weights);
        $weights = array_reverse($weights);

        if ($numWeights < 2) {
            return;
        }

        $startWeight = $weights[0];
        $endWeight = $weights[$numWeights - 1];
        $startTrend = $this->fatSecretWeight
    ->calculateTrend($this->fatSecret->dateIntToDateTime(
                $startWeight->date_int, $user),
            $user);
        $endTrend = $this->fatSecretWeight
    ->calculateTrend($this->fatSecret->dateIntToDateTime(
                $endWeight->date_int, $user),
            $user);

        return new Mass(($startTrend->toUnit('kg') - $endTrend->toUnit('kg')),
                'kg');
    }

    public function getLastMonthWeightLoss(User $user)
    {
        $tz = $user->getDateTimeZone();
        $today = new \DateTime('today', $tz);

        $weights = $this->fatSecretWeight
            ->getEntries($today, $user, 31, true);
        $numWeights = count($weights);
        $weights = array_reverse($weights);

        if ($numWeights < 2) {
            return;
        }

        $startWeight = $weights[0];
        $endWeight = $weights[$numWeights - 1];
        $startTrend = $this->fatSecretWeight
    ->calculateTrend($this->fatSecret->dateIntToDateTime(
                $startWeight->date_int, $user),
            $user);
        $endTrend = $this->fatSecretWeight
    ->calculateTrend($this->fatSecret->dateIntToDateTime(
                $endWeight->date_int, $user),
            $user);

        return new Mass(($startTrend->toUnit('kg') - $endTrend->toUnit('kg')),
                'kg');
    }

    public function getWeightLostThisMonth(\DateTime $date, User $user)
    {
        $tz = $user->getDateTimeZone();

        $entries = $this->fatSecretWeight
            ->getMonth($date, $user, 31, true);

        $weights = array();

        foreach ($entries->day as $day) {
            $weights[] = $day;
        }

        $numWeights = count($weights);

        if ($numWeights < 2) {
            return;
        }

        usort($weights, function ($a, $b) use ($user) {
                    if ((int) $a->date_int < (int) $b->date_int) {
                        return -1;
                    } elseif ((int) $a->date_int > (int) $b->date_int) {
                        return 1;
                    } else {
                        return 0;
                    }
                    });

        $startWeight = $weights[0];
        $endWeight = $weights[$numWeights - 1];
        $startTrend = $this->fatSecretWeight
    ->calculateTrend($this->fatSecret->dateIntToDateTime(
                $startWeight->date_int, $user),
            $user);
        $endTrend = $this->fatSecretWeight
    ->calculateTrend($this->fatSecret->dateIntToDateTime(
                $endWeight->date_int, $user),
            $user);

        return new Mass(($startTrend->toUnit('kg') - $endTrend->toUnit('kg')),
                'kg');
    }

    public function getDailyCalorieDeficit(\DateTime $date, User $user)
    {
        $weights = $this->fatSecretWeight
            ->getEntries($date, $user, 31, true);
        $numWeights = count($weights);
        $weights = array_reverse($weights);

        if ($numWeights < 2) {
            return;
        }

        $startWeight = $this->fatSecretWeight
            ->calculateTrend($this->fatSecret
                    ->dateIntToDateTime($weights[0]->date_int, $user),
                    $user);
        $endWeight = $this->fatSecretWeight
            ->calculateTrend($this->fatSecret
                    ->dateIntToDateTime($weights[$numWeights - 1]->date_int,
                        $user),
                    $user);

        $perDay = $startWeight->toUnit('lb') - $endWeight->toUnit('lb');
        $perDay /= floatval($numWeights - 1);

        return round($perDay * 3500);
    }

    public function getDailyCalorieDeficitThisMonth(\DateTime $date, User $user)
    {
        $entries = $this->fatSecretWeight
    ->getMonth($date, $user);

        $weights = array();

        foreach ($entries->day as $day) {
            $weights[] = $day;
        }

        $numWeights = count($weights);

        if ($numWeights < 2) {
            return;
        }

        usort($weights, function ($a, $b) use ($user) {
                    if ((int) $a->date_int < (int) $b->date_int) {
                        return -1;
                    } elseif ((int) $a->date_int > (int) $b->date_int) {
                        return 1;
                    } else {
                        return 0;
                    }
                    });

        $startDate = $this->fatSecret
->dateIntToDateTime($weights[0]->date_int, $user);
        $endDate = $this->fatSecret
->dateIntToDateTime($weights[$numWeights - 1]->date_int, $user);

        $startWeight = $this->fatSecretWeight
->calculateTrend($startDate, $user);
        $endWeight = $this->fatSecretWeight
->calculateTrend($endDate, $user);

        $perDay = $startWeight->toUnit('lb') - $endWeight->toUnit('lb');
        $perDay /= floatval($numWeights - 1);

        return $perDay * 3500;
    }

    public function getWeightLostPerWeekThisMonth(\DateTime $date, User $user)
    {
        $calories = $this->getDailyCalorieDeficitThisMonth($date, $user);

        if (null == $calories) {
            return;
        }

        $weight = $calories / 3500.0 * 7;

        return new Mass($weight, 'lb');
    }

    public function getCaloriesConsumedPerDay(\DateTime $date, User $user)
    {
        $entries = $this->fatSecretFoodEntries
->getEntries($date, $user, 30);

        if (empty($entries)) {
            return;
        }

        $calories = 0;
        $num = 0;

        foreach ($entries as $day) {
            if (intval($day->calories) > 0) {
                $num++;
                $calories += intval($day->calories);
            }
        }

        if ($num == 0) {
            return 0;
        }

        return round(floatval($calories) / $num);
    }

    public function getCaloriesConsumedPerDayThisMonth(\DateTime $date,
            User $user)
    {
        $entries = $this->fatSecretFoodEntries
->getMonth($date, $user);

        if (empty($entries->day)) {
            return;
        }

        $calories = 0;
        $num = 0;

        foreach ($entries->day as $day) {
            if (intval($day->calories) > 0) {
                $num++;
                $calories += intval($day->calories);
            }
        }

        if ($num == 0) {
            return 0;
        }

        return round(floatval($calories) / $num);
    }

    public function getBMR(\DateTime $date, User $user)
    {
        $entries = $this->fatSecretWeight
->getEntries($date, $user, 7, true);

        if (count($entries) > 0) {
            $weight = $this->fatSecretWeight
->calculateTrend($this->fatSecret
->dateIntToDateTime($entries[0]->date_int, $user),
$user);
        } else {
            $weight = $user->getPersonalDetails()->getStartWeight();

            return $this->calculateBMR($weight, $user);
        }

        $fudgeFactor = $this->getFudgeFactor($date, $user);

        if (1.0 == $fudgeFactor) {
            return $this->calculateBMR($weight, $user);
        } else {
            return $weight->toUnit('kg') * 24 * $fudgeFactor;
        }
    }

    private function calculateBMR(Mass $weight, User $user)
    {
        $bmr = 10 * $weight->toUnit('kg');
        $bmr += 6.25 * $user->getPersonalDetails()->getHeight()->toUnit('cm');

    // Get years since birth, i.e., age
    $today = new \DateTime();
        $age = $today->diff($user->getPersonalDetails()->getBirthDate());
        $bmr -= 4.92 * $age->y;

        if ('male' == $user->getPersonalDetails()->getGender()) {
            $bmr += 5;
        } elseif ('female' == $user->getPersonalDetails()->getGender()) {
            $bmr -= 161;
        }

        return $bmr;
    }

    public function getEstimatedTDEE(\DateTime $date, User $user)
    {
        $tdee = $this->getBMR($date, $user);

        switch ($user->getPersonalDetails()->getActivityLevel()) {
        case 's':
            $tdee *= 1.2;
            break;

        case 'l':
            $tdee *= 1.375;
            break;

        case 'm':
            $tdee *= 1.55;
            break;

        case 'v':
            $tdee *= 1.725;
            break;

        case 'e':
            $tdee *= 1.9;
            break;
    }

        return $tdee;
    }

    public function getTDEE(\DateTime $date, User $user,
            $fudgeFactor = null)
    {
        // What did we burn today?
            $burnedToday = $this->fatSecretExerciseEntries
                ->getTotalCalories($date, $user);

        if (null == $fudgeFactor) {
            $fudgeFactor = $this->getFudgeFactor($date, $user);
        }

        if (1.0 == $fudgeFactor) {
            return $this->getCalculatedTDEE($date, $user);
        }

        return round($burnedToday * $fudgeFactor);
    }

    public function getFudgeFactor(\DateTime $date, User $user)
    {
        $expectedTdee = $this->getCalculatedTDEE($date, $user);

            // Get last 14 days of exercise entries
            $entries = $this->fatSecretExerciseEntries
                ->getEntries($date, $user, 14, false);

        if (empty($entries)) {
            return 1.0;
        }

        // Get total calories
        $calories = 0;

        foreach ($entries as $entry) {
            $calories += intval($entry->calories);
        }

        $averageBurn = round(floatval($calories) / count($entries));

// Now calculate average fudge factor between expected TDEE
        // and average logged burn
return floatval($expectedTdee) / $averageBurn;
    }

    private function getCalculatedTDEE(\DateTime $date, User $user)
    {
        $caloriesConsumedPerDay = $this->getCaloriesConsumedPerDay($date, $user);

        if (0 == $caloriesConsumedPerDay) {
            // Not enough data to calculate the TDEE
return $this->getEstimatedTDEE($date, $user);
        }

        return $caloriesConsumedPerDay + $this->getDailyCalorieDeficit($date, $user);
    }

    public function getRDI(\DateTime $date, User $user)
    {
        $tdee = $this->getTDEE($date, $user);

        $targetDeficit = $this->getTargetDeficit($date, $user);

        $foodDiaryEntries = $this->fatSecretFoodEntries
    ->getMonth($date, $user, 16, false);
        $numEntries = count($foodDiaryEntries->day);

        if ($numEntries < 1) {
            return round($tdee - $targetDeficit);
        }

        $type = $user->getHealthPlan()->getType();
        $fudgeFactor = $this->getFudgeFactor($date, $user);
        $deficit = 0;
        $mostRecentDate = null;

        foreach ($foodDiaryEntries->day as $entry) {
            $calories = intval($entry->calories);
            if (0 == $calories) {
                $numEntries--;
                continue;
            }

            $thisDate = $this->fatSecret
->dateIntToDateTime($entry->date_int, $user);

            if ($thisDate >= $date) {
                $numEntries--;
                continue;
            }

            $thisTdee = $this->getTDEE($thisDate, $user, $fudgeFactor);

            $deficit += ($thisTdee - $calories);
            if (null == $mostRecentDate || $thisDate > $mostRecentDate) {
                $mostRecentDate = $thisDate;
            }
        }

        if ($numEntries <= 0) {
            return round($tdee - $targetDeficit);
        }

        $endOfMonth = clone $date;
        $endOfMonth->modify('last day of this month');
        $daysLeft = $endOfMonth->diff($mostRecentDate);
        $daysInMonth = $endOfMonth->format('t');

        $deficitToday = $targetDeficit * $daysInMonth;
        $deficitToday -= $deficit;
        $deficitToday /= $daysLeft->days;

        $rdi = $tdee - $deficitToday;

        return round($rdi);
    }

    /**
     * Get the target deficit.
     *
     * The target deficit takes into account the user's desired deficit, but
     * also the variation from that deficit over all previous months.
     *
     * @param DateTime $date The date for which to obtain the deficit
     * @param User     $user The user for whom to obtain the deficit
     *
     * @return int The target deficit for the given date
     */
    private function getTargetDeficit(\DateTime $date, User $user)
    {
        $targetDeficit = $user->getHealthPlan()->getTargetCalorieDeficit();

        // Adjust the deficit to average out all previous months
        $startDate = $user->getPersonalDetails()->getStartDate();
        $startWeight = $user->getPersonalDetails()->getStartWeight();
        $endDate = clone $date;
        $endDate->modify('last day of last month');

        if ($endDate < $startDate) {
            // This is the first month
            return $targetDeficit;
        }

        $endWeight = $this->fatSecretWeight
            ->calculateTrend($endDate, $user);
        $lastDay = clone $date;
        $lastDay->modify('last day of 2 months');
        $idealEndDate = $this->getIdealEndDate($user);
        if ($lastDay > $idealEndDate) {
            // Weight loss should be complete by this date
            $lastDay = $idealEndDate;
        }

        $days = $endDate->diff($startDate);
        $totalDays = $lastDay->diff($startDate);
        $monthLength = $lastDay->diff($endDate);

        $expectedDeltaWeight = $targetDeficit / 3500.0 * $totalDays->days;
        $deltaWeight = $startWeight->toUnit('lb') - $endWeight->toUnit('lb');

        $weightLeft = $expectedDeltaWeight - $deltaWeight;

        return intval(round($weightLeft / floatval($monthLength->days) * 3500));
    }

    /**
     * Calculates the ideal end date for the given user.
     *
     * The ideal end date is the date the user would reach their goal
     * weight, if the target calorie deficit would be followed every day to
     * the end.
     *
     * @param User $user The user whose ideal end date should be
     *                   calculated
     *
     * @return DateTime The ideal end date
     */
    private function getIdealEndDate(User $user)
    {
        $startDate = $user->getPersonalDetails()->getStartDate();
        $startWeight = $user->getPersonalDetails()->getStartWeight();
        $goalWeight = $user->getHealthPlan()->getGoalWeight();
        $targetDeficit = $user->getHealthPlan()->getTargetCalorieDeficit();

        $weightDelta = $startWeight->toUnit('lb') - $goalWeight->toUnit('lb');

        $days = floor($weightDelta / floatval($targetDeficit) * 3500);

        $idealEndDate = clone $startDate;
        $idealEndDate->add(new \DateInterval("P{$days}D"));

        return $idealEndDate;
    }
}
