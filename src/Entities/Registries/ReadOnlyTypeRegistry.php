<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Entities\Registries;

use DaveRandom\XsdDistiller\Entities\Type;
use DaveRandom\XsdDistiller\EntityName;
use DaveRandom\XsdDistiller\Registry;

final class ReadOnlyTypeRegistry extends Registry
{
    public function __construct(TypeRegistry $inner)
    {
        $this->itemsWithNames = $inner->itemsWithNames;
        $this->itemValuesOnly = $inner->itemValuesOnly;
    }

    public function get(EntityName $name): Type
    {
        return $this->getItem($name);
    }
}
