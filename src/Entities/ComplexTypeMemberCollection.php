<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Entities;

use DaveRandom\XsdDistiller\FullyQualifiedName;

final class ComplexTypeMemberCollection implements \IteratorAggregate, \ArrayAccess, \Countable
{
    private $members = [];

    public function __construct(\ArrayObject $members)
    {
        /** @var Element $member */
        foreach ($members as $member) {
            $name = $member->getName();

            $entityName = $name instanceof FullyQualifiedName
                ? $name->getEntityName()
                : (string)$name;

            $this->members[$entityName] = $member;
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->members);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->members[$offset]);
    }

    public function offsetGet($key): Element
    {
        if (!isset($this->members[$key])) {
            throw new \OutOfBoundsException("Invalid key: {$key}");
        }

        return $this->members[$key];
    }

    public function offsetSet($offset, $value)
    {
        throw new \LogicException(ComplexTypeMemberCollection::class . " is read-only");
    }

    public function offsetUnset($offset)
    {
        throw new \LogicException(ComplexTypeMemberCollection::class . " is read-only");
    }

    public function count(): int
    {
        return \count($this->members);
    }
}
