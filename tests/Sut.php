<?php declare(strict_types=1);

namespace BBBondemand;

use RuntimeException;

class Sut {
    /**
     * @param string|null $name
     * @return array|string
     */
    public static function vmConf(string $name = null) {
        static $conf;
        if (!$conf) {
            $conf = [
                'customerId' => self::readEnvVar('VM_CUSTOMER_ID'),
                'customerApiToken' => self::readEnvVar('VM_CUSTOMER_API_TOKEN'),
                'baseApiUrl' => self::readEnvVar('VM_BASE_API_URL'),
            ];
        }
        return null !== $name ? $conf[$name] : $conf;
    }

    /**
     * @param string $name
     * @return array|string
     */
    private static function readEnvVar(string $name) {
        $val = getenv($name);
        if (!$val) {
            throw new RuntimeException("The environment variable $name is not set");
        }
        return $val;
    }
}