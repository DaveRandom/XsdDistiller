<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser;

use DaveRandom\XsdDistiller\Entities\Registries\ElementRegistry;
use DaveRandom\XsdDistiller\Entities\Registries\ReadOnlyElementRegistry;
use DaveRandom\XsdDistiller\Entities\Registries\ReadOnlyTypeRegistry;
use DaveRandom\XsdDistiller\FullyQualifiedName;
use DaveRandom\XsdDistiller\Parser\Definitions\ComplexTypeDefinition;
use DaveRandom\XsdDistiller\Parser\Exceptions\CircularReferenceException;
use DaveRandom\XsdDistiller\Parser\Exceptions\InvalidDocumentException;
use DaveRandom\XsdDistiller\Parser\Exceptions\InvalidElementDefinitionException;
use DaveRandom\XsdDistiller\Parser\Exceptions\InvalidReferenceException;
use DaveRandom\XsdDistiller\Parser\Exceptions\InvalidTypeDefinitionException;
use DaveRandom\XsdDistiller\Parser\Exceptions\LoadErrorException;
use DaveRandom\XsdDistiller\Parser\Exceptions\MissingDefinitionException;
use DaveRandom\XsdDistiller\Parser\Exceptions\ParseErrorException;
use DaveRandom\XsdDistiller\Schema;
use Room11\DOMUtils\LibXMLFatalErrorException;
use const DaveRandom\XsdDistiller\RESOURCES_ROOT_DIR;
use const DaveRandom\XsdDistiller\XML_SCHEMA_URI;

final class Parser
{
    private $typeParser;
    private $typeResolver;

    public function __construct()
    {
        $this->typeParser = new EntityParser;
        $this->typeResolver = new EntityResolver;
    }

    /**
     * @param ParsingContext $ctx
     * @throws InvalidTypeDefinitionException
     * @throws InvalidReferenceException
     * @throws InvalidElementDefinitionException
     */
    private function parseTypes(ParsingContext $ctx): void
    {
        foreach ($ctx->xpath->query('/xs:schema/xs:simpleType') as $node) {
            $type = $this->typeParser->parseSimpleType($ctx, $node);

            if (!($type->getName() instanceof FullyQualifiedName)) {
                throw new InvalidTypeDefinitionException("No name defined for {$type}");
            }

            $ctx->typeDefinitions->add($type);
        }

        foreach ($ctx->xpath->query('/xs:schema/xs:complexType') as $node) {
            $type = $this->typeParser->parseComplexType($ctx, $node);

            if (!($type->getName() instanceof FullyQualifiedName)) {
                throw new InvalidTypeDefinitionException("No name defined for {$type}");
            }

            $ctx->typeDefinitions->add($type);
        }
    }

    /**
     * @param ParsingContext $ctx
     * @throws InvalidElementDefinitionException
     * @throws InvalidReferenceException
     * @throws InvalidTypeDefinitionException
     */
    private function parseRootElements(ParsingContext $ctx): void
    {
        foreach ($ctx->xpath->query('/xs:schema/xs:element') as $node) {
            $ctx->rootElementDefinitions->add($this->typeParser->parseElement($ctx, $node, true));
        }
    }

    /**
     * @param ParsingContext $ctx
     * @return \DaveRandom\XsdDistiller\Entities\Registries\ReadOnlyTypeRegistry
     * @throws CircularReferenceException
     * @throws InvalidTypeDefinitionException
     * @throws InvalidReferenceException
     * @throws MissingDefinitionException
     */
    private function resolveTypes(ParsingContext $ctx): ReadOnlyTypeRegistry
    {
        // First pass - resolve types
        foreach ($ctx->typeDefinitions as $definition) {
            $this->typeResolver->resolveType($ctx, $definition);
        }

        \assert(empty($ctx->resolvingTypes), new \Error('Context resolving types not empty, at least one type not fully resolved'));

        // Second pass - resolve complex type members (allow for circular references)
        foreach ($ctx->typeDefinitions as $name => $definition) {
            if ($definition instanceof ComplexTypeDefinition) {
                $this->typeResolver->resolveComplexTypeMembers($ctx, $definition);
            }
        }

        \assert(empty($ctx->memberStores), new \Error('Context member stores not empty, members for at least one complex type not fully resolved'));

        return new ReadOnlyTypeRegistry($ctx->types);
    }

    /**
     * @param ParsingContext $ctx
     * @return ReadOnlyElementRegistry
     * @throws MissingDefinitionException
     */
    private function resolveRootElements(ParsingContext $ctx): ReadOnlyElementRegistry
    {
        $registry = new ElementRegistry;

        foreach ($ctx->rootElementDefinitions as $def) {
            $registry->add($this->typeResolver->resolveElement($ctx, $def));
        }

        return new ReadOnlyElementRegistry($registry);
    }

    /**
     * @param \DOMDocument $document
     * @throws InvalidDocumentException
     */
    private function validateDocument(\DOMDocument $document): void
    {
        if ($document->documentElement->namespaceURI !== XML_SCHEMA_URI || $document->documentElement->localName !== 'schema') {
            throw new InvalidDocumentException(
                'Root element of XML schema must be <schema> in the ' . XML_SCHEMA_URI . ' namespace'
            );
        }

        if (!$document->schemaValidate(RESOURCES_ROOT_DIR . '/schema.xsd')) {
            throw new InvalidDocumentException("Schema validation failed");
        }
    }

    /**
     * @param \DOMDocument $document
     * @return Schema
     * @throws ParseErrorException
     */
    public function parseDocument(\DOMDocument $document): Schema
    {
        $this->validateDocument($document);

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('xs', XML_SCHEMA_URI);

        $ctx = new ParsingContext($document, $xpath);

        $this->parseTypes($ctx);
        $this->parseRootElements($ctx);

        $types = $this->resolveTypes($ctx);
        $elements = $this->resolveRootElements($ctx);

        return new Schema($types, $elements);
    }

    /**
     * @param \DOMElement $rootElement
     * @return Schema
     * @throws ParseErrorException
     */
    public function parseDocumentFragment(\DOMElement $rootElement): Schema
    {
        if ($rootElement->namespaceURI !== XML_SCHEMA_URI || $rootElement->localName !== 'schema') {
            throw new InvalidDocumentException(
                'Root element of XML schema must be <schema> in the ' . XML_SCHEMA_URI . ' namespace'
            );
        }

        $doc = new \DOMDocument;
        $doc->documentURI = $rootElement->ownerDocument->documentURI;
        $doc->appendChild($doc->importNode($rootElement, true));

        return $this->parseDocument($doc);
    }

    /**
     * @param string $xml
     * @return Schema
     * @throws ParseErrorException
     * @throws LoadErrorException
     */
    public function parseXml(string $xml): Schema
    {
        try {
            return $this->parseDocument(\Room11\DOMUtils\domdocument_load_xml($xml));
        } catch (LibXMLFatalErrorException $e) {
            throw new LoadErrorException("Unable to load schema: Error parsing document: {$e->getMessage()}", $e->getCode(), $e);
        }
    }

    /**
     * @param string $path
     * @return Schema
     * @throws ParseErrorException
     * @throws LoadErrorException
     */
    public function parsePath(string $path): Schema
    {
        $doc = new \DOMDocument();

        if (!$doc->load($path)) {
            throw new LoadErrorException('Unable to load schema: Could not retrieve document from supplied path');
        }

        return $this->parseDocument($doc);
    }
}
