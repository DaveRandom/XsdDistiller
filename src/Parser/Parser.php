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
use DaveRandom\XsdDistiller\XmlSchemaDocument;
use Room11\DOMUtils\LibXMLFatalErrorException;

final class Parser
{
    private $typeParser;
    private $typeResolver;
    private $xsdSchema;

    /**
     * @throws InvalidDocumentException
     */
    public function __construct()
    {
        $this->typeParser = new EntityParser;
        $this->typeResolver = new EntityResolver;
        $this->xsdSchema = XmlSchemaDocument::getSchemaSchema();
    }

    /**
     * @param ParsingContext $ctx
     * @throws InvalidTypeDefinitionException
     * @throws InvalidElementDefinitionException
     */
    private function parseTypes(ParsingContext $ctx): void
    {
        foreach ($ctx->xpath->query('./xs:simpleType', $ctx->rootElement) as $node) {
            $type = $this->typeParser->parseSimpleType($ctx, $node);

            if (!($type->getName() instanceof FullyQualifiedName)) {
                throw new InvalidTypeDefinitionException("No name defined for {$type}");
            }

            $ctx->typeDefinitions->add($type);
        }

        foreach ($ctx->xpath->query('./xs:complexType', $ctx->rootElement) as $node) {
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
     * @throws InvalidTypeDefinitionException
     */
    private function parseRootElements(ParsingContext $ctx): void
    {
        foreach ($ctx->xpath->query('./xs:element', $ctx->rootElement) as $node) {
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
     * @param \DOMElement|null $rootElement
     * @return Schema
     * @throws ParseErrorException
     */
    public function parseDocument(\DOMDocument $document, \DOMElement $rootElement = null): Schema
    {
        $this->xsdSchema->validate($rootElement ?? $document);
        $rootElement = $rootElement ?? $document->documentElement;

        $ctx = new ParsingContext($document, $rootElement);

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
        return $this->parseDocument($rootElement->ownerDocument, $rootElement);
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
