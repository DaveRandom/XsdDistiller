<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Entities;

use DaveRandom\XsdDistiller\FullyQualifiedName;

final class BuiltInType extends SimpleType
{
    public function __construct(FullyQualifiedName $name)
    {
        parent::__construct(null, $name, null);
    }
}
