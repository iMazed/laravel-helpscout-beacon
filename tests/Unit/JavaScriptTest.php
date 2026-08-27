<?php

namespace Imazed\HelpScoutBeacon\Tests\Unit;

use Imazed\HelpScoutBeacon\Support\JavaScript;
use Imazed\HelpScoutBeacon\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class JavaScriptTest extends TestCase
{
    #[Test]
    public function it_neutralizes_a_script_breakout_attempt(): void
    {
        $encoded = JavaScript::encode(['name' => '</script><script>alert(1)</script>']);

        $this->assertStringNotContainsString('</script>', $encoded);
        $this->assertStringNotContainsString('<script>', $encoded);
    }

    #[Test]
    public function it_encodes_objects_and_scalars_as_javascript_literals(): void
    {
        $this->assertSame('"abc-123"', JavaScript::encode('abc-123'));
        $this->assertSame('{"endActiveChat":true}', JavaScript::encode(['endActiveChat' => true]));
    }
}
