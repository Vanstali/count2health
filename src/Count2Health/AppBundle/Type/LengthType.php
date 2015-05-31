<?php

namespace Count2Health\AppBundle\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PhpUnitsOfMeasure\PhysicalQuantity\Length;

class LengthType extends Type
{
    const LENGTH = 'length';

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getDecimalTypeDeclarationSql($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return;
        }

        return new Length($value, 'cm');
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value->toUnit('cm');
    }

    public function getName()
    {
        return self::LENGTH;
    }
}
