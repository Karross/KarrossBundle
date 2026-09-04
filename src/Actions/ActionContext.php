<?php

namespace Karross\Actions;

use Symfony\Component\HttpFoundation\Request;

readonly class ActionContext
{
    public function __construct(
        public Request $request,
        public string $action,
        public string $slug,
    ) {
    }
}
