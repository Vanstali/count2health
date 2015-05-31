<?php

namespace Count2Health\AppBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;

class MetricWeightTransformer implements DataTransformerInterface
{
    public function transform($data)
    {
        if ($data === null) {
            return;
        }

        if (!($data instanceof Mass)) {
            throw new TransformationFailedException('Weight must be of type Mass');
        }

        $kg = $data->toUnit('kg');

        return array(
        'weight' => $kg,
        );
    }

    public function reverseTransform($data)
    {
        if (!isset($data['weight']) || $data['weight'] == null) {
            return;
        }

        $kg = $data['weight'];

        return new Mass($kg, 'kg');
    }
}
