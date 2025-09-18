<?php

namespace Karross\Exceptions;

use Symfony\Component\Config\Exception\LoaderLoadException;

class EntityShortnameException extends LoaderLoadException
{
    /**
     * @param mixed           $resource       The resource that could not be imported
     * @param string          $message        The message to help the user during installation
     * @param string|null     $sourceResource The original resource importing the new resource
     * @param int             $code           The error code
     * @param \Throwable|null $previous       A previous exception
     * @param string|null     $type           The type of resource
     */
    public function __construct(mixed $resource, string $message, ?string $sourceResource = null, int $code = 0, ?\Throwable $previous = null, ?string $type = null)
    {
        parent::__construct($resource, $sourceResource, $code, $previous, $type);
        $this->message = $message;
    }
}

