<?php

namespace Imazed\HelpScoutBeacon;

use Imazed\HelpScoutBeacon\Exceptions\InvalidBeaconData;
use Imazed\HelpScoutBeacon\Support\LogoutFlag;

/**
 * Per-request Beacon state: everything the embed component reads that is not
 * configuration.
 *
 * Call it anywhere before the layout renders — a controller, a middleware, a
 * view composer. State set after the component has rendered is lost.
 */
class Beacon
{
    protected const SESSION_DATA_LIMIT = 20;

    protected bool $suppressed = false;

    /** @var array<string, string|int|float|bool> */
    protected array $sessionData = [];

    public function __construct(protected LogoutFlag $logoutFlag) {}

    /**
     * Keep the Beacon off the page for this request only. The configuration's
     * "enabled" switch is the per-environment version of the same thing.
     */
    public function suppress(): void
    {
        $this->suppressed = true;
    }

    public function suppressed(): bool
    {
        return $this->suppressed;
    }

    /**
     * Conversation metadata attached to whatever the visitor sends from this
     * page. Shown alongside the conversation, not stored on the customer
     * profile; Help Scout caps it at 20 pairs.
     *
     * @param  array<string, string|int|float|bool>  $attributes
     */
    public function sessionData(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                throw InvalidBeaconData::invalidAttributeKey((string) $key);
            }

            if (! is_scalar($value)) {
                throw InvalidBeaconData::invalidAttributeValue($key);
            }
        }

        $merged = array_merge($this->sessionData, $attributes);

        if (count($merged) > self::SESSION_DATA_LIMIT) {
            throw InvalidBeaconData::tooManySessionAttributes(self::SESSION_DATA_LIMIT);
        }

        $this->sessionData = $merged;
    }

    /**
     * @return array<string, string|int|float|bool>
     */
    public function sessionDataAttributes(): array
    {
        return $this->sessionData;
    }

    /**
     * Emit Beacon("logout") on the next rendered page, clearing the identify
     * data and conversation history held in that browser.
     *
     * The package calls this from Laravel's Logout event when the logout
     * listener is enabled; calling it directly is for logouts that do not
     * fire that event.
     */
    public function logout(): void
    {
        $this->logoutFlag->queue();
    }
}
