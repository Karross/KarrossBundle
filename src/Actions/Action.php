<?php

namespace Karross\Actions;

use Karross\Actions\{Index, Show};
use Karross\Metadata\EntityMetadata;

enum Action: string
{
    // REST - actions currently implemented
    case INDEX = 'index';
    case SHOW = 'show';

    // TODO: not implemented yet
    // case CREATE     = 'create';
    // case UPDATE     = 'update';
    // case DELETE     = 'delete';

    // TODO: form rendering not implemented yet
    // case CREATE_FORM = 'create_form';
    // case EDIT_FORM   = 'edit_form';

    // Extra
    //case IMPORT   = 'import';
    //case EXPORT   = 'export';

    public function httpMethods(): array
    {
        return match($this) {
            self::INDEX, self::SHOW => ['GET'],
        };
    }

    public function routePattern(string $slug, array $identifiers = ['id']): string
    {
        $identifierPath = implode('/', array_map(fn($i) => "{{$i}}", $identifiers));

        return match($this) {
            self::INDEX => "/admin/$slug",
            self::SHOW => "/admin/$slug/{$identifierPath}",
        };
    }

    public function controller(): string
    {
        return match($this) {
            self::INDEX => Index::class,
            self::SHOW => Show::class,
        };
    }
}
