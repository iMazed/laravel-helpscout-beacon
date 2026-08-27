<?php

namespace Imazed\HelpScoutBeacon\Listeners;

use Illuminate\Auth\Events\Logout;
use Imazed\HelpScoutBeacon\Beacon;

/**
 * Turns a Laravel logout into a queued Beacon("logout"), so the browser does
 * not keep serving the previous user's conversation history.
 */
class QueueBeaconLogout
{
    public function __construct(protected Beacon $beacon) {}

    public function handle(Logout $event): void
    {
        $this->beacon->logout();
    }
}
