<?php

use Imazed\HelpScoutBeacon\Identity\AuthenticatedUserIdentity;

return [

    /*
    |--------------------------------------------------------------------------
    | Beacon ID
    |--------------------------------------------------------------------------
    |
    | Found in Help Scout under Manage → Beacons, in your Beacon's installation
    | instructions. Without it the component renders nothing at all, so a
    | missing ID cannot half-load a widget.
    |
    */

    'beacon_id' => env('HELPSCOUT_BEACON_ID'),

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | The per-environment switch. Turn it off in environments that should not
    | open real conversations — CI, staging with production-like data — instead
    | of removing the component from your layout.
    |
    */

    'enabled' => env('HELPSCOUT_BEACON_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Secure Mode
    |--------------------------------------------------------------------------
    |
    | The key Help Scout gives you when enabling Secure Mode on the Beacon
    | (Manage → Beacons → your Beacon → Contact). This package signs the
    | visitor's email address with it, server-side, so Help Scout can trust
    | that the visitor is who your application says they are.
    |
    | The signature covers the email address and nothing else. Every other
    | identify field — name, company, custom attributes — is unsigned, and
    | anything unsigned can be replaced from the browser console. Treat those
    | fields as display data, never as verified identity.
    |
    | With no key configured, identification is skipped rather than sent
    | unsigned: an unsigned identify only works when Secure Mode is off in
    | Help Scout, which lets anyone impersonate any customer and read their
    | conversation history. Set "allow_unsigned" to true only if you have
    | decided, deliberately, that your Beacon has nothing worth protecting.
    |
    */

    'secure_mode' => [
        'key' => env('HELPSCOUT_BEACON_SECURE_KEY'),
        'allow_unsigned' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Identity
    |--------------------------------------------------------------------------
    |
    | Who the current visitor is. The shipped builder reads the user on the
    | guard below (null means your default guard) and announces their email
    | and name. Point "identity" at your own BuildsBeaconIdentity
    | implementation to add company, avatar, or custom attributes.
    |
    */

    'guard' => null,

    'identity' => AuthenticatedUserIdentity::class,

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    |
    | When a user logs out of your application, the Beacon in their browser
    | still holds their identity and conversation history — a real problem on
    | shared machines. With this enabled, the package listens for Laravel's
    | Logout event and emits Beacon("logout") on the next rendered page.
    |
    | "end_active_chat" also closes any chat in progress, which is the safe
    | default: a chat that outlives the session belongs to nobody.
    |
    */

    'logout' => [
        'enabled' => true,
        'end_active_chat' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Beacon Config Passthrough
    |--------------------------------------------------------------------------
    |
    | Rendered verbatim as Beacon("config", ...) after init. Display options,
    | colors, messaging settings, and label translations go here; the accepted
    | keys are Help Scout's, documented at:
    |
    |   https://developer.helpscout.com/beacon-2/web/javascript-api/#beaconconfig-formobject
    |
    */

    'config' => [],

];
