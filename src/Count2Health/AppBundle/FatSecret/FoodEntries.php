<?php

namespace Count2Health\AppBundle\FatSecret;

use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\FatSecret;
use Count2Health\UserBundle\Entity\User;

/**
 * @DI\Service("fatsecret.food_entries")
 */
class FoodEntries extends FatSecretEntries
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

    public function get($param, $user)
    {
        $arguments = array();

        if ($param instanceof \DateTime) {
            $arguments['date'] = $this->fatSecret->dateTimeToDateInt($param);
        }
        else {
            $arguments['food_entry_id'] = $param;
        }

        $entry = $this->fatSecret->doApiCall('food_entries.get', $arguments, 'food', $user);

            return $entry;
    }

    public function getMonth(\DateTime $date, User $user)
    {
        $date = $this->fatSecret->dateTimeToDateInt($date);

        $response = $this->fatSecret->doApiCall('food_entries.get_month',
                array(
                    'date' => $date,
                    ),
'food',
                $user);

        return $response;
    }

}
