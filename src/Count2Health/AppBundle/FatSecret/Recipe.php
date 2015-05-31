<?php

namespace Count2Health\AppBundle\FatSecret;

use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\FatSecret;

/**
 * @DI\Service("fatsecret.recipe")
 */
class Recipe
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

    public function get($recipeId)
    {
        $result = $this->fatSecret->doApiCall('recipe.get', array(
                    'recipe_id' => $recipeId,
                    ),
'food');

        return $result;
    }
}
