<?php

namespace Count2Health\AppBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

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
            return null;
    }

}
