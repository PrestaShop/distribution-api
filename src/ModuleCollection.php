<?php

declare(strict_types=1);

namespace App;

use App\Model\Module;
use App\Model\Version;
use ArrayAccess;
use Iterator;

class ModuleCollection implements Iterator, ArrayAccess
{
    /** @var Module[] */
    private array $modules;

    public function __construct(Module...$modules)
    {
        $this->modules = $modules;
    }

    public function current(): mixed
    {
        return current($this->modules);
    }

    public function next(): void
    {
        next($this->modules);
    }

    public function key(): mixed
    {
        return key($this->modules);
    }

    public function valid(): bool
    {
        return current($this->modules) !== false;
    }

    public function rewind(): void
    {
        reset($this->modules);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->modules[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->modules[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->modules[$offset] = $value;
    }

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
}