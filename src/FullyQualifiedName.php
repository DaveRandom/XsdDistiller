<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller;

final class FullyQualifiedName extends EntityName
{
    private static $anyTypeName;

    private $namespace;
    private $entityName;

    public static function getAnyTypeName()
    {
        return self::$anyTypeName ?? (self::$anyTypeName = new FullyQualifiedName(XML_SCHEMA_URI, 'anyType'));
    }

    public function __construct(string $namespace, string $entityName)
    {
        parent::__construct("{$entityName}@{$namespace}");

        $this->namespace = $namespace;
        $this->entityName = $entityName;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }
}
