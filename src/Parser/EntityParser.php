<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser;

use DaveRandom\XsdDistiller\DefinitionLocation;
use DaveRandom\XsdDistiller\EntityName;
use DaveRandom\XsdDistiller\FullyQualifiedName;
use DaveRandom\XsdDistiller\Parser\Definitions\ComplexTypeDefinition;
use DaveRandom\XsdDistiller\Parser\Definitions\ElementDefinition;
use DaveRandom\XsdDistiller\Parser\Definitions\ListTypeDefinition;
use DaveRandom\XsdDistiller\Parser\Definitions\RestrictionTypeDefinition;
use DaveRandom\XsdDistiller\Parser\Definitions\SimpleTypeDefinition;
use DaveRandom\XsdDistiller\Parser\Exceptions\InvalidElementDefinitionException;
use DaveRandom\XsdDistiller\Parser\Exceptions\InvalidReferenceException;
use DaveRandom\XsdDistiller\Parser\Exceptions\InvalidTypeDefinitionException;
use const DaveRandom\XsdDistiller\XML_SCHEMA_URI;
use function DaveRandom\XsdDistiller\domelement_get_target_namespace;
use function DaveRandom\XsdDistiller\parse_fully_qualified_entity_name_from_attribute;

final class EntityParser
{
    /**
     * @param \DOMElement $typeNode
     * @param \DOMElement $restrictionNode
     * @return RestrictionTypeDefinition
     * @throws InvalidTypeDefinitionException
     */
    private function parseRestrictionType(\DOMElement $typeNode, \DOMElement $restrictionNode): RestrictionTypeDefinition
    {
        $location = new DefinitionLocation($typeNode);
        $name = $this->getTypeName($typeNode);

        try {
            $baseName = parse_fully_qualified_entity_name_from_attribute($restrictionNode, 'base');
        } catch (InvalidReferenceException $e) {
            throw new InvalidTypeDefinitionException(
                "Missing or invalid base type referenced by restriction type {$name} defined at {$location}"
            );
        }

        // todo: actually parse restriction info

        return new RestrictionTypeDefinition($location, $name, $baseName, $restrictionNode);
    }

    /**
     * @param \DOMElement $typeNode
     * @param \DOMElement $listNode
     * @return ListTypeDefinition
     * @throws InvalidTypeDefinitionException
     */
    private function parseListType(\DOMElement $typeNode, \DOMElement $listNode): ListTypeDefinition
    {
        $location = new DefinitionLocation($typeNode);
        $name = $this->getTypeName($typeNode);

        try {
            $baseName = parse_fully_qualified_entity_name_from_attribute($listNode, 'base');
        } catch (InvalidReferenceException $e) {
            throw new InvalidTypeDefinitionException(
                "Missing or invalid base type referenced by list type {$name} defined at {$location}"
            );
        }

        // todo: actually parse list info

        return new ListTypeDefinition($location, $name, $baseName, $listNode);
    }

    /**
     * @param \DOMElement $node
     * @return EntityName
     */
    private function getTypeName(\DOMElement $node): EntityName
    {
        return $node->hasAttributeNS(XML_SCHEMA_URI, 'name')
            ? new FullyQualifiedName(domelement_get_target_namespace($node), $node->getAttributeNS(XML_SCHEMA_URI, 'name'))
            : new EntityName('##anon##' . $node->getNodePath());
    }

    /**
     * @param ParsingContext $ctx
     * @param \DOMElement $node
     * @return SimpleTypeDefinition
     * @throws InvalidTypeDefinitionException
     */
    public function parseSimpleType(ParsingContext $ctx, \DOMElement $node): SimpleTypeDefinition
    {
        $restriction = $ctx->xpath->query('./xs:restriction', $node);

        if ($restriction && $restriction->length) {
            return $this->parseRestrictionType($node, $restriction->item(0));
        }

        $list = $ctx->xpath->query('./xs:list', $node);

        if ($list && $list->length) {
            return $this->parseListType($node, $list->item(0));
        }

        // todo: support unions
        throw new InvalidTypeDefinitionException(
            "Invalid or unsupported simple type definition at node {$node->getNodePath()}"
        );
    }

