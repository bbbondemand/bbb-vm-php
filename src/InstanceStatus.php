<?php declare(strict_types=1);
namespace BBBondemand;

abstract class InstanceStatus {
    public const DELETED = 'DELETED';
    public const STARTING = 'STARTING';
    public const AVAILABLE = 'AVAILABLE';
    public const STOPPING = 'STOPPING';
    public const STOPPED = 'STOPPED';

    public static function all(): array {
        return [self::STARTING, self::AVAILABLE, self::STOPPING, self::STOPPED, self::DELETED];
    }
}