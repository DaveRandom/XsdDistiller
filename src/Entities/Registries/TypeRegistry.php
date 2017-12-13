<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Entities\Registries;

use DaveRandom\XsdDistiller\Entities\Type;
use DaveRandom\XsdDistiller\EntityName;
use DaveRandom\XsdDistiller\Registry;

final class TypeRegistry extends Registry
{
    public function add(Type $type): Type
    {
        return $this->addItem($type->getName(), $type);
    }

    /**
     * @param EntityName $name
     * @return Type
     * @throws \OutOfBoundsException
     */
    public function get(EntityName $name): Type
    {
        return $this->getItem($name);
    }
}
