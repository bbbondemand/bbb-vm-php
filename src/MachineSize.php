<?php declare(strict_types=1);
namespace BBBondemand;

abstract class MachineSize {
    public const SMALL = 'Small';
    public const STANDARD = 'Standard';
    public const LARGE = 'Large';
    public const XLARGE = 'Xlarge';

    public static function all(): array {
        return [self::SMALL, self::STANDARD, self::LARGE, self::XLARGE];
    }
}