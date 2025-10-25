<?php

namespace Karross\Metadata;
interface PropertyInterface
{
    public function isField(): bool;
    public function isAssociation(): bool;
}
