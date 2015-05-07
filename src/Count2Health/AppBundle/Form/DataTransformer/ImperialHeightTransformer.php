<?php

namespace Count2Health\AppBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use PhpUnitsOfMeasure\PhysicalQuantity\Length;

class ImperialHeightTransformer implements DataTransformerInterface
{

    public function transform($data)
    {
        if ($data === null) {
            return null;
        }

if (!($data instanceof Length)) {
    throw new TransformationFailedException('Height must be of type Length');
}

$inches = $data->toUnit('inches');

$ft = floor($inches / 12);
$in = round(fmod($inches, 12), 1);

return array(
        'feet' => $ft,
        'inches' => $in,
        );
    }

    public function reverseTransform($data)
    {
        if (!isset($data['feet']) || !isset($data['inches'])
                || !$data['feet'] || !$data['inches']) {
            return;
        }
        $inches = $data['feet'] * 12 + $data['inches'];
        return new Length($inches, 'inches');
    }

}
