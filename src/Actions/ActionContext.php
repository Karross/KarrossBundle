<?php

namespace Karross\Actions;

use Karross\Config\KarrossConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

readonly class ActionContext
{
    public function __construct(
        public Request $request,
        public string $action,
        public string $slug,
    ) {}
}
