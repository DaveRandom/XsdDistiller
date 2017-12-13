<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller;

use DaveRandom\XsdDistiller\Entities\Registries\ReadOnlyElementRegistry;
use DaveRandom\XsdDistiller\Entities\Registries\ReadOnlyTypeRegistry;

final class Schema
{
    private $types;
    private $rootElements;

    public function __construct(ReadOnlyTypeRegistry $types, ReadOnlyElementRegistry $rootElements)
    {
        $this->types = $types;
        $this->rootElements = $rootElements;
    }

    public function getTypes(): ReadOnlyTypeRegistry
    {
        return $this->types;
    }

    public function getRootElements(): ReadOnlyElementRegistry
    {
        return $this->rootElements;
    }
}
