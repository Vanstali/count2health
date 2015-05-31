<?php

namespace Count2Health\AppBundle\FatSecret;

use JMS\DiExtraBundle\Annotation as DI;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use Count2Health\AppBundle\FatSecret;
use Count2Health\UserBundle\Entity\User;

/**
 * @DI\Service("fatsecret.profile")
 */
class Profile
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

    public function get(User $user)
    {
        $profile = $this->fatSecret->doApiCall('profile.get', array(),
'food',
                $user);

        $info = array();

        $info['last_weight'] = new Mass("$profile->last_weight_kg", 'kg');
        $info['last_weight_date'] = $this->fatSecret->dateIntToDateTime(
                $profile->last_weight_date_int, $user);
        $info['last_weight_comment'] = "$profile->last_weight_comment";
        $info['goal_weight'] = new Mass("$profile->goal_weight_kg", 'kg');

        return $info;
    }
}
