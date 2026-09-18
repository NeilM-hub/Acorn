<?php

declare(strict_types=1);

namespace Acorn\SafetyHealthcheck\Domain;

use InvalidArgumentException;

final class ApplicabilityContext
{
    public readonly array $profile;

    public function __construct(array $profile)
    {
        $this->profile = $profile;
    }

    public static function fromProfile(array $profile): self
    {
        $definitions = require dirname(__DIR__, 2) . '/config/profile-fields.php';
        foreach ($definitions as $key => $allowed) {
            if (!array_key_exists($key, $profile)) {
                throw new InvalidArgumentException("Missing profile field: $key");
            }
            $values = in_array($key, ['workplace_types', 'risk_flags'], true) ? $profile[$key] : [$profile[$key]];
            if (!is_array($values) || array_diff($values, $allowed)) {
                throw new InvalidArgumentException("Invalid profile field: $key");
            }
        }
        if ($profile['workplace_types'] === []) {
            throw new InvalidArgumentException('Select at least one workplace type.');
        }

        return new self($profile);
    }

    public function hasRiskFlag(string $flag): bool { return in_array($flag, $this->profile['risk_flags'], true); }
    public function hasEmployees(): bool { return $this->profile['employee_band'] !== 'none'; }
    public function nonHomeWorkplace(): bool { return array_diff($this->profile['workplace_types'], ['home_working']) !== []; }
}
