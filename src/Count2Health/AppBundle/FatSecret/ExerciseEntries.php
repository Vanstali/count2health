<?php

namespace Count2Health\AppBundle\FatSecret;

use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\FatSecret;
use Count2Health\UserBundle\Entity\User;

/**
 * @DI\Service("fatsecret.exercise_entries")
 */
class ExerciseEntries extends FatSecretEntries
{

    /**
     * @DI\InjectParams({
     *     "fatSecret" = @DI\Inject("fatsecret")
     * })
     */
    public function __construct(FatSecret $fatSecret)
    {
        $this->fatSecret = $fatSecret;
    }

    public function get(\DateTime $date, $user)
    {
        $arguments = array();
            $arguments['date'] = $this->fatSecret->dateTimeToDateInt($date);

        $entry = $this->fatSecret->doApiCall('exercise_entries.get', $arguments, 'exercise', $user);

            return $entry;
    }

    public function getMonth(\DateTime $date, User $user)
    {
        $date = $this->fatSecret->dateTimeToDateInt($date);

        $response = $this->fatSecret->doApiCall('exercise_entries.get_month',
                array(
                    'date' => $date,
                    ),
                'exercise',
$user);

        return $response;
    }

    public function getTotalCalories(\DateTime $date, User $user)
    {
$prevEntries = $this->getEntries($date, $user, 1, true);

if (empty($prevEntries)) {
$doCall = true;
}
else {
$d = $this->fatSecret
->dateIntToDateTime($prevEntries[0]->date_int, $user);

if ($d == $date) {
$doCall = false;
}
else {
$doCall = true;
}
}

if (true == $doCall) {
        $result = $this->get($date, $user);

        $calories = 0;

        if (!isset($result->exercise_entry)) {
                return 0;
                }

                foreach ($result->exercise_entry as $entry)
                {
                $calories += intval($entry->calories);
                }

                return $calories;
}
else {
return intval($prevEntries[0]->calories);
}
    }

public function isTemplate(\SimpleXMLElement $entries)
{
    $template = true;

    foreach ($entries->exercise_entry as $entry)
    {
        if (0 == intval($entry->is_template_value)) {
            $template = false;
        }
    }

    return $template;
}

public function commitDay(\DateTime $date, User $user)
{
    $date = $this->fatSecret->dateTimeToDateInt($date);

    $this->fatSecret->doApiCall('exercise_entries.commit_day', array(
                'date' => $date,
                ), 'exercise', $user);
}

}
