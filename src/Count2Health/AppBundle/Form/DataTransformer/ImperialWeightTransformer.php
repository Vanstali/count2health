<?php

namespace Count2Health\AppBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;

class ImperialWeightTransformer implements DataTransformerInterface
{
    public function transform($data)
    {
        if ($data === null) {
            return;
        }

        if (!($data instanceof Mass)) {
            throw new TransformationFailedException('Weight must be of type Mass');
        }

        $lb = $data->toUnit('lb');

        return array(
        'weight' => $lb,
        );
    }

    public function reverseTransform($data)
    {
        if (!isset($data['weight']) || $data['weight'] == null) {
            return;
        }

        $lb = $data['weight'];

        return new Mass($lb, 'lb');
    }
}
