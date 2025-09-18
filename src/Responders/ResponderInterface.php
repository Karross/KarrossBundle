<?php

namespace Karross\Responders;

use Karross\Actions\ActionContext;
use Symfony\Component\HttpFoundation\Response;

interface ResponderInterface
{
    public function supports(ActionContext $actionContext): bool;

    public function getResponse(ActionContext $actionContext, $data): Response;
}
