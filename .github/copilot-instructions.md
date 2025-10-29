# KarrossBundle AI Development Guide

This guide helps AI coding assistants understand the KarrossBundle's architecture, patterns, and conventions for more effective code generation and maintenance.

## Core Philosophy

KarrossBundle embodies several key principles that should guide development:

1. **Zero Configuration First**
   - Bundle must work "out of the box" with just Symfony and Doctrine
   - Default behavior should be immediately useful without any setup
   - Configuration only needed for customization, not basic functionality

2. **Maximum Customization Potential**
   - Every aspect should be customizable when needed
   - Clear extension points for overriding default behavior
   - Layered architecture allowing gradual customization

3. **Precomputation Over Runtime**
   - Maximize performance by computing ahead of time
   - Cache-first approach for template resolution, metadata, all that can be deducted before runtime.
   - Example: Twig templates are resolved during bundle initialization using override conventions

4. **Progressive Simplification**
   - Start with working implementations that are clear and readable
   - Continuously refactor to simplify concepts and implementation
   - Question and refine abstractions as patterns emerge
   - Keep code and templates as simple as possible

When contributing or modifying code, ask yourself:
- Does this work without configuration?
- Can this be precomputed or cached?
- Is there a simpler way to express this?
- Are we maintaining maximum customization potential?

## Project Overview

KarrossBundle is a Symfony bundle that auto-generates admin interfaces for PHP entities. It follows a modular architecture with these key components:

- **Actions/** - CRUD operations and form handling
- **Metadata/** - Entity structure deducted from Doctrine classmetadata and karross configuration
- **Responders/** - Output handling (Twig for now, also JSON later). Will allow to handle more than just the rendering (cache, altering response, redirect...)
- **Routes/** - Dynamic route generation for entities
- **Config/** - Bundle configuration and service definitions

## Key Architecture Patterns

1. **Entity Metadata System**
   - Uses `EntityMetadataBuilder` to create `EntityMetadata` objects
   - `EntityMetadata` contains all what is needed for actions, with no reference to rendering
   - See `src/Metadata/EntityMetadataBuilder.php` for the pattern

2. **Action Flow**
   - Actions (`Create`, `Update`, etc.) handle business logic
   - `ActionContext` allows to pass information at runtime, related to the current action (Request for example)
   - Responders handle output stuff : rendering, caching...
   - Example: `src/Actions/Index.php` shows the standard flow

3. **Dual Output Modes**
   - HTML output using Twig templates (`templates/`)
   - JSON API responses via `JsonResponder`
   - Controlled by bundle config (`apiEnabled()`, `htmlRenderer()`)

## Development Workflows

### Testing
```bash
vendor/bin/phpunit
```

### Key Configuration
```yaml
karross:
    output:
        api: true     # Enable JSON API
        html: 'Twig'  # Twig for now. React, Vue could be additional options for the frontend later.
    entities:
        App\Entity\Article:
            slug: 'post' # Without configuration, the "slug" is the shortname of the entity. Here, it would be 'article'
            actions: ['index', 'create', 'show'] # Enabled actions // To be handled later. For now actions are activated.
```

## Common Tasks

1. **Resolve Entity name conflict**
   - Configure entity slug in `config/packages/karross.yaml`

2. **Customizing Output**
   - Override templates in your app's `templates/bundles/KarrossBundle/`
   - See existing templates for an action in `templates/{actionName}/` for custom behavior
   - use prefixes to target fine-grained templates. 

   Examples: 
   - "entity_article_items.html.twig" can override "items.html.twig" for the article entity.
   - "field_title_entity_article.html.twig" can override "field.html.twig" for the specific field "title" of the article entity.

See `Karross/Twig/TemplateResolver.php` for an exhaustive list of template patterns that can be overridden, and with which precedence.

## Integration Points

1. **Symfony Integration**
   - Bundle configuration via `KarrossExtension`
   - Route loading through `RouteLoader`
   - Service configuration in `services.php`

2. **Template Extension**
   - Custom Twig extensions in `src/Twig/`

## Project-Specific Conventions

1. **Naming**
   - Action classes are named by their function (`Create`, `Show`, etc.)
   - Form classes end in `Form` (`CreateForm`, `EditForm`)
   - Responders implement `ResponderInterface`

2. **Error Handling**
   - Custom exceptions in `Exceptions/` namespace
   - Entity configuration errors use `EntityShortnameException`
