<?php

namespace Imazed\HelpScoutBeacon\Facades;

use Illuminate\Support\Facades\Facade;
use Imazed\HelpScoutBeacon\Beacon;

/**
 * @method static void suppress()
 * @method static void sessionData(array $attributes)
 * @method static void logout()
 *
 * @see Beacon
 */
class HelpScoutBeacon extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Beacon::class;
    }
}
