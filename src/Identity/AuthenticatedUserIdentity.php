<?php

namespace Imazed\HelpScoutBeacon\Identity;

use Illuminate\Contracts\Auth\Authenticatable;
use Imazed\HelpScoutBeacon\Contracts\BuildsBeaconIdentity;

/**
 * The shipped default: email and name straight off the authenticated user.
 *
 * Unlike a custom builder, this one must work on any model without throwing,
 * so an over-long name is truncated to Help Scout's limit rather than
 * refused; write your own builder when that tradeoff is wrong for you.
 */
class AuthenticatedUserIdentity implements BuildsBeaconIdentity
{
    public function build(?Authenticatable $user): ?BeaconIdentity
    {
        $email = $user?->email ?? null;

        if (! is_string($email) || trim($email) === '') {
            return null;
        }

        $identity = BeaconIdentity::for($email);

        $name = $user->name ?? null;

        if (is_string($name) && $name !== '') {
            $identity->name(mb_substr($name, 0, 80));
        }

        return $identity;
    }
}
