<?php declare(strict_types=1);
/**
 * BBB On Demand PHP VM Library
 *
 * Copyright (c) BBB On Demand
 * All rights reserved.
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this
 * software and associated documentation files (the "Software"), to deal in the Software
 * without restriction, including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons
 * to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED *AS IS*, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */
namespace BBBondemand\Test;

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