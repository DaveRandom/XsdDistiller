<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser;

use DaveRandom\XsdDistiller\Entities\Registries\TypeRegistry;
use DaveRandom\XsdDistiller\Parser\Definitions\Registries\ElementDefinitionRegistry;
use DaveRandom\XsdDistiller\Parser\Definitions\Registries\TypeDefinitionRegistry;

final class ParsingContext
{
    /** @var \DOMDocument */
    public $document;

    /** @var \DOMXPath */
    public $xpath;

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

    public function __construct(\DOMDocument $document, \DOMXPath $xpath)
    {
        $this->document = $document;
        $this->xpath = $xpath;

        $this->typeDefinitions = new TypeDefinitionRegistry;
        $this->types = new TypeRegistry;
    }
}
