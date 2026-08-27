<?php

namespace Imazed\HelpScoutBeacon\Tests\Unit;

use Imazed\HelpScoutBeacon\Beacon;
use Imazed\HelpScoutBeacon\Exceptions\InvalidBeaconData;
use Imazed\HelpScoutBeacon\Support\LogoutFlag;
use Imazed\HelpScoutBeacon\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BeaconTest extends TestCase
{
    #[Test]
    public function it_starts_unsuppressed_and_empty(): void
    {
        $beacon = $this->beacon();

        $this->assertFalse($beacon->suppressed());
        $this->assertSame([], $beacon->sessionDataAttributes());
    }

    #[Test]
    public function it_suppresses_for_the_current_request(): void
    {
        $beacon = $this->beacon();
        $beacon->suppress();

        $this->assertTrue($beacon->suppressed());
    }

    #[Test]
    public function it_merges_session_data_across_calls(): void
    {
        $beacon = $this->beacon();
        $beacon->sessionData(['order' => 'ORD-1', 'total' => 99]);
        $beacon->sessionData(['order' => 'ORD-2']);

        $this->assertSame(['order' => 'ORD-2', 'total' => 99], $beacon->sessionDataAttributes());
    }

    #[Test]
    public function it_enforces_help_scouts_session_data_cap(): void
    {
        $beacon = $this->beacon();

        $attributes = [];
        for ($i = 1; $i <= 21; $i++) {
            $attributes["key-{$i}"] = $i;
        }

        $this->expectException(InvalidBeaconData::class);

        $beacon->sessionData($attributes);
    }

    #[Test]
    public function it_refuses_non_scalar_session_values(): void
    {
        $this->expectException(InvalidBeaconData::class);

        $this->beacon()->sessionData(['nested' => ['not' => 'allowed']]);
    }

    #[Test]
    public function it_refuses_session_data_without_string_keys(): void
    {
        $this->expectException(InvalidBeaconData::class);

        $this->beacon()->sessionData(['plain value']);
    }

    protected function beacon(): Beacon
    {
        return new Beacon($this->app->make(LogoutFlag::class));
    }
}
