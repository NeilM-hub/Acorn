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

        foreach ($profile as $key => $value) {
            if (!array_key_exists($key, $definitions)) {
                throw new InvalidArgumentException("Unknown profile field: $key");
            }

            $allowed = $definitions[$key];
            $values = in_array($key, ['workplace_types', 'risk_flags'], true) ? $value : [$value];

            if (!is_array($values) || array_diff($values, $allowed)) {
                throw new InvalidArgumentException("Invalid profile field: $key");
            }

            if ($key === 'workplace_types' && $values === []) {
                throw new InvalidArgumentException('Select at least one workplace type.');
            }
        }

        return new self($profile);
    }

    public function hasRiskFlag(string $flag): bool
    {
        return in_array($flag, $this->profile['risk_flags'] ?? [], true);
    }

    public function hasEmployees(): bool
    {
        return isset($this->profile['employee_band']) && $this->profile['employee_band'] !== 'none';
    }

    public function nonHomeWorkplace(): bool
    {
        $types = $this->profile['workplace_types'] ?? [];
        return $types !== [] && array_diff($types, ['home_working']) !== [];
    }
}
