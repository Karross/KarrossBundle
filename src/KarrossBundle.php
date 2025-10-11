<?php

namespace Karross;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class KarrossBundle extends Bundle
{
    public function getPath(): string
    {
        $reflected = new \ReflectionObject($this);
        /** @var non-empty-string $fileName */
        $fileName = $reflected->getFileName();

        return \dirname($fileName, 2);
    }
}
