<?php

namespace Count2Health\AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class GoalWeightGreaterThanStartWeight extends Constraint
{
    public $message = "The goal weight must be greater than your starting weight of {{ start_weight }}.";

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
