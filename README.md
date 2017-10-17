# Router [![SensioLabsInsight](https://insight.sensiolabs.com/projects/2ac98973-732a-4d99-8259-ddfdd023efe5/small.png)](https://insight.sensiolabs.com/projects/2ac98973-732a-4d99-8259-ddfdd023efe5) [![Build Status](https://travis-ci.org/HakimCh/Router.svg?branch=master)](https://travis-ci.org/HakimCh/Router) [![Code Climate](https://codeclimate.com/github/HakimCh/Router/badges/gpa.svg)](https://codeclimate.com/github/HakimCh/Router) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/HakimCh/Router/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/HakimCh/Router/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/HakimCh/Router/badges/build.png?b=master)](https://scrutinizer-ci.com/g/HakimCh/Router/build-status/master) [![Code Coverage](https://scrutinizer-ci.com/g/HakimCh/Router/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/HakimCh/Router/?branch=master)
Router is a small but powerful routing class for PHP 5.6+, forked from [AltoRouter.php](https://github.com/dannyvankooten/AltoRouter/).

```php
$parser = new \HakimCh\Http\RouterParser();
$router = new \HakimCh\Http\Router($parser, [], '', $_SERVER);

// map homepage
$router->map( 'GET', '/', function() {
    require __DIR__ . '/views/home.php';
});

// map users details page
$router->map( 'GET|POST', '/users/[i:id]/', function( $id ) {
  $user = .....
  require __DIR__ . '/views/user/details.php';
});

// map users details page (get, post, delete, put, patch, update)
$router->get('/users/[i:id]/', function( $id ) {
  $user = .....
  require __DIR__ . '/views/user/details.php';
});

## Features

* Can be used with all HTTP Methods
* Dynamic routing with named route parameters
* Reversed routing
* Flexible regular expression routing (inspired by [Sinatra](http://www.sinatrarb.com/))
* Custom regexes

## Getting started

You need PHP >= 5.6 to use Router.

- [Install AltoRouter](http://altorouter.com/usage/install.html)
- [Rewrite all requests to AltoRouter](http://altorouter.com/usage/rewrite-requests.html)
- [Map your routes](http://altorouter.com/usage/mapping-routes.html)
- [Match requests](http://altorouter.com/usage/matching-requests.html)
- [Process the request your preferred way](http://altorouter.com/usage/processing-requests.html)

## Contributors
- [Danny van Kooten](https://github.com/dannyvankooten)
- [Koen Punt](https://github.com/koenpunt)
- [John Long](https://github.com/adduc)
- [Niahoo Osef](https://github.com/niahoo)
- [Hakim Ch](https://github.com/HakimCh)

## License

(MIT License)

Copyright (c) 2017 Hakim Ch <ab.chmimo@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
