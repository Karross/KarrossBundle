<?php

namespace Karross\Responders;

use Karross\Actions\ActionContext;
use Symfony\Component\HttpFoundation\Response;

class ResponderManager
{
    public function __construct(private $responders) {}

    public function getResponse(ActionContext $actionContext, $data): Response {
        foreach ($this->responders as $responder) {
            if ($responder->supports($actionContext)) {
                return $responder->getResponse($actionContext, $data);
            }
        }
    }
}
