<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller;

abstract class Registry implements \IteratorAggregate
{
    protected $itemsWithNames;
    protected $itemValuesOnly;

    /**
     * @param EntityName $name
     * @param mixed $value
     * @return mixed
     */
    protected function addItem(EntityName $name, $value)
    {
        \assert(!isset($this->itemsWithNames[(string)$name]), new \Error("Registry already contains name: {$name}"));

        $this->itemsWithNames[(string)$name] = [$name, $value];
        return $this->itemValuesOnly[(string)$name] = $value;
    }

    /**
     * @param EntityName $name
     * @return mixed
     * @throws \OutOfBoundsException
     */
    protected function getItem(EntityName $name)
    {
        if (!isset($this->itemValuesOnly[(string)$name])) {
            throw new \OutOfBoundsException("Unregistered name: {$name}");
        }

        return $this->itemValuesOnly[(string)$name];
    }

    /**
     * @param EntityName $name
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function contains(EntityName $name): bool
    {
        return isset($this->itemsWithNames[(string)$name]);
    }

    public function getIterator(): \Iterator
    {
        foreach ($this->itemsWithNames as [$name, $item]) {
            yield $name => $item;
        }
    }

    public function toArray(): array
    {
        return $this->itemValuesOnly;
    }
}
