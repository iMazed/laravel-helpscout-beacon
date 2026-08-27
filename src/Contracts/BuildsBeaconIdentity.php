<?php

namespace Imazed\HelpScoutBeacon\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Imazed\HelpScoutBeacon\Identity\BeaconIdentity;

/**
 * Decides who the current visitor is to Help Scout.
 */
interface BuildsBeaconIdentity
{
    /**
     * The identity to announce for this visitor, or null to stay anonymous.
     *
     * @param  Authenticatable|null  $user  The user on the configured guard, if any.
     */
    public function build(?Authenticatable $user): ?BeaconIdentity;
}
