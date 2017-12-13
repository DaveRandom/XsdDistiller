<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Entities;

use DaveRandom\XsdDistiller\DefinitionLocation;
use DaveRandom\XsdDistiller\EntityName;

final class Element extends Entity
{
    private $type;
    private $minOccurs;
    private $maxOccurs;

    public function __construct(?DefinitionLocation $definitionLocation, EntityName $name, Type $type, int $minOccurs, ?int $maxOccurs)
    {
        parent::__construct($definitionLocation, $name);

        $this->type = $type;
        $this->minOccurs = $minOccurs;
        $this->maxOccurs = $maxOccurs;
    }

    public function getType(): Type
    {
        return $this->type;
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
