<?php

namespace Karross\Routes;

/**
 * Resolves a Karross route pattern into an actual Symfony route path.
 *
 * Supported tokens:
 *   - {prefix}       the global route prefix (default "admin")
 *   - {slug}         the entity slug
 *   - {identifiers}  one segment per entity identifier (dynamic: supports composite keys)
 *   - {_locale}      passed through as-is (handled by the host's Symfony locale config)
 */
final class RoutePattern
{
    private const TOKENS = ['{prefix}', '{slug}', '{identifiers}', '{_locale}'];

    /**
     * @param string[] $identifiers
     */
    public function resolve(string $pattern, string $prefix, string $slug, array $identifiers): string
    {
        $identifierPath = implode('/', array_map(static fn (string $i) => "{{$i}}", $identifiers));

        return strtr($pattern, [
            '{prefix}' => $prefix,
            '{slug}' => $slug,
            '{identifiers}' => $identifierPath,
        ]);
    }

    public function validate(string $pattern): string
    {
        if (preg_match_all('/\{([a-z_]+)\}/', $pattern, $matches)) {
            foreach ($matches[0] as $token) {
                if (!\in_array($token, self::TOKENS, true)) {
                    throw new \InvalidArgumentException(\sprintf('Unknown token %s in Karross route pattern "%s". Allowed tokens: %s.', $token, $pattern, implode(', ', self::TOKENS)));
                }
            }
        }

        return $pattern;
    }
}
