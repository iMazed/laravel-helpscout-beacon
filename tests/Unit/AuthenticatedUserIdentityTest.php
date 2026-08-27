<?php

namespace Imazed\HelpScoutBeacon\Tests\Unit;

use Imazed\HelpScoutBeacon\Identity\AuthenticatedUserIdentity;
use Imazed\HelpScoutBeacon\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthenticatedUserIdentityTest extends TestCase
{
    #[Test]
    public function it_stays_anonymous_for_a_guest(): void
    {
        $this->assertNull((new AuthenticatedUserIdentity)->build(null));
    }

    #[Test]
    public function it_stays_anonymous_for_a_user_without_an_email(): void
    {
        $this->assertNull((new AuthenticatedUserIdentity)->build($this->user(['email' => null])));
        $this->assertNull((new AuthenticatedUserIdentity)->build($this->user(['email' => '  '])));
    }

    #[Test]
    public function it_announces_email_and_name(): void
    {
        $identity = (new AuthenticatedUserIdentity)->build($this->user());

        $this->assertSame([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ], $identity->toArray());
    }

    #[Test]
    public function it_omits_the_name_when_the_user_has_none(): void
    {
        $identity = (new AuthenticatedUserIdentity)->build($this->user(['name' => null]));

        $this->assertSame(['email' => 'ada@example.com'], $identity->toArray());
    }

    #[Test]
    public function it_truncates_an_over_long_name_rather_than_throwing(): void
    {
        // The default builder runs against arbitrary user models, so it must
        // degrade rather than take the page down with it.
        $identity = (new AuthenticatedUserIdentity)->build($this->user(['name' => str_repeat('x', 120)]));

        $this->assertSame(str_repeat('x', 80), $identity->toArray()['name']);
    }
}
