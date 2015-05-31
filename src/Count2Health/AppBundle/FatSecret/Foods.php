<?php

namespace Count2Health\AppBundle\FatSecret;

use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\FatSecret;
use Count2Health\UserBundle\Entity\User;

/**
 * @DI\Service("fatsecret.foods")
 */
class Foods
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
        $result = $this->fatSecret->doApiCall('foods.search', array(
                    'search_expression' => $expression,
                    'page_number' => $page,
                    'max_results' => $resultsPerPage,
                    ),
'food');

        return $result;
    }

    public function getMostEaten($meal, User $user)
    {
        return $this->fatSecret->doApiCall('foods.get_most_eaten', array(
                    'meal' => $meal,
                    ),
'food',
                $user);
    }

    public function getRecentlyEaten($meal, User $user)
    {
        return $this->fatSecret->doApiCall('foods.get_recently_eaten', array(
                    'meal' => $meal,
                    ),
'food',
                $user);
    }
}
