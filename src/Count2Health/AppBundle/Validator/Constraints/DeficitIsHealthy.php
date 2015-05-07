<?php

namespace Count2Health\AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DeficitIsHealthy extends Constraint
{
    public $message = "This caloric deficit is unhealthy. You should not consume fewer than {{ healthy_calories }} calories per day. Your maximum deficit is {{ maximum_deficit }} calories.";

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
