<?php

class AltoRouter
{
	protected $routes = array();
	protected $namedRoutes = array();
	protected $basePath = '';
	protected $all = array(
		'get', 'post'
	);
	protected $matchTypes = array(
		'i'  => '[0-9]++',
		'a'  => '[0-9A-Za-z]++',
		'h'  => '[0-9A-Fa-f]++',
		'*'  => '.+?',
		'**' => '.++',
		''   => '[^/\.]++'
	);

	/**
	 * Create router in one call from config.
	 *
	 * @param array $routes
	 * @param string $basePath
	 * @param array $matchTypes
	 */
	public function __construct($routes = array(), $basePath = '', $matchTypes = array())
	{
		$this->addRoutes($routes);
		$this->setBasePath($basePath);
		$this->addMatchTypes($matchTypes);
	}

	/**
	 * Retrieves all routes.
	 * Useful if you want to process or display routes.
	 * @return array All routes.
	 */
	public function getRoutes()
	{
		return $this->routes;
	}

	/**
	 * Add multiple routes at once from array in the following format:
	 *
	 *   $routes = array(
	 *      array($method, $route, $target, $name)
	 *   );
	 *
	 * @param array $routes
	 * @return void
	 * @author Koen Punt
	 */
	public function addRoutes($routes)
	{
		if (!is_array($routes) && !$routes instanceof Traversable) {
			throw new Exception('Routes should be an array or an instance of Traversable');
		}
		foreach ($routes as $route) {
			call_user_func_array(array($this, 'map'), $route);
		}
	}

	/**
	 * Set the base path.
	 * Useful if you are running your application from a subdirectory.
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $basePath;
	}

	/**
	 * Add named match types. It uses array_merge so keys can be overwritten.
	 *
	 * @param array $matchTypes The key is the name and the value is the regex.
	 */
	public function addMatchTypes($matchTypes)
	{
		$this->matchTypes = array_merge($this->matchTypes, $matchTypes);
	}

	/**
	 * Map a route to a target
	 *
	 * @param string $method One of 5 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE)
	 * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
	 * @param mixed $target The target where this route should point to. Can be anything.
	 * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
	 */
	public function map($method, $route, $target, $name = null)
	{
		if ($name) {
			if (isset($this->namedRoutes[$name])) {
				throw new \Exception("Can not redeclare route '{$name}'");
			}
			$this->namedRoutes[$name] = $route;
		}

		$this->routes[] = array($method, $route, $target, $name);
	}

	/**
	 * Reversed routing
	 *
	 * Generate the URL for a named route. Replace regexes with supplied parameters
	 *
	 * @param string $routeName The name of the route.
	 * @param array @params Associative array of parameters to replace placeholders with.
	 * @return string The URL of the route with named parameters in place.
	 */
	public function generate($routeName, array $params = array())
	{

		// Check if named route exists
		if (!isset($this->namedRoutes[$routeName])) {
			throw new \Exception("Route '{$routeName}' does not exist.");
		}

		// Replace named parameters
		$route = $this->namedRoutes[$routeName];

		// prepend base path to route url again
		$url = $this->basePath . $route;

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
	 * Match a given Request Url against stored routes
	 * @param string $requestUrl
	 * @param string $requestMethod
	 * @return array|boolean Array with route information on success, false on failure (no match).
	 */
	public function match($requestUrl = null, $requestMethod = null)
	{
		$params = array();

		$requestUrl = $this->getRequestUrl($requestUrl);

		// set Request Method if it isn't passed as a parameter
		if (is_null($requestMethod)) {
			$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
		}

		// Force request_order to be GP
		// http://www.mail-archive.com/internals@lists.php.net/msg33119.html
		$_REQUEST = array_merge($_GET, $_POST);

		foreach ($this->routes as $handler) {
			list($method, $routeString, $target, $name) = $handler;

			// Method did not match, continue to next route.
			if (!$match = $this->methodMatch($method, $requestMethod, $routeString, $requestUrl)) {
				continue;
			}

			if (($match === true || $match > 0)) {
				if ($keys = array_keys($params)) {
					foreach ($keys as $key) {
						if (is_numeric($key)) {
							unset($params[$key]);
						}
					}
				}

				return array(
					'target' => $target,
					'params' => $params,
					'name'   => $name
				);
			}
		}

		return false;
	}

	/**
	 * Compile the regex for a given route (EXPENSIVE)
	 */
	private function compileRoute($routeString, $requestUrl)
	{
		$route = $this->getRoute($routeString, $requestUrl);

		if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
			$matchTypes = $this->matchTypes;
			foreach ($matches as $match) {
				list($block, $pre, $type, $param, $optional) = $match;

				if (isset($matchTypes[$type])) {
					$type = $matchTypes[$type];
				}
				if ($pre === '.') {
					$pre = '\.';
				}

				//Older versions of PCRE require the 'P' in (?P<named>)
				$pattern = '(?:'
				           . ($pre !== '' ? $pre : null)
				           . '('
				           . ($param !== '' ? "?P<$param>" : null)
				           . $type
				           . '))'
				           . ($optional !== '' ? '?' : null);

				$route = str_replace($block, $pattern, $route);
			}
		}

		return "`^$route$`u";
	}

