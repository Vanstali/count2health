<?php

namespace Count2Health\AppBundle\FatSecret;

use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\FatSecret;
use Count2Health\UserBundle\Entity\User;

/**
 * @DI\Service("fatsecret.food_entry")
 */
class FoodEntry
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

    public function create($foodId,
            $name,
            $servingId,
            $numberOfUnits,
            $meal,
            \DateTime $date,
            User $user)
    {
        $response = $this->fatSecret->doApiCall('food_entry.create', array(
                    'food_id' => $foodId,
                    'food_entry_name' => $name,
                    'serving_id' => $servingId,
                    'number_of_units' => $numberOfUnits,
                    'meal' => $meal,
                    'date' => $this->fatSecret->dateTimeToDateInt($date),
                    ),
'food',
                $user);

        return (int)$response;
    }

    public function delete($id, User $user)
    {
        $this->fatSecret->doApiCall('food_entry.delete', array(
                    'food_entry_id' => $id,
                    ),
'food',
                $user);
    }

public function edit($id, $name, $units, $servingId, $meal, User $user)
{
$arguments['food_entry_id'] = $id;
$arguments['food_entry_name'] = $name;
$arguments['number_of_units'] = $units;
$arguments['meal'] = $meal;

if (null != $servings) {
$arguments['serving_id'] = $servingId;
}

$this->fatSecret->doApiCall('food_entry.edit', $arguments, 'food', $user);
}

}
