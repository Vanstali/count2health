<?php

namespace Count2Health\AppBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class NullWeightTransformer implements DataTransformerInterface
{
    public function transform($data)
    {
        return array(
                'weight' => null,
                );
    }

    public function reverseTransform($data)
    {
        return;
    }
}
