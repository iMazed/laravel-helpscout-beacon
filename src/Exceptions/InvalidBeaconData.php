<?php

namespace Imazed\HelpScoutBeacon\Exceptions;

use InvalidArgumentException;

/**
 * A payload that would be rejected or truncated by Help Scout, refused here
 * instead so the mistake surfaces in development rather than in a customer
 * profile.
 */
class InvalidBeaconData extends InvalidArgumentException
{
    public static function emptyEmail(): self
    {
        return new self('A Beacon identity requires a non-empty email address.');
    }

    public static function tooLong(string $field, int $limit): self
    {
        return new self("Beacon identify field [{$field}] exceeds Help Scout's limit of {$limit} characters.");
    }

    public static function invalidAvatarUrl(string $url): self
    {
        return new self("Beacon avatar [{$url}] is not a valid URL.");
    }

    public static function invalidAttributeKey(string $key): self
    {
        return new self("Beacon attribute key [{$key}] is invalid: up to 100 characters of letters, numbers, hyphens and underscores.");
    }

    public static function reservedAttributeKey(string $key): self
    {
        return new self("Beacon attribute key [{$key}] is reserved by Help Scout's identify object.");
    }

    public static function attributeValueTooLong(string $key, int $limit): self
    {
        return new self("Beacon attribute [{$key}] exceeds Help Scout's limit of {$limit} characters.");
    }

    public static function invalidAttributeValue(string $key): self
    {
        return new self("Beacon attribute [{$key}] must be a scalar, a DateTimeInterface, or null.");
    }

    public static function tooManySessionAttributes(int $limit): self
    {
        return new self("Beacon session data is limited to {$limit} attributes by Help Scout.");
    }

    public static function secureModeKeyMissing(): self
    {
        return new self('Cannot sign a Beacon identity: no Secure Mode key is configured.');
    }
}
