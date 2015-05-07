<?php

namespace Count2Health\AppBundle\Util;

use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("activity_name_parser")
 */
class ActivityNameParser
{

private $entityManager;

    /**
     * @DI\InjectParams({
     *     "entityManager" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

public function parse($id, $name)
{
$repository = $this->entityManager
->getRepository('Count2HealthAppBundle:Activity');

if (0 == $id) {
$nameParts = explode(' > ', $name);

$activity = $repository
->findOneByName($nameParts[1]);

if (null == $activity) {
return $name;
}
else {
return $activity;
}
}
else {
$activity = $repository
->findOneByFatsecretEntryId($id);

if (null == $activity) {
return $name;
}
else {
return $activity;
}
}
}

}