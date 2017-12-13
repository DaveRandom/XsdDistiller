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
use function DaveRandom\XsdDistiller\domelement_get_target_namespace;

final class EntityParser
{
    /**
     * @param \DOMElement $node
     * @param string $attribute
     * @return \DaveRandom\XsdDistiller\FullyQualifiedName
     * @throws InvalidReferenceException
     */
    private function parseFullyQualifiedTypeName(\DOMElement $node, string $attribute): FullyQualifiedName
    {
        $nameParts = \explode(':', $node->getAttribute($attribute));

        switch (\count($nameParts)) {
            case 1: {
                $name = $nameParts[0];
                $namespace = domelement_get_target_namespace($node);
                break;
            }

            case 2: {
                [$prefix, $name] = $nameParts;
                $namespace = $node->lookupNamespaceUri($prefix);
                break;
            }

            default: throw new InvalidReferenceException('Invalid type reference ' . $node->getAttribute($attribute));
        }

        return new FullyQualifiedName($namespace, $name);
    }

    /**
     * @param \DOMElement $typeNode
     * @param \DOMElement $restrictionNode
     * @return RestrictionTypeDefinition
     * @throws InvalidReferenceException
     */
    private function parseRestrictionType(\DOMElement $typeNode, \DOMElement $restrictionNode): RestrictionTypeDefinition
    {
        $location = new DefinitionLocation($typeNode);
        $name = $this->getTypeName($typeNode);
        $baseName = $this->parseFullyQualifiedTypeName($restrictionNode, 'base');

        // todo: actually parse restriction info

        return new RestrictionTypeDefinition($location, $name, $baseName, $restrictionNode);
    }

    /**
     * @param \DOMElement $typeNode
     * @param \DOMElement $listNode
     * @return ListTypeDefinition
     * @throws InvalidReferenceException
     */
    private function parseListType(\DOMElement $typeNode, \DOMElement $listNode): ListTypeDefinition
    {
        $location = new DefinitionLocation($typeNode);
        $name = $this->getTypeName($typeNode);
        $baseName = $this->parseFullyQualifiedTypeName($listNode, 'base');

        // todo: actually parse list info

        return new ListTypeDefinition($location, $name, $baseName, $listNode);
    }

    /**
     * @param \DOMElement $node
     * @return EntityName
     */
    private function getTypeName(\DOMElement $node): EntityName
    {
        return $node->hasAttribute('name')
            ? new FullyQualifiedName(domelement_get_target_namespace($node), $node->getAttribute('name'))
            : new EntityName('##anon##' . $node->getNodePath());
    }

    /**
     * @param ParsingContext $ctx
     * @param \DOMElement $node
     * @return SimpleTypeDefinition
     * @throws InvalidTypeDefinitionException
     * @throws InvalidReferenceException
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
     * @throws InvalidReferenceException
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
            $baseTypeName = $this->parseFullyQualifiedTypeName($membersParent, 'base');
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
     * @throws InvalidReferenceException
     * @throws InvalidTypeDefinitionException
     */
    public function parseElement(ParsingContext $ctx, \DOMElement $node, bool $root): ElementDefinition
    {
        $location = new DefinitionLocation($node);

        if (!$node->hasAttribute('name')) {
            throw new InvalidElementDefinitionException("Element does not define name at {$location}");
        }

        $name = $root
            ? new FullyQualifiedName(domelement_get_target_namespace($node), $node->getAttribute('name'))
            : new EntityName($node->getAttribute('name'));

        $minOccurs = $node->hasAttribute('minOccurs') ? (int)$node->getAttribute('minOccurs') : 1;
        $maxOccurs = 1;

        if ($node->hasAttribute('maxOccurs')) {
            $maxOccursStr = $node->getAttribute('maxOccurs');

            $maxOccurs = $maxOccursStr !== 'unbounded'
                ? (int)$maxOccurs
                : null;
        }

        if (isset($maxOccurs) && $minOccurs > $maxOccurs) {
            throw new InvalidElementDefinitionException("minOccurs larger than maxOccurs for element {$name} defined at {$location}");
        }

        if ($node->hasAttribute('type')) {
            $typeName = $this->parseFullyQualifiedTypeName($node, 'type');

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
