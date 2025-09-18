<?php

namespace Karross\Responders;

use Karross\Actions\ActionContext;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsonResponder implements ResponderInterface
{
    public function supports(ActionContext $actionContext): bool
    {
        return false;
    }

    public function getResponse(ActionContext $actionContext, $data): JsonResponse {
        return new JsonResponse($data);
    }
}
