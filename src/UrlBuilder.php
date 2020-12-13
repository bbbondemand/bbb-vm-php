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
namespace BBBondemand;

/**
 * Class UrlBuilder
 *
 * @package BBBondemand\Util
 */
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
    public function buildUrl(string $route, array $pathParams = null, string $queryString = null): string {
        $pathParams = (array)$pathParams;
        preg_match('#\{(.*?)\}#', $route, $match);
        $variable = $match[1] ?? '';
        $route = empty($pathParams) ? $route : ($variable != '' ? str_replace("{" . $variable . "}", $pathParams[$variable], $route) : $route);
        $queryString = !empty($queryString) ? '?' . $queryString : '';

        return $this->baseApiUrl . '/' . $this->customerId . '/vm/' . $route . $queryString;
    }
}
