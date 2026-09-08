# Quickstart

## Requirements

- PHP 8.5+
- Composer
- Symfony 7.3+ with a Doctrine ORM configuration and mapped entities
- PHP extensions: `intl`, `bcmath`

## Installation

```bash
composer require karross/karross-bundle
```

### Register the bundle (if not using Symfony Flex)

Symfony Flex does this automatically. Otherwise, register the bundle in your
`config/bundles.php` file:

```php
Karross\KarrossBundle::class => ['all' => true],
```

### Import Karross' routes

A recipe will automate this in the future. For now, add the import to your
routes file (all standard Symfony formats are supported):

=== "PHP"

    ```php
    # config/routes.php
    return static function (RoutingConfigurator $routes) {
        $routes->import('.', 'karross.routes');
    };
    ```

=== "YAML"

    ```yaml
    # config/routes.yaml
    karross:
        resource: .
        type: karross.routes
    ```

=== "XML"

    ```xml
    <!-- config/routes.xml -->
    <routes xmlns="http://symfony.com/schema/routing">
        <import resource="." type="karross.routes" />
    </routes>
    ```

## First admin interface

No configuration needed. Suppose your application has an `Article` entity: it
is exposed with two routes:

| Route | Action |
|---|---|
| `/admin/article` | list (index) |
| `/admin/article/1` | single row (show) |

> Composite identifiers are supported.