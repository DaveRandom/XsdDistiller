<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Entities;

use DaveRandom\XsdDistiller\DefinitionLocation;
use DaveRandom\XsdDistiller\EntityName;

abstract class Entity
{
    private $definitionLocation;
    private $name;

    protected function __construct(?DefinitionLocation $definitionLocation, EntityName $name)
    {
        $this->definitionLocation = $definitionLocation;
        $this->name = $name;
    }

    public function getDefinitionLocation(): ?DefinitionLocation
    {
        return $this->definitionLocation;
    }

    public function getName(): EntityName
    {
        return $this->name;
    }

    public function __toString(): string
    {
        $format = '%s %s';
        $values = [
            \substr(static::class, (int)\strrpos(static::class, '\\')), // class name without namespace
            (string)$this->name,
        ];

        if ($this->getDefinitionLocation() !== null) {
            $format .= " defined at %s";
            $values[] = (string)$this->definitionLocation;
        }

        return \vsprintf($format, $values);
    }
}
