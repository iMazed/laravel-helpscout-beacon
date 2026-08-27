# Laravel Help Scout Beacon

Embed the Help Scout Beacon in your Laravel application, with Secure Mode signatures generated server-side, so logged-in customers are identified safely and logged-out ones stop being identified at all.

Companion to [laravel-helpscout-sidebar](https://github.com/iMazed/laravel-helpscout-sidebar): the sidebar shows agents your application's data inside Help Scout; this package shows customers Help Scout inside your application.

---

## Contents

[Requirements](#requirements) · [Installation](#installation) · [Identification](#identification) · [Session data and runtime control](#session-data-and-runtime-control)

---

## Requirements

- PHP `8.3+`
- Laravel `12.x` or `13.x`
- A Help Scout Beacon (Manage → Beacons); no API access or OAuth app needed

---

## Installation

```bash
composer require imazed/laravel-helpscout-beacon
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=helpscout-beacon-config
```

Add your credentials to `.env`:

```dotenv
HELPSCOUT_BEACON_ID=your-beacon-id
HELPSCOUT_BEACON_SECURE_KEY=your-secure-mode-key
```

Both come from Help Scout under **Manage → Beacons**: the ID from the installation instructions, the key from the Contact tab when you enable Secure Mode.

Then drop the component into your layout, once, before `</body>`:

```blade
<x-helpscout-beacon />
```

That renders the embed script, initializes the Beacon, and, when someone is logged in, identifies them with a server-signed signature. Guests get the anonymous Beacon.

Attributes pass through to the rendered `<script>` tag, so a Content Security Policy nonce is `<x-helpscout-beacon :nonce="$cspNonce" />`. If your CSP restricts sources, allow `https://beacon-v2.helpscout.net`.

Beacon display options (colors, position, labels, messaging) go in the `config` array of `config/helpscout-beacon.php`, rendered verbatim as `Beacon('config', ...)`. The accepted keys are [Help Scout's](https://developer.helpscout.com/beacon-2/web/javascript-api/#beaconconfig-formobject), not the package's, so new options work without a package update.

## Identification

Out of the box, the user on your default auth guard is identified by their `email` and `name` attributes; guests stay anonymous. Set `guard` in `config/helpscout-beacon.php` if your customers authenticate elsewhere. The shipped builder is deliberately forgiving: no email means no identify rather than an error, and an over-long name is truncated to Help Scout's limit rather than refused, because the default must work against any user model without taking the page down.

To announce more, implement `BuildsBeaconIdentity` and point `identity` in the config at your class. Return `null` to keep a visitor anonymous; the builder resolves through the container, so constructor dependencies work as usual.

```php
use Illuminate\Contracts\Auth\Authenticatable;
use Imazed\HelpScoutBeacon\Contracts\BuildsBeaconIdentity;
use Imazed\HelpScoutBeacon\Identity\BeaconIdentity;

class CustomerBeaconIdentity implements BuildsBeaconIdentity
{
    public function build(?Authenticatable $user): ?BeaconIdentity
    {
        if ($user === null) {
            return null;
        }

        return BeaconIdentity::for($user->email)
            ->name($user->name)
            ->company($user->team->name)
            ->avatar($user->avatar_url)
            ->attribute('plan', $user->plan)
            ->attribute('customer-since', $user->created_at)
            ->companyProperty('industry', $user->team->industry);
    }
}
```

### Validation

`BeaconIdentity` enforces Help Scout's documented limits when a value is set, throwing `InvalidBeaconData` immediately rather than letting Help Scout truncate or drop the value later:

| Field | Rule |
| --- | --- |
| `email` | required, non-empty |
| `name` | ≤ 80 characters |
| `company`, `jobTitle` | ≤ 60 characters |
| `avatar` | valid URL, ≤ 200 characters |
| attribute keys | letters, numbers, hyphens, underscores; ≤ 100 characters; case-sensitive |
| attribute text values | ≤ 255 characters |

Attribute keys may not shadow the identify object's own keys (`name`, `email`, `signature`, `company`, `jobTitle`, `avatar`, `companyProperties`). The one that matters is `signature`: an attribute must never be able to overwrite it. The rest are refused for the same reason rather than silently colliding.

Two conversions happen for you: `DateTimeInterface` values become `YYYY-MM-DD` strings, the format Help Scout's date properties expect, and `null` passes through untouched because null is how a property's current value is removed. Custom attributes must already exist as properties in Help Scout (Manage → Customer Properties); the package validates shape, not existence.

## Session data and runtime control

Session data is conversation metadata: it rides along with whatever the visitor sends from the current page and shows next to that conversation, without being stored on the customer profile. Set it anywhere before the layout renders (a controller, a middleware, a view composer):

```php
use Imazed\HelpScoutBeacon\Facades\HelpScoutBeacon;

HelpScoutBeacon::sessionData([
    'order' => $order->number,
    'cart-total' => $cart->total,
]);
```

Repeated calls merge; Help Scout caps the result at 20 pairs and the package enforces that cap. Like custom attributes, session data is unsigned: display context for agents, not verified fact (see [Security](#security)).

```php
HelpScoutBeacon::suppress();   // no Beacon on this page (this request only)
HelpScoutBeacon::logout();     // for logouts that skip Laravel's Logout event
```

`suppress()` is the per-page switch; the config's `enabled` is the per-environment one. Logout normally needs no call at all, because the package listens for Laravel's `Logout` event; `logout()` is for token-based or custom flows that end a session without firing it.

## License

MIT. See [LICENSE.md](LICENSE.md).
