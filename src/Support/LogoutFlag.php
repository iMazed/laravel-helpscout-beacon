<?php

namespace Imazed\HelpScoutBeacon\Support;

use Illuminate\Contracts\Cookie\QueueingFactory;
use Illuminate\Http\Request;

/**
 * Remembers, across the logout redirect, that Beacon("logout") still has to
 * run in the browser.
 *
 * A cookie rather than the session on purpose: logging out invalidates the
 * session, so anything flashed there dies before the next page renders, while
 * a queued cookie rides the redirect response and survives. Only presence is
 * read, never the value, so the cookie carries nothing worth forging.
 */
class LogoutFlag
{
    public const COOKIE = 'helpscout_beacon_logout';

    public function __construct(protected QueueingFactory $cookies) {}

    public function queue(): void
    {
        $this->cookies->queue($this->cookies->make(self::COOKIE, '1'));
    }

    public function due(Request $request): bool
    {
        return $request->cookies->has(self::COOKIE);
    }

    public function clear(): void
    {
        $this->cookies->queue($this->cookies->forget(self::COOKIE));
    }
}
