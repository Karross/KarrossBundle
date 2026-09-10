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

## Access the admin

Visit `/admin`.

Want `/dashboard` instead? See [Changing the URL structure](customization/routes.md#changing-the-url-structure).