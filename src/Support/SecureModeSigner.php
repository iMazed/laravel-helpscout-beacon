<?php

namespace Imazed\HelpScoutBeacon\Support;

use Imazed\HelpScoutBeacon\Exceptions\InvalidBeaconData;

/**
 * Computes the Secure Mode signature Help Scout verifies on identify.
 *
 * The signature covers the email address and nothing else; every other
 * identify field is unsigned. This class runs server-side only — the key must
 * never reach a view or a response.
 */
class SecureModeSigner
{
    public function __construct(protected ?string $key = null) {}

    public function configured(): bool
    {
        return is_string($this->key) && $this->key !== '';
    }

    /**
     * The signature for an email address: hex-encoded HMAC-SHA256, as Help
     * Scout's verification expects.
     */
    public function sign(string $email): string
    {
        if (! $this->configured()) {
            throw InvalidBeaconData::secureModeKeyMissing();
        }

        return hash_hmac('sha256', $email, (string) $this->key);
    }
}
