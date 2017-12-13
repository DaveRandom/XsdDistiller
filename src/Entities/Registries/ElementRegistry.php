<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Entities\Registries;

use DaveRandom\XsdDistiller\Entities\Element;
use DaveRandom\XsdDistiller\EntityName;
use DaveRandom\XsdDistiller\Registry;

final class ElementRegistry extends Registry
{
    public function add(Element $type): Element
    {
        return $this->addItem($type->getName(), $type);
    }

    /**
     * @param EntityName $name
     * @return Element
     * @throws \OutOfBoundsException
     */
    public function get(EntityName $name): Element
    {
        return $this->getItem($name);
    }
}
