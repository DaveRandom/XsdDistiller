<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller;

use DaveRandom\XsdDistiller\Parser\Exceptions\InvalidReferenceException;
const XML_SCHEMA_URI = 'http://www.w3.org/2001/XMLSchema';

\define(__NAMESPACE__ . '\\LIB_ROOT_DIR', \realpath(__DIR__ . '/..'));
\define(__NAMESPACE__ . '\\RESOURCES_ROOT_DIR', \realpath(LIB_ROOT_DIR . '/resources'));

\assert(LIB_ROOT_DIR !== false, new \Error("LIB_ROOT_DIR is false"));
\assert(RESOURCES_ROOT_DIR !== false, new \Error("RESOURCES_ROOT_DIR is false"));

function domelement_get_target_namespace(\DOMElement $element): string
{
    $targetNamespace = $element->namespaceURI;

    do {
        if ($element->hasAttribute('targetNamespace')) {
            return $element->getAttribute('targetNamespace');
        }
    } while ($element = $element->parentNode);

    return $targetNamespace;
}

/**
 * @param \DOMElement $node
 * @param string $attribute
 * @return \DaveRandom\XsdDistiller\FullyQualifiedName
 * @throws InvalidReferenceException
 */
function parse_fully_qualified_entity_name_from_attribute(\DOMElement $node, string $attribute): FullyQualifiedName
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
