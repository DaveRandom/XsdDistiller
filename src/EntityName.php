<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller;

class EntityName
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
