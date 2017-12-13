<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller;

final class FullyQualifiedName extends EntityName
{
    private $namespace;
    private $entityName;

    public function __construct(string $namespace, string $entityName)
    {
        parent::__construct("{$namespace}\0{$entityName}");

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
