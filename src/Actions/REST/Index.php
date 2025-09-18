<?php

namespace Karross\Actions\REST;

use Symfony\Component\HttpFoundation\Response;

class Index
{
    public function __invoke(): Response
    {
        return new Response('The index page');
    }
}
