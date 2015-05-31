<?php

namespace Count2Health\AppBundle\FatSecret;

use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\FatSecret;
use Count2Health\UserBundle\Entity\User;

/**
 * @DI\Service("fatsecret.exercise_entry")
 */
class ExerciseEntry
{
    private $fatSecret;

    /**
     * @DI\InjectParams({
     *     "fatSecret" = @DI\Inject("fatsecret")
     * })
     */
    public function __construct(FatSecret $fatSecret)
    {
        $this->fatSecret = $fatSecret;
    }

    public function edit(\DateTime $date, $to, $toName, $from, $fromName, $minutes, $calories, User $user)
    {
        $arguments = array();
        $arguments['date'] = $this->fatSecret->dateTimeToDateInt($date);
        $arguments['shift_to_id'] = $to;
        $arguments['shift_to_name'] = $toName;
        $arguments['shift_from_id'] = $from;
        $arguments['shift_from_name'] = $fromName;
        $arguments['minutes'] = $minutes;
        $arguments['kcal'] = round($calories);

        $result = $this->fatSecret->doApiCall('exercise_entry.edit', $arguments, 'exercise', $user);

        return $result;
    }
}
