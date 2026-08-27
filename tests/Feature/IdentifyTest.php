<?php

namespace Imazed\HelpScoutBeacon\Tests\Feature;

use Illuminate\Support\Facades\Log;
use Imazed\HelpScoutBeacon\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IdentifyTest extends TestCase
{
    #[Test]
    public function it_leaves_a_guest_anonymous(): void
    {
        $this->embedRoute();

        $this->get('/page')
            ->assertSee('Beacon("init"', false)
            ->assertDontSee('Beacon("identify"', false);
    }

    #[Test]
    public function it_identifies_an_authenticated_user_with_a_server_side_signature(): void
    {
        $this->embedRoute();

        // The pinned HMAC-SHA256 of ada@example.com under test-secure-key.
        $this->actingAs($this->user())
            ->get('/page')
            ->assertSee('"email":"ada@example.com"', false)
            ->assertSee('"name":"Ada Lovelace"', false)
            ->assertSee('"signature":"54ac7c435433519bd12560e21c9f5d8128a1cbe02c1bbb930eae9328ca500f20"', false);
    }

    #[Test]
    public function it_never_renders_the_secure_mode_key_itself(): void
    {
        $this->embedRoute();

        $this->actingAs($this->user())
            ->get('/page')
            ->assertDontSee($this->secureKey, false);
    }

    #[Test]
    public function it_skips_identify_and_warns_when_no_key_is_configured(): void
    {
        config()->set('helpscout-beacon.secure_mode.key', null);
        $this->embedRoute();

        Log::spy();

        $this->actingAs($this->user())
            ->get('/page')
            ->assertSee('Beacon("init"', false)
            ->assertDontSee('Beacon("identify"', false)
            ->assertDontSee('ada@example.com', false);

        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function it_identifies_unsigned_only_when_explicitly_allowed(): void
    {
        config()->set('helpscout-beacon.secure_mode.key', null);
        config()->set('helpscout-beacon.secure_mode.allow_unsigned', true);
        $this->embedRoute();

        $this->actingAs($this->user())
            ->get('/page')
            ->assertSee('Beacon("identify"', false)
            ->assertSee('"email":"ada@example.com"', false)
            ->assertDontSee('"signature"', false);
    }

    #[Test]
    public function it_neutralizes_markup_in_user_data_before_it_reaches_the_script(): void
    {
        $this->embedRoute();

        $response = $this->actingAs($this->user(['name' => 'x</script><script>alert(1)']))
            ->get('/page');

        $response->assertDontSee('<script>alert(1)', false);

        // The payload survives, hex-escaped so it cannot terminate the element.
        // chr(92) is a backslash; spelled out so the assertion stays readable.
        $response->assertSee(chr(92).'u003Cscript'.chr(92).'u003Ealert(1)', false);
    }
}
