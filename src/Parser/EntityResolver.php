<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser;

use DaveRandom\XsdDistiller\Entities\BuiltInType;
use DaveRandom\XsdDistiller\Entities\ComplexType;
use DaveRandom\XsdDistiller\Entities\Element;
use DaveRandom\XsdDistiller\Entities\ListType;
use DaveRandom\XsdDistiller\Entities\RestrictionType;
use DaveRandom\XsdDistiller\Entities\SimpleType;
use DaveRandom\XsdDistiller\Entities\Type;
use DaveRandom\XsdDistiller\FullyQualifiedName;
use DaveRandom\XsdDistiller\Parser\Definitions\ComplexTypeDefinition;
use DaveRandom\XsdDistiller\Parser\Definitions\ElementDefinition;
use DaveRandom\XsdDistiller\Parser\Definitions\ListTypeDefinition;
use DaveRandom\XsdDistiller\Parser\Definitions\RestrictionTypeDefinition;
use DaveRandom\XsdDistiller\Parser\Definitions\SimpleTypeDefinition;
use DaveRandom\XsdDistiller\Parser\Definitions\TypeDefinition;
use DaveRandom\XsdDistiller\Parser\Exceptions\CircularReferenceException;
use DaveRandom\XsdDistiller\Parser\Exceptions\InvalidReferenceException;
use DaveRandom\XsdDistiller\Parser\Exceptions\InvalidTypeDefinitionException;
use DaveRandom\XsdDistiller\Parser\Exceptions\MissingDefinitionException;
use const DaveRandom\XsdDistiller\XML_SCHEMA_URI;

final class EntityResolver
{
    private static $builtInTypes = [];

    private static function getBuiltInType(FullyQualifiedName $name): BuiltInType
    {
        return self::$builtInTypes[(string)$name] ?? (self::$builtInTypes[(string)$name] = new BuiltInType($name));
    }

    /**
     * @param ParsingContext $ctx
     * @param TypeDefinition $typeDef
     * @return \DaveRandom\XsdDistiller\Entities\Type
     * @throws CircularReferenceException
     * @throws InvalidTypeDefinitionException
     * @throws InvalidReferenceException
     * @throws MissingDefinitionException
     */
    private function getBaseType(ParsingContext $ctx, TypeDefinition $typeDef): ?Type
    {
        if (!$typeDef->hasBaseType()) {
            return null;
        }

        $baseName = $typeDef->getBaseTypeName();

        // built-in type
        if ($typeDef instanceof SimpleTypeDefinition && $baseName->getNamespace() === XML_SCHEMA_URI) {
            return self::getBuiltInType($baseName);
        }

        // already resolved type
        if ($ctx->types->contains($baseName)) {
            return $ctx->types->get($baseName);
        }

        // type awaiting resolution
        if ($ctx->typeDefinitions->contains($baseName)) {
            return $this->resolveType($ctx, $ctx->typeDefinitions->get($baseName));
        }

        throw new MissingDefinitionException(
            "Type {$baseName} was not defined in the document, referenced as base type of {$typeDef}"
        );
    }

    /**
     * @param SimpleTypeDefinition $typeDef
     * @param \DaveRandom\XsdDistiller\Entities\Type $baseType
     * @return \DaveRandom\XsdDistiller\Entities\SimpleType
     * @throws InvalidTypeDefinitionException
     */
    private function createSimpleType(SimpleTypeDefinition $typeDef, ?Type $baseType): SimpleType
    {
        if ($baseType === null) {
            throw new InvalidTypeDefinitionException("No base type defined for {$typeDef}");
        }

        if (!($baseType instanceof SimpleType)) {
            throw new InvalidTypeDefinitionException("Invalid base type relationship: {$typeDef} inherits {$baseType}");
        }

        if ($typeDef instanceof RestrictionTypeDefinition) {
            return new RestrictionType($typeDef->getDefinitionLocation(), $typeDef->getName(), $baseType);
        }

        if ($typeDef instanceof ListTypeDefinition) {
            return new ListType($typeDef->getDefinitionLocation(), $typeDef->getName(), $baseType);
        }

        throw new InvalidTypeDefinitionException("Unknown form of simple type encountered at {$typeDef}");
    }

