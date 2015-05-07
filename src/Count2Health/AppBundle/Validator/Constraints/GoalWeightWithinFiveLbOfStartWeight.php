<?php

namespace Count2Health\AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class GoalWeightWithinFiveLbOfStartWeight extends Constraint
{
    public $message = 'Your goal weight must be within 5 {{ units }} of your start weight of {{ start_weight }}';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
