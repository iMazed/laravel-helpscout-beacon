<?php

namespace Imazed\HelpScoutBeacon\Tests\Unit;

use Imazed\HelpScoutBeacon\Exceptions\InvalidBeaconData;
use Imazed\HelpScoutBeacon\Support\SecureModeSigner;
use Imazed\HelpScoutBeacon\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SecureModeSignerTest extends TestCase
{
    #[Test]
    public function it_signs_an_email_address_the_way_help_scout_verifies_it(): void
    {
        // A pinned vector, not a re-derivation: hex HMAC-SHA256 is what Help
        // Scout's servers compute, so a change here is a wire-format break.
        $this->assertSame(
            '6f61b537fb4b149e75cda85687aca109c0da48ba25e6e1278f1925310c05403e',
            (new SecureModeSigner('shh'))->sign('ada@example.com'),
        );
    }

    #[Test]
    public function it_reports_whether_a_key_is_configured(): void
    {
        $this->assertTrue((new SecureModeSigner('shh'))->configured());
        $this->assertFalse((new SecureModeSigner(null))->configured());
        $this->assertFalse((new SecureModeSigner(''))->configured());
    }

    #[Test]
    public function it_refuses_to_sign_without_a_key(): void
    {
        $this->expectException(InvalidBeaconData::class);

        (new SecureModeSigner(null))->sign('ada@example.com');
    }
}