    /**
     * @param ParsingContext $ctx
     * @param ComplexTypeDefinition $typeDef
     * @param \DaveRandom\XsdDistiller\Entities\Type|null $baseType
     * @return \DaveRandom\XsdDistiller\Entities\ComplexType
     * @throws InvalidTypeDefinitionException
     */
    private function createComplexType(ParsingContext $ctx, ComplexTypeDefinition $typeDef, ?Type $baseType): ComplexType
    {
        if ($baseType !== null && !($baseType instanceof ComplexType)) {
            throw new InvalidTypeDefinitionException("Invalid base type relationship: {$typeDef} inherits {$baseType}");
        }

        $ctx->memberStores[(string)$typeDef->getName()] = $members = new \ArrayObject();

        return new ComplexType($typeDef->getDefinitionLocation(), $typeDef->getName(), $baseType, $members);
    }

    /**
     * @param ElementDefinition $elementDef
     * @param \DaveRandom\XsdDistiller\Entities\Type $type
     * @return \DaveRandom\XsdDistiller\Entities\Element
     */
    private function createElement(ElementDefinition $elementDef, Type $type): Element
    {
        return new Element(
            $elementDef->getDefinitionLocation(),
            $elementDef->getName(),
            $type,
            $elementDef->getMinOccurs(),
            $elementDef->getMaxOccurs()
        );
    }

    /**
     * @param ParsingContext $ctx
     * @param ComplexTypeDefinition|null $typeDef
     * @param ElementDefinition $elementDef
     * @return \DaveRandom\XsdDistiller\Entities\Element
     * @throws MissingDefinitionException
     */
    public function resolveElement(ParsingContext $ctx, ElementDefinition $elementDef, ComplexTypeDefinition $typeDef = null): Element
    {
        $memberTypeName = $elementDef->getTypeName();

        if (!$ctx->types->contains($memberTypeName)) {
            $description = $typeDef !== null
                ? "member {$elementDef->getName()} of {$typeDef}"
                : (string)$elementDef;

            throw new MissingDefinitionException(
                "Type {$memberTypeName} was not defined in the document, referenced as type of {$description}"
            );
        }

        return $this->createElement($elementDef, $ctx->types->get($memberTypeName));
    }

    /**
     * @param ParsingContext $ctx
     * @param ComplexTypeDefinition $typeDef
     * @throws InvalidReferenceException
     * @throws MissingDefinitionException
     */
    public function resolveComplexTypeMembers(ParsingContext $ctx, ComplexTypeDefinition $typeDef): void
    {
        if (!isset($ctx->memberStores[(string)$typeDef->getName()])) {
            throw new InvalidReferenceException("Member store does not exist for {$typeDef}");
        }

        $memberStore = $ctx->memberStores[(string)$typeDef->getName()];
        unset($ctx->memberStores[(string)$typeDef->getName()]);

        foreach ($typeDef->getMemberDefinitions() as $memberDef) {
            $memberStore[] = $this->resolveElement($ctx, $memberDef, $typeDef);
        }

        // Get the members to force creation of member collection object
        // todo: this is hacky af, fix
        /** @noinspection PhpUndefinedMethodInspection */
        $ctx->types->get($typeDef->getName())->getMembers();
    }

    /**
     * @param ParsingContext $ctx
     * @param TypeDefinition $typeDef
     * @return Type
     * @throws InvalidTypeDefinitionException
     * @throws InvalidReferenceException
     * @throws CircularReferenceException
     */
    public function resolveType(ParsingContext $ctx, TypeDefinition $typeDef): Type
    {
        if (!$ctx->types->contains($typeDef->getName())) {
            return $ctx->types->get($typeDef->getName());
        }

        if (isset($this->resolvingTypes[(string)$typeDef->getName()])) {
            throw new CircularReferenceException("Circular reference detected, cycle root is {$typeDef}");
        }

        $ctx->resolvingTypes[(string)$typeDef->getName()] = true;

        try {
            $baseType = $this->getBaseType($ctx, $typeDef);

            if ($typeDef instanceof SimpleTypeDefinition) {
                return $ctx->types->add($this->createSimpleType($typeDef, $baseType));
            }

            if ($typeDef instanceof ComplexTypeDefinition) {
                return $ctx->types->add($this->createComplexType($ctx, $typeDef, $baseType));
            }

            throw new InvalidTypeDefinitionException("Unknown form of type encountered at {$typeDef}");
        } finally {
            unset($ctx->resolvingTypes[(string)$typeDef->getName()]);
        }
    }
}
