<?php

namespace Count2Health\AppBundle\Util;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Criteria;
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
$endWeight = $weights[$numWeights-1];

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
        $endWeight = $weights[$numWeights-1];
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
        $endWeight = $weights[$numWeights-1];
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
                    ->dateIntToDateTime($weights[$numWeights-1]->date_int,
                        $user),
                    $user);

        $perDay = $startWeight->toUnit('lb') - $endWeight->toUnit('lb');
        $perDay /= floatval($numWeights - 1);

        return round($perDay * 3500);
    }

    public function getCaloriesConsumedPerDay(\DateTime $date, User $user)
    {
        $entries = $this->fatSecretFoodEntries
->getEntries($date, $user, 30);

        if (!empty($entries)) {
            $calories = 0;
            $num = 0;

            foreach ($entries as $day)
            {
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
        }
        else {
            $weight = $user->getPersonalDetails()->getStartWeight();
        }

    $bmr = 10 * $weight->toUnit('kg');
    $bmr += 6.25 * $user->getPersonalDetails()->getHeight()->toUnit('cm');

    // Get years since birth, i.e., age
    $today = new \DateTime();
    $age = $today->diff($user->getPersonalDetails()->getBirthDate());
    $bmr -= 4.92 * $age->y;

    if ('male' == $user->getPersonalDetails()->getGender()) {
        $bmr += 5;
    }
    elseif ('female' == $user->getPersonalDetails()->getGender()) {
        $bmr -= 161;
    }

    return $bmr;
    }

public function getEstimatedTDEE(\DateTime $date, User $user)
{
    $tdee = $this->getBMR($date, $user);

    switch ($user->getPersonalDetails()->getActivityLevel())
    {
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

        foreach ($entries as $entry)
        {
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

        $targetDeficit = $user->getHealthPlan()->getTargetCalorieDeficit();

$foodDiaryEntries = $this->fatSecretFoodEntries
    ->getEntries($date, $user, 16, false);
$numEntries = count($foodDiaryEntries);

if ($numEntries < 2) {
return $tdee - $targetDeficit;
}

// We purposely unset the last element.
// If there are fewer than 15 days, the first (last) entry will be garbage,
// because it can't get average calories eaten per day.
// If it is 16 days, we don't need the 16th day anyway.
unset($foodDiaryEntries[$numEntries-1]);
$numEntries--;

        $type = $user->getHealthPlan()->getType();
        $fudgeFactor = $this->getFudgeFactor($date, $user);
$deficit = 0;

foreach ($foodDiaryEntries as $entry)
{
    $calories = intval($entry->calories);
if (0 == $calories) {
$numEntries--;
continue;
}

$thisDate = $this->fatSecret
->dateIntToDateTime($entry->date_int, $user);
$thisTdee = $this->getTDEE($thisDate, $user, $fudgeFactor);

$deficit += ($thisTdee - $calories);
}

$deficitToday = $targetDeficit * ($numEntries + 15);
$deficitToday -= $deficit;
$deficitToday /= 15.0;

$rdi = $tdee - $deficitToday;

return round($rdi);
    }

}
