<?php

namespace Karross\Metadata\Computed;

class FieldLabel
{
    public function __construct(public string $label, public string $path, public int $depth = 0, public int $numberOfLeaves = 1, public bool $isLeaf = false)
    {
    }
}
