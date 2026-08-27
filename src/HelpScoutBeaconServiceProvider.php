<?php

namespace Imazed\HelpScoutBeacon;

use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Cookie\QueueingFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Imazed\HelpScoutBeacon\Contracts\BuildsBeaconIdentity;
use Imazed\HelpScoutBeacon\Listeners\QueueBeaconLogout;
use Imazed\HelpScoutBeacon\Support\BeaconConfig;
use Imazed\HelpScoutBeacon\Support\LogoutFlag;
use Imazed\HelpScoutBeacon\Support\SecureModeSigner;
use Imazed\HelpScoutBeacon\View\Components\BeaconEmbed;

class HelpScoutBeaconServiceProvider extends ServiceProvider
{
    /**
     * Register package bindings.
     *
     * Everything is wired here rather than read from `config()` inside the
     * classes themselves, so each piece can be constructed directly in a test
     * without booting the framework's configuration.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/helpscout-beacon.php', 'helpscout-beacon');

        $this->app->singleton(BeaconConfig::class, function (Application $app): BeaconConfig {
            return BeaconConfig::fromArray((array) $app['config']->get('helpscout-beacon', []));
        });

        $this->app->singleton(SecureModeSigner::class, function (Application $app): SecureModeSigner {
            return new SecureModeSigner($app['config']->get('helpscout-beacon.secure_mode.key'));
        });

        $this->app->singleton(LogoutFlag::class, function (Application $app): LogoutFlag {
            return new LogoutFlag($app->make(QueueingFactory::class));
        });

        $this->app->singleton(Beacon::class, function (Application $app): Beacon {
            return new Beacon($app->make(LogoutFlag::class));
        });

        $this->app->bind(BuildsBeaconIdentity::class, function (Application $app): BuildsBeaconIdentity {
            return $app->make($app['config']->get('helpscout-beacon.identity'));
        });
    }

    /**
     * Register the component, the logout listener, and publishable assets.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'helpscout-beacon');

        Blade::component(BeaconEmbed::class, 'helpscout-beacon');

        if ($this->app['config']->get('helpscout-beacon.logout.enabled', true)) {
            Event::listen(Logout::class, QueueBeaconLogout::class);
        }

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/helpscout-beacon.php' => config_path('helpscout-beacon.php'),
        ], 'helpscout-beacon-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/helpscout-beacon'),
        ], 'helpscout-beacon-views');
    }
}
