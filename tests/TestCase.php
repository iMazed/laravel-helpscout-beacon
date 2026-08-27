<?php

namespace Imazed\HelpScoutBeacon\Tests;

use Illuminate\Auth\GenericUser;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Support\Facades\Route;
use Imazed\HelpScoutBeacon\HelpScoutBeaconServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected string $beaconId = 'test-beacon-id';

    protected string $secureKey = 'test-secure-key';

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [HelpScoutBeaconServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('helpscout-beacon.beacon_id', $this->beaconId);
        $app['config']->set('helpscout-beacon.secure_mode.key', $this->secureKey);
    }

    /**
     * A page whose only content is the embed component.
     *
     * Queued cookies only reach the response through the middleware, so the
     * route carries it; the logout flow is untestable without it.
     */
    protected function embedRoute(): void
    {
        Route::middleware(AddQueuedCookiesToResponse::class)->get('/page', function () {
            return view('embed-page');
        });

        view()->addLocation(__DIR__.'/Fixtures/views');
    }

    /**
     * An Authenticatable the default identity builder can read.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function user(array $attributes = []): GenericUser
    {
        return new GenericUser($attributes + [
            'id' => 1,
            'email' => 'ada@example.com',
            'name' => 'Ada Lovelace',
            'password' => null,
        ]);
    }
}
