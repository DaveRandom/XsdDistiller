<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser;

use DaveRandom\XsdDistiller\Entities\Registries\TypeRegistry;
use DaveRandom\XsdDistiller\Parser\Definitions\Registries\ElementDefinitionRegistry;
use DaveRandom\XsdDistiller\Parser\Definitions\Registries\TypeDefinitionRegistry;
use const DaveRandom\XsdDistiller\XML_SCHEMA_URI;

final class ParsingContext
{
    /** @var \DOMDocument */
    public $document;

    /** @var \DOMXPath */
    public $xpath;

    /** @var \DOMElement */
    public $rootElement;

    /** @var TypeDefinitionRegistry */
    public $typeDefinitions;

    /** @var ElementDefinitionRegistry */
    public $rootElementDefinitions;

    /** @var TypeRegistry */
    public $types;

    /** @var \ArrayObject[] */
    public $memberStores = [];

    /** @var bool[] */
    public $resolvingTypes = [];

    public function __construct(\DOMDocument $document, \DOMElement $rootElement)
    {
        $this->document = $document;
        $this->rootElement = $rootElement;

        $this->xpath = new \DOMXPath($document);
        $this->xpath->registerNamespace('xs', XML_SCHEMA_URI);

        $this->typeDefinitions = new TypeDefinitionRegistry;
        $this->rootElementDefinitions = new ElementDefinitionRegistry;
        $this->types = new TypeRegistry;
    }
}
