<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Entities\Registries;

use DaveRandom\XsdDistiller\Entities\Element;
use DaveRandom\XsdDistiller\EntityName;
use DaveRandom\XsdDistiller\Registry;

final class ReadOnlyElementRegistry extends Registry
{
    public function __construct(ElementRegistry $inner)
    {
        $this->itemsWithNames = $inner->itemsWithNames;
        $this->itemValuesOnly = $inner->itemValuesOnly;
    }

    public function get(EntityName $name): Element
    {
        return $this->getItem($name);
    }
}
