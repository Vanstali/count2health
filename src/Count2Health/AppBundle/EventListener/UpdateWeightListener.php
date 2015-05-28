<?php

namespace Count2Health\AppBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\Entity\WeightDiaryEntry;
use Count2Health\AppBundle\FatSecret;

/**
 * @DI\Service
 * @DI\Tag("doctrine.event_listener", attributes={"event": "postPersist"})
 * @DI\Tag("doctrine.event_listener", attributes={"event": "postUpdate"})
 */
class UpdateWeightListener
{

    private $fatSecret;

    /**
     * @DI\InjectParams({
     * "fatSecret" = @DI\Inject("fatsecret")
     * })
     */
    public function __construct(FatSecret $fatSecret)
    {
        $this->fatSecret = $fatSecret;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!($entity instanceof WeightDiaryEntry)) {
            return;
        }

        $this->postWeightToFatSecret($entity);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!($entity instanceof WeightDiaryEntry)) {
            return;
        }

        $this->postWeightToFatSecret($entity);
    }

    private function postWeightToFatSecret(WeightDiaryEntry $weight)
    {
        $user = $weight->getUser();

$arguments = array();
$arguments['current_weight_kg'] = $weight->getWeight()->toUnit('kg');
$arguments['date'] = $this->fatSecret->dateTimeToDateInt($weight->getDate());
$arguments['weight_type'] = $user->getPersonalDetails()->getWeightUnits();
$arguments['height_type'] = $user->getPersonalDetails()->getHeightUnits();
$arguments['goal_weight_kg'] = $user->getHealthPlan()->getGoalWeight()
    ->toUnit('kg');
$arguments['current_height_cm'] = $user->getPersonalDetails()->getHeight()->toUnit('cm');
$arguments['comment'] = $weight->getComment();

$this->fatSecret->doApiCall('weight.update', $arguments, $user);
    }
}
