<?php declare(strict_types=1);
/**
 * /**
 *  BBB On Demand VM library for PHP
 *
 *  This allows customers to create and manage their own, dedicated virtual servers running BBB. So the '/bigbluebutton/api' end *  point is used
 *  to manage meetings using a standard BBB library or integration; the /vm endpoint is used to manage your own virtual machines - and you would
 *  then use a BBB library to interact with the actual BBB instance running on each machine.
 *
 * @author Richard Phillips
 */

namespace BBBondemand\Util;

/**
 * Class UrlBuilder
 *
 * @package BBBondemand\Util
 */
class UrlBuilder
{
    /**
     * @var string
     */
    private $customerId;

    /**
     * @var string
     */
    private $apiServerBaseUrl;

    public const API_SERVER_BASE_URL = 'https://bbbondemand.com/api/v1';

    /**
     * UrlBuilder constructor.
     *
     * @param $customerId
     * @param $apiServerBaseUrl
     */
    public function __construct($customerId, string $apiServerBaseUrl = null)
    {
        $this->customerId       = $customerId;
        $this->apiServerBaseUrl = $apiServerBaseUrl ?? self::API_SERVER_BASE_URL;
    }

    /**
     * Builds an API method URL that includes the url + params.
     *
     * @param string $route
     * @param array|null  $params
     * @param string $queryString
     *
     * @return string
     */
    public function buildUrl(string $route, array $params = null, string $queryString = ''): string
    {
        $params = (array) $params;
        preg_match('#\{(.*?)\}#', $route, $match);
        $variable    = $match[1] ?? '';
        $route       = empty($params) ? $route : ($variable != '' ? str_replace("{" . $variable . "}", $params[$variable], $route) : $route);
        $queryString = !empty($queryString) ? '?' . $queryString : '';

        return $this->apiServerBaseUrl . '/' . $this->customerId . '/vm/' . $route . $queryString;
    }
}
