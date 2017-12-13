<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller;

const XML_SCHEMA_URI = 'http://www.w3.org/2001/XMLSchema';
const WSDL_SCHEMA_URI = 'http://schemas.xmlsoap.org/wsdl/';

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
