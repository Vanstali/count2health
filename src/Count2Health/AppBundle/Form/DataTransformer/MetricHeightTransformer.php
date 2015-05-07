<?php

namespace Count2Health\AppBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use PhpUnitsOfMeasure\PhysicalQuantity\Length;

class MetricHeightTransformer implements DataTransformerInterface
{

    public function transform($data)
    {
        if ($data === null) {
            return null;
        }

if (!($data instanceof Length)) {
    throw new TransformationFailedException('Height must be of type Length');
}

$cm = $data->toUnit('cm');

return array(
        'height' => $cm,
        );
    }

    public function reverseTransform($data)
    {
        if (!isset($data['height']) || $data['height'] == null) {
            return;
        }

        $cm = $data['height'];
        return new Length($cm, 'cm');
    }

}