    /**
     * @param ParsingContext $ctx
     * @param \DOMElement $node
     * @return ComplexTypeDefinition
     * @throws InvalidElementDefinitionException
     * @throws InvalidTypeDefinitionException
     */
    public function parseComplexType(ParsingContext $ctx, \DOMElement $node): ComplexTypeDefinition
    {
        $location = new DefinitionLocation($node);
        $name = $this->getTypeName($node);

        $baseTypeName = null;
        $elements = [];

        $membersParent = $node;

        // todo: support <xs:restriction>
        $extensionNodes = $ctx->xpath->query('./xs:complexContent/xs:extension', $node);

        if ($extensionNodes->length === 1) {
            $membersParent = $extensionNodes->item(0);

            try {
                $baseTypeName = parse_fully_qualified_entity_name_from_attribute($membersParent, 'base');
            } catch (InvalidReferenceException $e) {
                throw new InvalidTypeDefinitionException(
                    "Base type not defined for extension for complex type {$name} defined at {$location}"
                );
            }
        }

        // todo: support content container other than <xs:sequence>
        foreach ($ctx->xpath->query('./xs:sequence/xs:element', $membersParent) as $member) {
            $elements[] = $this->parseElement($ctx, $member, false);
        }

        return new ComplexTypeDefinition($location, $name, $baseTypeName, $elements);
    }

    /**
     * @param ParsingContext $ctx
     * @param \DOMElement $node
     * @param bool $root
     * @return ElementDefinition
     * @throws InvalidElementDefinitionException
     * @throws InvalidTypeDefinitionException
     */
    public function parseElement(ParsingContext $ctx, \DOMElement $node, bool $root): ElementDefinition
    {
        $location = new DefinitionLocation($node);

        if (!$node->hasAttributeNS(XML_SCHEMA_URI, 'name')) {
            throw new InvalidElementDefinitionException("Element does not define name at {$location}");
        }

        $name = $root
            ? new FullyQualifiedName(domelement_get_target_namespace($node), $node->getAttributeNS(XML_SCHEMA_URI, 'name'))
            : new EntityName($node->getAttributeNS(XML_SCHEMA_URI, 'name'));

        $minOccurs = $node->hasAttributeNS(XML_SCHEMA_URI, 'minOccurs')
            ? (int)$node->getAttributeNS(XML_SCHEMA_URI, 'minOccurs')
            : 1;
        $maxOccurs = 1;

        if ($node->hasAttributeNS(XML_SCHEMA_URI, 'maxOccurs')) {
            $maxOccursStr = $node->getAttributeNS(XML_SCHEMA_URI, 'maxOccurs');

            $maxOccurs = $maxOccursStr !== 'unbounded'
                ? (int)$maxOccurs
                : null;
        }

        if (isset($maxOccurs) && $minOccurs > $maxOccurs) {
            throw new InvalidElementDefinitionException("minOccurs larger than maxOccurs for element {$name} defined at {$location}");
        }

        if ($node->hasAttributeNS(XML_SCHEMA_URI, 'type')) {
            try {
                $typeName = parse_fully_qualified_entity_name_from_attribute($node, 'type');
            } catch (InvalidReferenceException $e) {
                throw new InvalidElementDefinitionException("Invalid type referenced by element {$name} defined at {$location}");
            }

            return new ElementDefinition($location, $name, $typeName, $minOccurs, $maxOccurs);
        }

        $typeNodes = $ctx->xpath->query('./xs:complexType', $node);

        if ($typeNodes->length === 1) {
            $typeName = $ctx->typeDefinitions->add($this->parseComplexType($ctx, $typeNodes->item(0)))->getName();

            return new ElementDefinition($location, $name, $typeName, $minOccurs, $maxOccurs);
        }

        $typeNodes = $ctx->xpath->query('./xs:simpleType', $node);

        if ($typeNodes->length === 1) {
            $typeName = $ctx->typeDefinitions->add($this->parseSimpleType($ctx, $typeNodes->item(0)))->getName();

            return new ElementDefinition($location, $name, $typeName, $minOccurs, $maxOccurs);
        }

        return new ElementDefinition($location, $name, FullyQualifiedName::getAnyTypeName(), $minOccurs, $maxOccurs);
    }
}
