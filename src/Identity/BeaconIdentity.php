<?php

namespace Imazed\HelpScoutBeacon\Identity;

use DateTimeInterface;
use Imazed\HelpScoutBeacon\Exceptions\InvalidBeaconData;

/**
 * One visitor's identify payload, validated against Help Scout's documented
 * field limits when it is built rather than silently truncated when it lands.
 *
 * The Secure Mode signature is not part of this object; it is attached at
 * render time so identities can be built and tested without a key.
 */
class BeaconIdentity
{
    /**
     * Top-level keys Help Scout assigns meaning to; custom attributes may not
     * shadow them.
     *
     * @var array<int, string>
     */
    protected const RESERVED_KEYS = ['name', 'email', 'signature', 'company', 'jobTitle', 'avatar', 'companyProperties'];

    protected const NAME_LIMIT = 80;

    protected const COMPANY_LIMIT = 60;

    protected const JOB_TITLE_LIMIT = 60;

    protected const AVATAR_LIMIT = 200;

    protected const TEXT_VALUE_LIMIT = 255;

    protected const ATTRIBUTE_KEY_PATTERN = '/^[A-Za-z0-9_-]{1,100}$/';

    protected ?string $name = null;

    protected ?string $company = null;

    protected ?string $jobTitle = null;

    protected ?string $avatar = null;

    /** @var array<string, string|int|float|bool|null> */
    protected array $attributes = [];

    /** @var array<string, string|int|float|bool|null> */
    protected array $companyProperties = [];

    final protected function __construct(protected string $email) {}

    public static function for(string $email): static
    {
        if (trim($email) === '') {
            throw InvalidBeaconData::emptyEmail();
        }

        return new static($email);
    }

    public function name(string $name): static
    {
        $this->name = $this->limited('name', $name, self::NAME_LIMIT);

        return $this;
    }

    public function company(string $company): static
    {
        $this->company = $this->limited('company', $company, self::COMPANY_LIMIT);

        return $this;
    }

    public function jobTitle(string $jobTitle): static
    {
        $this->jobTitle = $this->limited('jobTitle', $jobTitle, self::JOB_TITLE_LIMIT);

        return $this;
    }

    public function avatar(string $url): static
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw InvalidBeaconData::invalidAvatarUrl($url);
        }

        $this->avatar = $this->limited('avatar', $url, self::AVATAR_LIMIT);

        return $this;
    }

    /**
     * A custom customer property. The property must already exist in Help
     * Scout under that ID; null removes its current value.
     *
     * These sync into the customer profile agents see, and they are not
     * covered by the Secure Mode signature — see the security notes in the
     * configuration file.
     */
    public function attribute(string $key, string|int|float|bool|DateTimeInterface|null $value): static
    {
        $this->attributes[$this->attributeKey($key)] = $this->attributeValue($key, $value);

        return $this;
    }

    /**
     * @param  array<string, string|int|float|bool|DateTimeInterface|null>  $attributes
     */
    public function attributes(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->attribute((string) $key, $value);
        }

        return $this;
    }

    /**
     * A company property, under the same rules as {@see attribute()}.
     */
    public function companyProperty(string $key, string|int|float|bool|DateTimeInterface|null $value): static
    {
        $this->companyProperties[$this->attributeKey($key)] = $this->attributeValue($key, $value);

        return $this;
    }

    /**
     * @param  array<string, string|int|float|bool|DateTimeInterface|null>  $properties
     */
    public function companyProperties(array $properties): static
    {
        foreach ($properties as $key => $value) {
            $this->companyProperty((string) $key, $value);
        }

        return $this;
    }

    /**
     * The address the Secure Mode signature is computed over.
     */
    public function email(): string
    {
        return $this->email;
    }

    /**
     * The identify object, without a signature.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'company' => $this->company,
            'jobTitle' => $this->jobTitle,
            'avatar' => $this->avatar,
        ], static fn (mixed $value): bool => $value !== null);

        // Null attribute values survive the merge: null is how a property is removed.
        $payload += $this->attributes;

        if ($this->companyProperties !== []) {
            $payload['companyProperties'] = $this->companyProperties;
        }

        return $payload;
    }

    protected function limited(string $field, string $value, int $limit): string
    {
        if (mb_strlen($value) > $limit) {
            throw InvalidBeaconData::tooLong($field, $limit);
        }

        return $value;
    }

    protected function attributeKey(string $key): string
    {
        if (preg_match(self::ATTRIBUTE_KEY_PATTERN, $key) !== 1) {
            throw InvalidBeaconData::invalidAttributeKey($key);
        }

        if (in_array($key, self::RESERVED_KEYS, true)) {
            throw InvalidBeaconData::reservedAttributeKey($key);
        }

        return $key;
    }

    protected function attributeValue(string $key, string|int|float|bool|DateTimeInterface|null $value): string|int|float|bool|null
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && mb_strlen($value) > self::TEXT_VALUE_LIMIT) {
            throw InvalidBeaconData::attributeValueTooLong($key, self::TEXT_VALUE_LIMIT);
        }

        return $value;
    }
}
