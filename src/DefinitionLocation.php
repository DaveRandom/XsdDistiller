<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller;

final class DefinitionLocation
{
    private $element;

    public function __construct(\DOMElement $element)
    {
        $this->element = $element;
    }

    public function getElement(): \DOMElement
    {
        return $this->element;
    }

    public function getNodePath(): string
    {
        return $this->element->getNodePath();
    }

    public function getDocumentUri(): string
    {
        return $this->element->ownerDocument->documentURI ?? '[unknown]';
    }

    public function getLineNumber(): int
    {
        return $this->element->getLineNo();
    }

    public function __toString(): string
    {
        return "{$this->getNodePath()} in {$this->getDocumentUri()} on line {$this->getLineNumber()}";
    }
}
