<?php

namespace Count2Health\AppBundle\FatSecret;

use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\FatSecret;

/**
 * @DI\Service("fatsecret.food")
 */
class Food
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

    public function get($foodId)
    {
        $result = $this->fatSecret->doApiCall('food.get', array(
                    'food_id' => $foodId,
                    ), 'food');

return $result;
    }

}
