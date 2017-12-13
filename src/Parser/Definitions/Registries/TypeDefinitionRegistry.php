<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser\Definitions\Registries;

use DaveRandom\XsdDistiller\EntityName;
use DaveRandom\XsdDistiller\Parser\Definitions\TypeDefinition;
use DaveRandom\XsdDistiller\Registry;

final class TypeDefinitionRegistry extends Registry
{
    public function add(TypeDefinition $typeDef): TypeDefinition
    {
        return $this->addItem($typeDef->getName(), $typeDef);
    }

    /**
     * @param EntityName $name
     * @return TypeDefinition
     * @throws \OutOfBoundsException
     */
    public function get(EntityName $name): TypeDefinition
    {
        return $this->getItem($name);
    }
}
