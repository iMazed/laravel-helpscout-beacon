<?php

namespace Imazed\HelpScoutBeacon\Tests\Feature;

use Illuminate\Auth\Events\Logout;
use Imazed\HelpScoutBeacon\Support\LogoutFlag;
use Imazed\HelpScoutBeacon\Tests\TestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;

class LogoutFlowTest extends TestCase
{
    protected function usesDisabledLogout($app): void
    {
        $app['config']->set('helpscout-beacon.logout.enabled', false);
    }

    #[Test]
    public function it_queues_the_flag_when_laravel_fires_its_logout_event(): void
    {
        event(new Logout('web', $this->user()));

        $this->assertTrue($this->app['cookie']->hasQueued(LogoutFlag::COOKIE));
    }

    #[Test]
    public function it_emits_beacon_logout_on_the_next_page_and_clears_the_flag(): void
    {
        $this->embedRoute();

        $this->withUnencryptedCookie(LogoutFlag::COOKIE, '1')
            ->get('/page')
            ->assertSee('Beacon("logout", {"endActiveChat":true});', false)
            ->assertCookieExpired(LogoutFlag::COOKIE);
    }

    #[Test]
    public function it_can_leave_an_active_chat_running(): void
    {
        config()->set('helpscout-beacon.logout.end_active_chat', false);
        $this->embedRoute();

        $this->withUnencryptedCookie(LogoutFlag::COOKIE, '1')
            ->get('/page')
            ->assertSee('Beacon("logout", {"endActiveChat":false});', false);
    }

    #[Test]
    public function it_does_not_emit_logout_without_the_flag(): void
    {
        $this->embedRoute();

        $this->get('/page')->assertDontSee('Beacon("logout"', false);
    }

    #[Test]
    #[DefineEnvironment('usesDisabledLogout')]
    public function it_ignores_the_logout_event_when_the_listener_is_disabled(): void
    {
        event(new Logout('web', $this->user()));

        $this->assertFalse($this->app['cookie']->hasQueued(LogoutFlag::COOKIE));
    }

    #[Test]
    #[DefineEnvironment('usesDisabledLogout')]
    public function it_ignores_a_stray_flag_when_logout_handling_is_disabled(): void
    {
        $this->embedRoute();

        $this->withUnencryptedCookie(LogoutFlag::COOKIE, '1')
            ->get('/page')
            ->assertDontSee('Beacon("logout"', false);
    }
}
