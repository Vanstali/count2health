<?php

namespace Count2Health\AppBundle\FatSecret;

use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\FatSecret;

/**
 * @DI\Service("fatsecret.recipes")
 */
class Recipes
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

    public function search($expression, $page = 0, $resultsPerPage = 20)
    {
        $result = $this->fatSecret->doApiCall('recipes.search', array(
                    'search_expression' => $expression,
                    'page_number' => $page,
                    'max_results' => $resultsPerPage,
                    ),
'food');

        return $result;
    }
}
