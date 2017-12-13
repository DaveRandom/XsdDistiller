<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser\Definitions;

use DaveRandom\XsdDistiller\DefinitionLocation;
use DaveRandom\XsdDistiller\EntityName;

final class ElementDefinition extends EntityDefinition
{
    private $typeName;
    private $minOccurs;
    private $maxOccurs;

    public function __construct(DefinitionLocation $definitionLocation, EntityName $name, EntityName $typeName, int $minOccurs, ?int $maxOccurs)
    {
        parent::__construct($definitionLocation, $name);

        $this->typeName = $typeName;
        $this->minOccurs = $minOccurs;
        $this->maxOccurs = $maxOccurs;
    }

    public function getTypeName(): EntityName
    {
        return $this->typeName;
    }

    public function getMinOccurs(): int
    {
        return $this->minOccurs;
    }

    public function getMaxOccurs(): ?int
    {
        return $this->maxOccurs;
    }
}
