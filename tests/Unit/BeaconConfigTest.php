<?php

namespace Imazed\HelpScoutBeacon\Tests\Unit;

use Imazed\HelpScoutBeacon\Support\BeaconConfig;
use Imazed\HelpScoutBeacon\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BeaconConfigTest extends TestCase
{
    #[Test]
    public function it_reads_the_packaged_config_shape(): void
    {
        $config = BeaconConfig::fromArray([
            'beacon_id' => 'abc-123',
            'enabled' => true,
            'guard' => 'customers',
            'secure_mode' => ['key' => 'shh', 'allow_unsigned' => true],
            'logout' => ['enabled' => false, 'end_active_chat' => false],
            'config' => ['color' => '#aa0000'],
        ]);

        $this->assertSame('abc-123', $config->beaconId);
        $this->assertSame('customers', $config->guard);
        $this->assertTrue($config->allowUnsigned);
        $this->assertFalse($config->logoutEnabled);
        $this->assertFalse($config->endActiveChatOnLogout);
        $this->assertSame(['color' => '#aa0000'], $config->jsConfig);
    }

    #[Test]
    public function it_defaults_to_signed_identify_and_wired_logout(): void
    {
        $config = BeaconConfig::fromArray(['beacon_id' => 'abc-123']);

        $this->assertFalse($config->allowUnsigned);
        $this->assertTrue($config->logoutEnabled);
        $this->assertTrue($config->endActiveChatOnLogout);
    }

    #[Test]
    public function it_is_only_renderable_with_an_id_and_the_switch_on(): void
    {
        $this->assertTrue(BeaconConfig::fromArray(['beacon_id' => 'abc-123'])->renderable());
        $this->assertFalse(BeaconConfig::fromArray(['beacon_id' => null])->renderable());
        $this->assertFalse(BeaconConfig::fromArray(['beacon_id' => ''])->renderable());
        $this->assertFalse(BeaconConfig::fromArray(['beacon_id' => 'abc-123', 'enabled' => false])->renderable());
    }
}
