<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser\Definitions\Registries;

use DaveRandom\XsdDistiller\EntityName;
use DaveRandom\XsdDistiller\Parser\Definitions\ElementDefinition;
use DaveRandom\XsdDistiller\Registry;

final class ElementDefinitionRegistry extends Registry
{
    public function add(ElementDefinition $elementDef): ElementDefinition
    {
        return $this->addItem($elementDef->getName(), $elementDef);
    }

    /**
     * @param EntityName $name
     * @return ElementDefinition
     * @throws \OutOfBoundsException
     */
    public function get(EntityName $name): ElementDefinition
    {
        return $this->getItem($name);
    }
}
