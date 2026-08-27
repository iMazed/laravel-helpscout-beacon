<?php

namespace Imazed\HelpScoutBeacon\View\Components;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;
use Imazed\HelpScoutBeacon\Beacon;
use Imazed\HelpScoutBeacon\Contracts\BuildsBeaconIdentity;
use Imazed\HelpScoutBeacon\Support\BeaconConfig;
use Imazed\HelpScoutBeacon\Support\JavaScript;
use Imazed\HelpScoutBeacon\Support\LogoutFlag;
use Imazed\HelpScoutBeacon\Support\SecureModeSigner;
use Psr\Log\LoggerInterface;

/**
 * <x-helpscout-beacon /> — the embed script, placed once before </body>.
 *
 * Attributes pass through to the <script> tag, so a CSP nonce is
 * <x-helpscout-beacon :nonce="$nonce" />.
 */
class BeaconEmbed extends Component
{
    public function __construct(
        protected Beacon $beacon,
        protected BeaconConfig $config,
        protected BuildsBeaconIdentity $identity,
        protected SecureModeSigner $signer,
        protected LogoutFlag $logoutFlag,
        protected AuthFactory $auth,
        protected Request $request,
        protected LoggerInterface $logger,
    ) {}

    public function render(): View
    {
        return view('helpscout-beacon::components.beacon', ['calls' => $this->calls()]);
    }

    /**
     * The Beacon() calls this page makes, in order, as rendered JavaScript.
     * Empty means the component renders nothing at all.
     *
     * Protected on purpose: a public component method becomes a lazy view
     * variable that shadows the array render() passes, so the view would
     * receive an object where the emptiness check expects an array.
     *
     * @return array<int, string>
     */
    protected function calls(): array
    {
        if (! $this->config->renderable() || $this->beacon->suppressed()) {
            return [];
        }

        $calls = [$this->call('init', $this->config->beaconId)];

        if ($this->config->jsConfig !== []) {
            $calls[] = $this->call('config', $this->config->jsConfig);
        }

        // Logout runs before identify: when one user signs in right after
        // another signed out, the old identity must be gone first.
        if ($this->config->logoutEnabled && $this->logoutFlag->due($this->request)) {
            $this->logoutFlag->clear();
            $calls[] = $this->call('logout', ['endActiveChat' => $this->config->endActiveChatOnLogout]);
        }

        if (($payload = $this->identifyPayload()) !== null) {
            $calls[] = $this->call('identify', $payload);
        }

        if ($this->beacon->sessionDataAttributes() !== []) {
            $calls[] = $this->call('session-data', $this->beacon->sessionDataAttributes());
        }

        return $calls;
    }

    /**
     * The identify object with its Secure Mode signature, or null when this
     * visitor stays anonymous.
     *
     * No key and no explicit allow_unsigned means no identify at all: an
     * unsigned identify is an impersonation primitive, so it has to be opted
     * into, never fallen into.
     *
     * @return array<string, mixed>|null
     */
    protected function identifyPayload(): ?array
    {
        $identity = $this->identity->build($this->auth->guard($this->config->guard)->user());

        if ($identity === null) {
            return null;
        }

        if ($this->signer->configured()) {
            return $identity->toArray() + ['signature' => $this->signer->sign($identity->email())];
        }

        if ($this->config->allowUnsigned) {
            return $identity->toArray();
        }

        $this->logger->warning(
            'Help Scout Beacon: an identity was built but no Secure Mode key is configured, so identify was skipped. '
            .'Set HELPSCOUT_BEACON_SECURE_KEY, or set secure_mode.allow_unsigned to opt out deliberately.',
        );

        return null;
    }

    protected function call(string $method, mixed ...$args): string
    {
        $encoded = array_map(JavaScript::encode(...), [$method, ...$args]);

        return 'Beacon('.implode(', ', $encoded).');';
    }
}
