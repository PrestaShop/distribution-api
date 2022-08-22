<?php

declare(strict_types=1);

namespace App;

use App\Model\Module;
use App\Model\Version;
use ArrayAccess;
use Countable;
use Iterator;

/**
 * @implements Iterator<int, Module>
 * @implements ArrayAccess<int, Module>
 */
class ModuleCollection implements Iterator, ArrayAccess, Countable
{
    /** @var Module[] */
    private array $modules;

    public function __construct(Module ...$modules)
    {
        $this->modules = $modules;
    }

    /**
     * @return Module|false
     */
    public function current(): mixed
    {
        return current($this->modules);
    }

    public function next(): void
    {
        next($this->modules);
    }

    /**
     * @return int
     */
    public function key(): mixed
    {
        return (int) key($this->modules);
    }

    public function valid(): bool
    {
        return current($this->modules) !== false;
    }

    public function rewind(): void
    {
        reset($this->modules);
    }

    /**
     * @param int $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->modules[$offset]);
    }

    /**
     * @param int $offset
     *
     * @return Module
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->modules[$offset];
    }

    /**
     * @param int $offset
     * @param Module $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->modules[$offset] = $value;
    }

    /**
     * @param int $offset
     */
    public function offsetUnset(mixed $offset)
    {
        unset($this->modules[$offset]);
    }

    public function contains(string $moduleName, Version $version): bool
    {
        foreach ($this->modules as $module) {
            if ($module->getName() !== $moduleName) {
                continue;
            }
            foreach ($module->getVersions() as $v) {
                if ($v->getTag() === $version->getTag()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function count(): int
    {
        return count($this->modules);
    }
}
