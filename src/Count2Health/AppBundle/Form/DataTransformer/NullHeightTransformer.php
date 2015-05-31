<?php

namespace Count2Health\AppBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class NullHeightTransformer implements DataTransformerInterface
{
    public function transform($data)
    {
        return array(
                'height' => null,
                );
    }

    public function reverseTransform($data)
    {
        return;
    }
}
