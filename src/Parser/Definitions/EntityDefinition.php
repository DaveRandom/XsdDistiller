<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser\Definitions;

use DaveRandom\XsdDistiller\DefinitionLocation;
use DaveRandom\XsdDistiller\EntityName;

abstract class EntityDefinition
{
    private $definitionLocation;
    private $name;

    protected function __construct(DefinitionLocation $definitionLocation, EntityName $name)
    {
        $this->definitionLocation = $definitionLocation;
        $this->name = $name;
    }

    public function getDefinitionLocation(): DefinitionLocation
    {
        return $this->definitionLocation;
    }

    public function getName(): EntityName
    {
        return $this->name;
    }
}
