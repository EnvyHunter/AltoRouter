<?php
namespace HakimCh\Http;

class RouterParser implements RouterParserInterface
{
    protected $params = [];
    protected $matchTypes = [
        'i'  => '[0-9]++',
        'a'  => '[0-9A-Za-z]++',
        'h'  => '[0-9A-Fa-f]++',
        '*'  => '.+?',
        '**' => '.++',
        ''   => '[^/\.]++'
    ];

    /**
     * Create router in one call from config.
     *
     * @param array $matchTypes
     */
    public function __construct($matchTypes = [])
    {
        $this->setMatchTypes($matchTypes);
    }

    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     *
     * @param array $matchTypes The key is the name and the value is the regex.
     */
    public function setMatchTypes($matchTypes)
    {
        $this->matchTypes = array_merge($this->matchTypes, $matchTypes);
    }

    /**
     * Get the url from a route name
     *
     * @param string $basePath
     * @param string $route
     * @param array $params
     *
     * @return string
     */
    public function generate($basePath, $route, array $params)
    {
        $url = $basePath . $route;

        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $block  = $match[0];
                $pre    = $match[1];
                $param  = $match[3];

                if ($pre) {
                    $block = substr($block, 1);
                }

                if (isset($params[$param])) {
                    $url = str_replace($block, $params[$param], $url);
                } elseif ($match[4]) {
                    $url = str_replace($pre . $block, '', $url);
                }
            }
        }

        return $url;
    }

    /**
     * @param string $method
     * @param string $requestMethod
     * @param string $routeString
     * @param string $requestUrl
     *
     * @return mixed
     */
    public function methodMatch($method, $requestMethod, $routeString, $requestUrl)
    {
        $methods = explode('|', $method);

        if (preg_grep("/{$requestMethod}/i", $methods)) {
            if ($routeString == '*') {
                return true;
            } elseif (isset($routeString[0]) && $routeString[0] == '@') {
                return preg_match('`' . substr($routeString, 1) . '`u', $requestUrl, $this->params);
            } elseif (($position = strpos($routeString, '[')) === false) {
                return strcmp($requestUrl, $routeString) === 0;
            }
            if (strncmp($requestUrl, $routeString, $position) !== 0) {
                return false;
            }

            return preg_match($this->compileRoute($routeString, $requestUrl), $requestUrl, $this->params);
        }

        return false;
    }

    /**
     * Compile the regex for a given route (EXPENSIVE)
     *
     * @param $routeString
     * @param $requestUrl
     *
     * @return string
     */
    private function compileRoute($routeString, $requestUrl)
    {
        $route = $this->getRoute($routeString, $requestUrl);

        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            $matchTypes = $this->matchTypes;
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;
                $pattern = $this->getRoutePattern($matchTypes, $pre, $type, $param, $optional);
                $route = str_replace($block, $pattern, $route);
            }
        }

        return "`^$route$`u";
    }

    /**
     * @param $matchTypes
     * @param $pre
     * @param $type
     * @param $param
     * @param $optional
     *
     * @return string
     */
    private function getRoutePattern($matchTypes, $pre, $type, $param, $optional)
    {
        if (isset($matchTypes[$type])) {
            $type = $matchTypes[$type];
        }
        if ($pre === '.') {
            $pre = '\.';
        }

        //Older versions of PCRE require the 'P' in (?P<named>)
        return '(?:'
            . ($pre !== '' ? $pre : null)
            . '('
            . ($param !== '' ? "?P<$param>" : null)
            . $type
            . '))'
            . ($optional !== '' ? '?' : null);
    }

    /**
     * @param $routeString
     * @param $requestUrl
     *
     * @return bool|string
     */
    private function getRoute($routeString, $requestUrl)
    {
        $iPointer = $jPointer = 0;
        $nPointer = isset($routeString[0]) ? $routeString[0] : null;
        $regex = $route = false;

        // Find the longest non-regex substring and match it against the URI
        while (true) {
            if (!isset($routeString[$iPointer])) {
                break;
            }
            if ($regex === false) {
                if (!$this->getRouteRegexCheck($nPointer, $jPointer, $iPointer, $routeString, $requestUrl)) {
                    continue;
                }
                $jPointer++;
            }
            $route .= $routeString[$iPointer++];
        }

        return $route;
    }

    /**
     * @param $nPointer
     * @param $jPointer
     * @param $iPointer
     * @param $routeString
     * @param $requestUrl
     *
     * @return bool
     */
    private function getRouteRegexCheck($nPointer, $jPointer, $iPointer, $routeString, $requestUrl)
    {
        $cPointer = $nPointer;
        $regex = in_array($cPointer, array('[', '(', '.'));
        if (!$regex && isset($routeString[$iPointer+1])) {
            $nPointer = $routeString[$iPointer + 1];
            $regex = in_array($nPointer, array('?', '+', '*', '{'));
        }
        if (!$regex && $cPointer !== '/' && (!isset($requestUrl[$jPointer]) || $cPointer !== $requestUrl[$jPointer])) {
            return false;
        }
        return true;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return array
     */
    public function getMatchTypes()
    {
        return $this->matchTypes;
    }
}
