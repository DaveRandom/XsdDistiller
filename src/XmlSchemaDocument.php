<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller;

use DaveRandom\XsdDistiller\Parser\Exceptions\InvalidDocumentException;
use DaveRandom\XsdDistiller\Parser\Exceptions\LoadErrorException;
use Room11\DOMUtils\LibXMLFatalErrorException;

final class XmlSchemaDocument
{
    private const XSD_SCHEMA_SOURCE_FILE = __DIR__ . '/../../resources/schema.xsd'; // schemaception
    private static $xsdSchemaSource;
    private static $xsdSchema;

    private $source;

    private static function getXsdSchemaSource(): string
    {
        return self::$xsdSchemaSource ?? self::$xsdSchemaSource = \file_get_contents(self::XSD_SCHEMA_SOURCE_FILE);
    }

    /**
     * @param string $source
     * @return bool
     * @throws InvalidDocumentException
     */
    private static function validateSchemaSource(string $source): bool
    {
        try {
            return \Room11\DOMUtils\domdocument_load_xml($source)->schemaValidateSource(self::getXsdSchemaSource());
        } catch (LibXMLFatalErrorException $e) {
            throw new InvalidDocumentException("Parsing schema document failed");
        }
    }

    /**
     * @return XmlSchemaDocument
     * @throws InvalidDocumentException
     */
    public static function getSchemaSchema(): XmlSchemaDocument
    {
        return self::$xsdSchema ?? self::$xsdSchema = self::createFromString(self::getXsdSchemaSource(), true);
    }

    /**
     * @param string $path
     * @param resource|null $context
     * @param bool $validate
     * @return XmlSchemaDocument
     * @throws InvalidDocumentException
     * @throws LoadErrorException
     */
    public static function createFromPath(string $path, $context = null, bool $validate = false): XmlSchemaDocument
    {
        if (false === $source = \file_get_contents($path, false, $context)) {
            throw new LoadErrorException("Loading schema from path {$path} failed");
        }

        if ($validate && !self::validateSchemaSource($source)) {
            throw new InvalidDocumentException("Schema loaded from path {$path} is invalid");
        }

        return new XmlSchemaDocument($source);
    }

    /**
     * @param string $source
     * @param bool $validate
     * @return XmlSchemaDocument
     * @throws InvalidDocumentException
     */
    public static function createFromString(string $source, bool $validate = false): XmlSchemaDocument
    {
        if ($validate && !self::validateSchemaSource($source)) {
            throw new InvalidDocumentException('Schema source invalid');
        }

        return new XmlSchemaDocument($source);
    }

    private function __construct(string $source)
    {
        $this->source = $source;
    }

    private function validateElement(\DOMElement $element): bool
    {
        $doc = new \DOMDocument();
        $doc->documentURI = $element->ownerDocument->documentURI;
        $doc->appendChild($doc->importNode($element, true));

        foreach ((new \DOMXPath($element->ownerDocument))->query('namespace::*') as $namespace) {
            if (null !== $prefix = $element->lookupPrefix($namespace->nodeValue)) {
                $doc->documentElement->setAttributeNS(XML_NAMESPACE_URI, "xmlns:{$prefix}", $namespace->nodeValue);
            }
        }

        return $doc->schemaValidateSource($this->source);
    }

    public function validate($document): bool
    {
        if ($document instanceof \DOMDocument) {
            return $document->schemaValidateSource($this->source);
        }

        if ($document instanceof \DOMElement) {
            return $this->validateElement($document);
        }

        throw new \InvalidArgumentException("Document must be instance of DOMDocument or DOMElement");
    }
}
