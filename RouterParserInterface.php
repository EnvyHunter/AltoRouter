<?php
namespace HakimCh\Http;

interface RouterParserInterface
{
    /**
     * Get the route parameters
     *
     * @return array
     */
    public function getParams();

    /**
     * Get the url from a route name
     *
     * @param string $basePath
     * @param string $route
     * @param array $params
     *
     * @return string
     */
    public function generate($basePath, $route, array $params);

    /**
     * Check if the request method match the route methods
     * Update the $params
     *
     * @param string $method
     * @param string $requestMethod
     * @param string $routeString
     * @param string $requestUrl
     *
     * @return mixed
     */
    public function methodMatch($method, $requestMethod, $routeString, $requestUrl);
}