	/**
	 * @param $requestUrl
	 *
	 * @return mixed
	 */
	private function getRequestUrl($requestUrl)
	{
		// set Request Url if it isn't passed as parameter
		if (is_null($requestUrl)) {
			$requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
		}

		$requestUrl = parse_url($requestUrl, PHP_URL_PATH);

		return str_replace($this->basePath, '', $requestUrl);
	}

	/**
	 * @param $method
	 * @param $requestMethod
	 * @param $routeString
	 * @param $requestUrl
	 *
	 * @return mixed
	 */
	private function methodMatch($method, $requestMethod, $routeString, $requestUrl)
	{
		$method = strtolower($method);
		$requestMethod = strtolower($requestMethod);
		$methods = explode('|', $method);
		$methodMatch = in_array($requestMethod, $methods);

		if(!$methodMatch) return false;

		// Check for a wildcard (matches all)
		if ($routeString === '*') {
			return true;
		} elseif (isset($routeString[0]) && $routeString[0] === '@') {
			$pattern = '`' . substr($routeString, 1) . '`u';
			return preg_match($pattern, $requestUrl, $params);
		}

		$regex = $this->compileRoute($routeString, $requestUrl);
		return preg_match($regex, $requestUrl, $params);
	}

	/**
	 * @param $routeString
	 * @param $requestUrl
	 *
	 * @return bool|string
	 */
	private function getRoute($routeString, $requestUrl)
	{
		$i = $j = 0;
		$n = isset($routeString[0]) ? $routeString[0] : null;
		$regex = $route = false;

		// Find the longest non-regex substring and match it against the URI
		while (true) {
			if (!isset($routeString[$i])) {
				break;
			} elseif (false === $regex) {
				$c = $n;
				$regex = in_array($c, array('[', '(', '.'));
				if (!$regex && isset($routeString[$i+1])) {
					$n = $routeString[$i + 1];
					$regex = in_array($n, array('?', '+', '*', '{'));
				}
				if (!$regex && $c !== '/' && (!isset($requestUrl[$j]) || $c !== $requestUrl[$j])) {
					continue;
				}
				$j++;
			}
			$route .= $routeString[$i++];
		}

		return $route;
	}

	public function __call($method, $arguments)
	{
		if(!in_array($method, array('get', 'post', 'delete', 'put', 'patch', 'update', 'all'))) {
			throw new Exception($method . ' not exist in the '. __CLASS__);
		}

		$methods = $method == 'all' ? implode('|', $this->all) : $method;

		$route = array_merge(array($methods), $arguments);

		call_user_func_array(array($this, 'map'), $route);
	}

	public function __toString()
	{
		return 'AltoRouter';
	}
}
