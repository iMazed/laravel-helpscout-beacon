<?php

namespace Imazed\HelpScoutBeacon\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Imazed\HelpScoutBeacon\Beacon;
use Imazed\HelpScoutBeacon\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EmbedRenderingTest extends TestCase
{
    #[Test]
    public function it_loads_the_beacon_and_initializes_it_with_the_configured_id(): void
    {
        $this->embedRoute();

        $this->get('/page')
            ->assertOk()
            ->assertSee('beacon-v2.helpscout.net', false)
            ->assertSee('Beacon("init", "test-beacon-id");', false);
    }

    #[Test]
    public function it_renders_nothing_without_a_beacon_id(): void
    {
        config()->set('helpscout-beacon.beacon_id', null);
        $this->embedRoute();

        $this->get('/page')->assertOk()->assertDontSee('Beacon(', false);
    }

    #[Test]
    public function it_renders_nothing_when_disabled(): void
    {
        config()->set('helpscout-beacon.enabled', false);
        $this->embedRoute();

        $this->get('/page')->assertOk()->assertDontSee('Beacon(', false);
    }

    #[Test]
    public function it_renders_nothing_when_suppressed_for_the_request(): void
    {
        $this->embedRoute();
        $this->app->make(Beacon::class)->suppress();

        $this->get('/page')->assertOk()->assertDontSee('Beacon(', false);
    }

    #[Test]
    public function it_passes_the_config_array_through_verbatim(): void
    {
        config()->set('helpscout-beacon.config', ['color' => '#aa0000', 'display' => ['style' => 'manual']]);
        $this->embedRoute();

        $this->get('/page')
            ->assertSee('Beacon("config", {"color":"#aa0000","display":{"style":"manual"}});', false);
    }

    #[Test]
    public function it_renders_session_data_set_during_the_request(): void
    {
        $this->embedRoute();
        $this->app->make(Beacon::class)->sessionData(['order' => 'ORD-42']);

        $this->get('/page')->assertSee('Beacon("session-data", {"order":"ORD-42"});', false);
    }

    #[Test]
    public function it_passes_component_attributes_through_to_the_script_tag(): void
    {
        Route::get('/nonce-page', fn () => view('embed-page-nonce'));
        view()->addLocation(__DIR__.'/../Fixtures/views');

        $this->get('/nonce-page')->assertSee('<script nonce="test-nonce">', false);
    }
}
