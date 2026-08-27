<?php

namespace Imazed\HelpScoutBeacon\Tests\Unit;

use DateTimeImmutable;
use Imazed\HelpScoutBeacon\Exceptions\InvalidBeaconData;
use Imazed\HelpScoutBeacon\Identity\BeaconIdentity;
use Imazed\HelpScoutBeacon\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BeaconIdentityTest extends TestCase
{
    #[Test]
    public function it_builds_a_minimal_payload_from_an_email_alone(): void
    {
        $this->assertSame(
            ['email' => 'ada@example.com'],
            BeaconIdentity::for('ada@example.com')->toArray(),
        );
    }

    #[Test]
    public function it_includes_every_standard_field_that_was_set(): void
    {
        $payload = BeaconIdentity::for('ada@example.com')
            ->name('Ada Lovelace')
            ->company('Analytical Engines Ltd')
            ->jobTitle('Programmer')
            ->avatar('https://example.com/ada.png')
            ->toArray();

        $this->assertSame([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'company' => 'Analytical Engines Ltd',
            'jobTitle' => 'Programmer',
            'avatar' => 'https://example.com/ada.png',
        ], $payload);
    }

    #[Test]
    public function it_refuses_an_empty_email(): void
    {
        $this->expectException(InvalidBeaconData::class);

        BeaconIdentity::for('   ');
    }

    #[Test]
    public function it_enforces_help_scouts_field_limits(): void
    {
        $identity = BeaconIdentity::for('ada@example.com');

        foreach (['name' => 81, 'company' => 61, 'jobTitle' => 61] as $field => $length) {
            try {
                $identity->{$field}(str_repeat('x', $length));
                $this->fail("Expected [{$field}] at {$length} characters to be refused.");
            } catch (InvalidBeaconData $e) {
                $this->assertStringContainsString($field, $e->getMessage());
            }
        }

        // The same lengths minus one are accepted.
        $identity->name(str_repeat('x', 80))->company(str_repeat('x', 60))->jobTitle(str_repeat('x', 60));

        $this->assertSame(str_repeat('x', 80), $identity->toArray()['name']);
    }

    #[Test]
    public function it_refuses_an_avatar_that_is_not_a_url(): void
    {
        $this->expectException(InvalidBeaconData::class);

        BeaconIdentity::for('ada@example.com')->avatar('not-a-url');
    }

    #[Test]
    public function it_refuses_an_avatar_url_over_the_length_limit(): void
    {
        $this->expectException(InvalidBeaconData::class);

        BeaconIdentity::for('ada@example.com')->avatar('https://example.com/'.str_repeat('a', 200));
    }

    #[Test]
    public function it_merges_custom_attributes_into_the_top_level(): void
    {
        $payload = BeaconIdentity::for('ada@example.com')
            ->attribute('plan', 'enterprise')
            ->attribute('seats', 12)
            ->toArray();

        $this->assertSame('enterprise', $payload['plan']);
        $this->assertSame(12, $payload['seats']);
    }

    #[Test]
    public function it_keeps_null_attribute_values_because_null_removes_a_property(): void
    {
        $payload = BeaconIdentity::for('ada@example.com')->attribute('plan', null)->toArray();

        $this->assertArrayHasKey('plan', $payload);
        $this->assertNull($payload['plan']);
    }

    #[Test]
    public function it_formats_date_attributes_the_way_help_scout_expects(): void
    {
        $payload = BeaconIdentity::for('ada@example.com')
            ->attribute('customer-since', new DateTimeImmutable('2026-08-27 15:30:00'))
            ->toArray();

        $this->assertSame('2026-08-27', $payload['customer-since']);
    }

    #[Test]
    public function it_refuses_attribute_keys_help_scout_would_reject(): void
    {
        $identity = BeaconIdentity::for('ada@example.com');

        foreach (['has space', 'dot.ted', '', str_repeat('k', 101)] as $key) {
            try {
                $identity->attribute($key, 'value');
                $this->fail("Expected attribute key [{$key}] to be refused.");
            } catch (InvalidBeaconData) {
                $this->addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function it_refuses_attribute_keys_that_shadow_the_identify_object(): void
    {
        // "signature" is the one that matters: an attribute must never be able
        // to overwrite the Secure Mode signature in the rendered payload.
        foreach (['signature', 'email', 'name', 'companyProperties'] as $reserved) {
            try {
                BeaconIdentity::for('ada@example.com')->attribute($reserved, 'spoofed');
                $this->fail("Expected reserved key [{$reserved}] to be refused.");
            } catch (InvalidBeaconData) {
                $this->addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function it_refuses_text_attribute_values_over_the_limit(): void
    {
        $this->expectException(InvalidBeaconData::class);

        BeaconIdentity::for('ada@example.com')->attribute('notes', str_repeat('x', 256));
    }

    #[Test]
    public function it_nests_company_properties_under_their_own_key(): void
    {
        $payload = BeaconIdentity::for('ada@example.com')
            ->companyProperty('industry', 'computing')
            ->toArray();

        $this->assertSame(['industry' => 'computing'], $payload['companyProperties']);
    }

    #[Test]
    public function it_exposes_the_email_the_signature_is_computed_over(): void
    {
        $this->assertSame('ada@example.com', BeaconIdentity::for('ada@example.com')->email());
    }
}
