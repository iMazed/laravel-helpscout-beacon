<?php

namespace Imazed\HelpScoutBeacon\Support;

/**
 * The package configuration as a value object, so every consumer can be
 * constructed directly in a test without booting the framework's
 * configuration.
 */
class BeaconConfig
{
    /**
     * @param  array<string, mixed>  $jsConfig  Rendered verbatim as Beacon("config", ...).
     */
    public function __construct(
        public readonly ?string $beaconId,
        public readonly bool $enabled = true,
        public readonly ?string $guard = null,
        public readonly bool $allowUnsigned = false,
        public readonly bool $logoutEnabled = true,
        public readonly bool $endActiveChatOnLogout = true,
        public readonly array $jsConfig = [],
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        $secure = (array) ($config['secure_mode'] ?? []);
        $logout = (array) ($config['logout'] ?? []);

        return new self(
            beaconId: $config['beacon_id'] ?? null,
            enabled: (bool) ($config['enabled'] ?? true),
            guard: $config['guard'] ?? null,
            allowUnsigned: (bool) ($secure['allow_unsigned'] ?? false),
            logoutEnabled: (bool) ($logout['enabled'] ?? true),
            endActiveChatOnLogout: (bool) ($logout['end_active_chat'] ?? true),
            jsConfig: (array) ($config['config'] ?? []),
        );
    }

    /**
     * Whether the embed can render at all in this environment.
     */
    public function renderable(): bool
    {
        return $this->enabled && is_string($this->beaconId) && $this->beaconId !== '';
    }
}
