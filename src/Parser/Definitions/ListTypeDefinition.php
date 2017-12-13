<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser\Definitions;

use DaveRandom\XsdDistiller\DefinitionLocation;
use DaveRandom\XsdDistiller\EntityName;
use DaveRandom\XsdDistiller\FullyQualifiedName;

final class ListTypeDefinition extends SimpleTypeDefinition
{
    private $listElement;

    public function __construct(DefinitionLocation $definitionLocation, EntityName $name, FullyQualifiedName $baseTypeName, \DOMElement $listElement)
    {
        parent::__construct($definitionLocation, $name, $baseTypeName);

        $this->listElement = $listElement;
    }

    public function getListElement(): \DOMElement
    {
        return $this->listElement;
    }

    public function __toString(): string
    {
        return "list type {$this->getName()} defined at {$this->getDefinitionLocation()}";
    }
}
