<?php

namespace Count2Health\AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class GoalWeightLessThanStartWeight extends Constraint
{
    public $message = "The goal weight must be less than your starting weight of {{ start_weight }}.";

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
