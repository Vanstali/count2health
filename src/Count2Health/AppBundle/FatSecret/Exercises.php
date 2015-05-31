<?php

namespace Count2Health\AppBundle\FatSecret;

use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\FatSecret;

/**
 * @DI\Service("fatsecret.exercises")
 */
class Exercises
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

    public function get()
    {
        $result = $this->fatSecret->doApiCall('exercises.get', array(), 'exercise');

        return $result;
    }
}
