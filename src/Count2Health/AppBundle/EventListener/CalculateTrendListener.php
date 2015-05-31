<?php

namespace Count2Health\AppBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use JMS\DiExtraBundle\Annotation as DI;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use Count2Health\AppBundle\Entity\WeightDiaryEntry;

/**
 * @DI\Service
 * @DI\Tag("doctrine.event_listener", attributes={"event": "prePersist"})
 */
class CalculateTrendListener
{
    public function prePersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $entity = $args->getEntity();

        if (!($entity instanceof WeightDiaryEntry)) {
            return;
        }

        // Get previous weight log if any
        $entry = $entity->getUser()->getWeightBefore($entity);

        if ($entry) {
            $prevTrend = $entry->getTrend();
            $trend = $prevTrend->toUnit('kg')
                + 0.1 * ($entity->getWeight()->toUnit('kg')
                        - $prevTrend->toUnit('kg'));
            $entity->setTrend(new Mass($trend, 'kg'));
        } else {
            $entity->setTrend($entity->getWeight());
        }
    }
}
