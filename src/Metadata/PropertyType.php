<?php

namespace Karross\Metadata;

/**
 * Represents the semantic type of a property.
 * Used for formatter resolution and template resolution.
 */
enum PropertyType: string
{
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Float = 'float';
    case String = 'string';
    case Text = 'text';
    case Date = 'date';
    case Time = 'time';
    case DateTime = 'datetime';
    case Enum = 'enum';
    case Array = 'array';
    case Single = 'single';        // Single-valued association
    case Collection = 'collection'; // Collection-valued association
    case Unknown = 'unknown';
}

