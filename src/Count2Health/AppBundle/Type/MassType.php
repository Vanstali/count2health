<?php

namespace Count2Health\AppBundle\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;

class MassType extends Type
{
    const MASS = 'mass';

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getDecimalTypeDeclarationSql($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

return new Mass($value, 'kg');
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value->toUnit('kg');
    }

    public function getName()
    {
        return self::MASS;
    }

}
