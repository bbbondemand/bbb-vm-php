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
namespace BBBondemand;

class UrlBuilder {
    /**
     * @var string
     */
    private $customerId;

    /**
     * @var string
     */
    private $baseApiUrl;

    public const BASE_API_URL = 'https://bbbondemand.com/api/v1';

    /**
     * UrlBuilder constructor.
     *
     * @param $customerId
     * @param $baseApiUrl
     */
    public function __construct($customerId, string $baseApiUrl = null) {
        $this->customerId = $customerId;
        $this->baseApiUrl = $baseApiUrl ?? self::BASE_API_URL;
    }

    /**
     * Builds an API method URL that includes the url + params.
     *
     * @param string $route
     * @param array|null $pathParams
     * @param string|null $queryString
     *
     * @return string
     */
    public function __invoke(string $route, array $pathParams = null, string $queryString = null): string {
        $pathParams = (array)$pathParams;
        preg_match('#\{(.*?)\}#', $route, $match);
        $variable = $match[1] ?? '';
        $route = empty($pathParams) ? $route : ($variable != '' ? str_replace("{" . $variable . "}", $pathParams[$variable], $route) : $route);
        $queryString = !empty($queryString) ? '?' . $queryString : '';

        return $this->baseApiUrl . '/' . $this->customerId . '/vm/' . $route . $queryString;
    }
}
