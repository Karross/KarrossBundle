<?php

namespace Karross\Actions;

use Karross\Actions\REST\{Create, Update, Delete, Index, Show};
use Karross\Actions\UI\{CreateForm, EditForm};

enum Action: string
{
    // REST
    case INDEX = 'index';
    case SHOW = 'show';
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';

    // UI
    case CREATE_FORM = 'create_form';
    case EDIT_FORM = 'edit_form';

    // Extra
    //case IMPORT   = 'import';
    //case EXPORT   = 'export';

    public function httpMethods(): array
    {
        return match($this) {
            self::INDEX, self::SHOW, self::CREATE_FORM, self::EDIT_FORM => ['GET'],
            self::CREATE => ['POST'],
            self::UPDATE => ['PUT', 'PATCH'],
            self::DELETE => ['DELETE'],
        };
    }

    public function routePattern(string $slug, array $identifiers = ['id']): string
    {
        $identifierPath = implode('/', array_map(fn($i) => "{{$i}}", $identifiers));

        return match($this) {
            self::INDEX, self::CREATE => "/admin/$slug",
            self::SHOW, self::DELETE, self::UPDATE => "/admin/$slug/{$identifierPath}",
            self::CREATE_FORM => "/admin/$slug/create",
            self::EDIT_FORM => "/admin/$slug/{$identifierPath}/edit",
        };
    }

    public function controller(): string
    {
        return match($this) {
            self::INDEX => Index::class,
            self::SHOW => Show::class,
            self::CREATE => Create::class,
            self::UPDATE => Update::class,
            self::DELETE => Delete::class,
            self::CREATE_FORM => CreateForm::class,
            self::EDIT_FORM => EditForm::class,
        };
    }
}
