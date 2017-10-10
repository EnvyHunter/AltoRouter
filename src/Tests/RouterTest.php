<?php
namespace HakimCh\Http\Tests;

use Exception;
use HakimCh\Http\Router;
use HakimCh\Http\RouterException;
use HakimCh\Http\RouterParser;
use phpDocumentor\Reflection\Types\Boolean;
use PHPUnit_Framework_TestCase;
use stdClass;

class RouterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Router
     */
    protected $router;
    protected $closure;
    protected $param1;
    protected $param2;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $parser = new RouterParser();
        $this->router  = new Router($parser, [], '', []);
        $this->param1  = ['controller' => 'test', 'action' => 'someaction'];
        $this->param2  = ['controller' => 'test', 'action' => 'someaction', 'type' => 'json'];
        $this->closure = function () {
        };
    }

    /**
     * @covers Router::getRoutes
     */
    public function testMapAndGetRoutes()
    {
        $route = ['POST', '/[:controller]/[:action]', $this->closure, null];

        call_user_func_array([$this->router, 'map'], $route);

        $routes = $this->router->getRoutes();

        $this->assertInternalType('array', $routes);
        $this->assertEquals([$route], $routes);
    }

    /**
     * @covers Router::setRoutes
     */
    public function testAddRoutesWithArrayArgument()
    {
        $route1 = ['POST', '/[:controller]/[:action]', $this->closure, null];
        $route2 = ['POST', '/[:controller]/[:action]', $this->closure, 'second_route'];

        $this->router->setRoutes([$route1, $route2]);
        
        $routes = $this->router->getRoutes();
        
        $this->assertEquals($route1, $routes[0]);
        $this->assertEquals($route2, $routes[1]);
    }

    /**
     * @covers Router::setRoutes
     */
    public function testAddRoutesWithTraversableArgument()
    {
        $traversable = new SimpleTraversable();
        $this->router->setRoutes($traversable);
        
        $traversable->rewind();
        
        $first = $traversable->current();
        $traversable->next();
        $second = $traversable->current();
        
        $routes = $this->router->getRoutes();
        
        $this->assertEquals($first, $routes[0]);
        $this->assertEquals($second, $routes[1]);
    }

    /**
     * @covers Router::setRoutes
     */
    public function testAddRoutesWidthInvalidArgument()
    {
        try {
            $this->router->setRoutes(new stdClass);
        } catch (RouterException $e) {
            $this->assertEquals('Routes should be an array or an instance of Iterator', $e->getMessage());
        }
    }

    /**
     * @covers Router::setBasePath
     */
    public function testSetBasePath()
    {
        $this->router->setBasePath('/some/path');
        $this->assertEquals('/some/path', $this->router->getBasePath());
    }

    /**
     * @covers Router::map
     */
    public function testMapWithName()
    {
        $route = ['POST', '/[:controller]/[:action]', $this->closure, 'my_route'];

        call_user_func_array([$this->router, 'map'], $route);

        $routes = $this->router->getRoutes();
        $namedRoutes = $this->router->getNamedRoutes();

        $this->assertInternalType('array', $routes);
        $this->assertEquals($route, $routes[0]);

        $this->assertInternalType('array', $namedRoutes);
        $this->assertEquals('/[:controller]/[:action]', $namedRoutes['my_route']);
    }

    /**
     * @covers Router::map
     */
    public function testMapWithDuplicatedRouteName()
    {
        try {
            $route = ['POST', '/[:controller]/[:action]', $this->closure, 'my_route'];
            call_user_func_array([$this->router, 'map'], $route);
            call_user_func_array([$this->router, 'map'], $route);
        } catch (Exception $e) {
            $this->assertEquals("Can not redeclare route 'my_route'", $e->getMessage());
        }
    }

    /**
     * @covers Router::generate
     */
    public function testGenerate()
    {
        $this->router->map('GET', '/[:controller]/[:action]', $this->closure, 'foo_route');
        
        $this->assertEquals('/test/someaction', $this->router->generate('foo_route', $this->param1));
        $this->assertEquals('/test/someaction', $this->router->generate('foo_route', $this->param2));
    }

    /**
     * @covers Router::generate
     */
    public function testGenerateWithOptionalUrlParts()
    {
        $this->router->map('GET', '/[:controller]/[:action].[:type]?', $this->closure, 'bar_route');
        
        $this->assertEquals('/test/someaction', $this->router->generate('bar_route', $this->param1));
        $this->assertEquals('/test/someaction.json', $this->router->generate('bar_route', $this->param2));
    }

    /**
     * @covers Router::generate
     */
    public function testGenerateWithNonExistingRoute()
    {
        try {
            $this->router->generate('non_existing_route');
        } catch (Exception $e) {
            $this->assertEquals("Route 'non_existing_route' does not exist.", $e->getMessage());
        }
    }
    
    /**
     * @covers Router::match
     * @covers Router::compileRoute
     */
    public function testMatch()
    {
        $params = [
            'target' => 'foo_action',
            'params' => ['controller' => 'test', 'action' => 'do'],
            'name'   => 'foo_route'
        ];

        $this->router->map('GET', '/foo/[:controller]/[:action]', 'foo_action', 'foo_route');
        
        $this->assertEquals($params, $this->router->match('/foo/test/do', 'GET'));
        $this->assertSame(false, $this->router->match('/foo/test/do', 'POST'));
        $this->assertEquals($params, $this->router->match('/foo/test/do?param=value', 'GET'));
    }
    
    public function testMatchWithFixedParamValues()
    {
        $params = [
            'target' => 'usersController#doAction',
            'params' => ['id' => 1, 'action' => 'delete'],
            'name' => 'users_do'
        ];

        $this->router->map('POST', '/users/[i:id]/[delete|update:action]', 'usersController#doAction', 'users_do');
        
        $this->assertEquals($params, $this->router->match('/users/1/delete', 'POST'));
        $this->assertFalse($this->router->match('/users/1/delete', 'GET'));
        $this->assertFalse($this->router->match('/users/abc/delete', 'POST'));
        $this->assertFalse($this->router->match('/users/1/create', 'GET'));
    }
    
    public function testMatchWithServerVars()
    {
        $this->router->map('GET', '/foo/[:controller]/[:action]', 'foo_action', 'foo_route');
        
        $this->router->setServer([
            'REQUEST_URI' => '/foo/test/do',
            'REQUEST_METHOD' => 'GET'
        ]);
        
        $this->assertEquals(array(
            'target' => 'foo_action',
            'params' => array(
                'controller' => 'test',
                'action' => 'do'
            ),
            'name' => 'foo_route'
        ), $this->router->match());
    }
    
    public function testMatchWithOptionalUrlParts()
    {
        $this->router->map('GET', '/bar/[:controller]/[:action].[:type]?', 'bar_action', 'bar_route');
        
        $this->assertEquals(array(
            'target' => 'bar_action',
            'params' => array(
                'controller' => 'test',
                'action' => 'do',
                'type' => 'json'
            ),
            'name' => 'bar_route'
        ), $this->router->match('/bar/test/do.json', 'GET'));
    }
    
    public function testMatchWithWildcard()
    {
        $this->router->map('GET', '/a', 'foo_action', 'foo_route');
        $this->router->map('GET', '*', 'bar_action', 'bar_route');
        
        $this->assertEquals(array(
            'target' => 'bar_action',
            'params' => array(),
            'name' => 'bar_route'
        ), $this->router->match('/everything', 'GET'));
    }
    
    public function testMatchWithCustomRegexp()
    {
        $this->router->map('GET', '@^/[a-z]*$', 'bar_action', 'bar_route');
        
        $this->assertEquals(array(
            'target' => 'bar_action',
            'params' => array(),
            'name' => 'bar_route'
        ), $this->router->match('/everything', 'GET'));
        
        $this->assertFalse($this->router->match('/some-other-thing', 'GET'));
    }

    public function testMatchWithUnicodeRegex()
    {
        $pattern = '/(?<path>[^';
        // Arabic characters
        $pattern .= '\x{0600}-\x{06FF}';
        $pattern .= '\x{FB50}-\x{FDFD}';
        $pattern .= '\x{FE70}-\x{FEFF}';
        $pattern .= '\x{0750}-\x{077F}';
        // Alphanumeric, /, _, - and space characters
        $pattern .= 'a-zA-Z0-9\/_\-\s';
        // 'ZERO WIDTH NON-JOINER'
        $pattern .= '\x{200C}';
        $pattern .= ']+)';
        
        $this->router->map('GET', '@' . $pattern, 'unicode_action', 'unicode_route');
        
        $this->assertEquals(array(
            'target' => 'unicode_action',
            'name' => 'unicode_route',
            'params' => array(
                'path' => '大家好'
            )
        ), $this->router->match('/大家好', 'GET'));
        
        $this->assertFalse($this->router->match('/﷽‎', 'GET'));
    }

    /**
     * @covers Router::setMatchTypes
     */
    public function testMatchWithCustomNamedRegex()
    {
        $this->router->getParser()->setMatchTypes(array('cId' => '[a-zA-Z]{2}[0-9](?:_[0-9]++)?'));
        $this->router->map('GET', '/bar/[cId:customId]', 'bar_action', 'bar_route');
        
        $this->assertEquals(array(
            'target' => 'bar_action',
            'params' => array(
                'customId' => 'AB1',
            ),
            'name' => 'bar_route'
        ), $this->router->match('/bar/AB1', 'GET'));

        $this->assertEquals(array(
            'target' => 'bar_action',
            'params' => array(
                'customId' => 'AB1_0123456789',
            ),
            'name' => 'bar_route'
        ), $this->router->match('/bar/AB1_0123456789', 'GET'));
        
        $this->assertFalse($this->router->match('/some-other-thing', 'GET'));
    }

    public function testMatchWithCustomNamedUnicodeRegex()
    {
        $pattern = '[^';
        // Arabic characters
        $pattern .= '\x{0600}-\x{06FF}';
        $pattern .= '\x{FB50}-\x{FDFD}';
        $pattern .= '\x{FE70}-\x{FEFF}';
        $pattern .= '\x{0750}-\x{077F}';
        $pattern .= ']+';
        
        $this->router->getParser()->setMatchTypes(array('nonArabic' => $pattern));
        $this->router->map('GET', '/bar/[nonArabic:string]', 'non_arabic_action', 'non_arabic_route');

        $this->assertEquals(array(
            'target' => 'non_arabic_action',
            'name'   => 'non_arabic_route',
            'params' => array(
                'string' => 'some-path'
            )
        ), $this->router->match('/bar/some-path', 'GET'));
        
        $this->assertFalse($this->router->match('/﷽‎', 'GET'));
    }
}
