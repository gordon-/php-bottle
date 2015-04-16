<?php

require_once realpath(dirname(__FILE__).'/../../build/bottle.phar');

/**
 * Unit tests for Bottle class
 *
 * @package PhpBottle
 * @author  Damien Nicolas <damien@gordon.re>
 * @version 0.2
 * @license MIT
 */
class BottleTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->bottle = new Bottle();
        $this->bottle->setViewsPath(realpath(dirname(__FILE__))
                                    .DIRECTORY_SEPARATOR.'fixtures'
                                    .DIRECTORY_SEPARATOR.'views');
    }

    private function setUrl($url) {
        $r = $this->bottle->getRequest();
        $r->setURL($url);
        $this->bottle->setRequest($r);
    }

    public function testConstructor() {
        $this->assertCount(2, $this->bottle->getHandlers(),
                            'A generic Bottle object should contain 2 handlers:'
                           .' 404 and Exception');

    }

    public function testRequest() {
        $r = $this->bottle->getRequest();
        $this->assertInstanceOf('Bottle_Request', $r,
                                'Bottle::getRequest() should return a '
                               .'Bottle_Request object');
    }

    public function test404Handler() {
        $r = $this->bottle->getRequest();
        $h = $this->bottle->getHandler();
        $this->assertInstanceOf('Bottle_Handler_404', $h,
                                'Handler for unknown request should be an '
                               .'instance of Bottle_Handler_404');
        $resp = $h->run();
        $this->assertInstanceOf('Bottle_Response', $resp,
                                'Bottle_Handler::run() should return a '
                               .'Bottle_Response object');
        $this->assertEquals(404, $resp->getCode(),
                            'Bottle_Handler_404 should have an HTTP code of '
                           .'404');
    }

    public function testHandler() {
        function demoHandler($request) {
            return ['key' => 'value'];
        }
        $route = '/demo';
        $view = 'demo.html';
        $this->bottle->addHandler('demoHandler', $route, $view);
        $this->assertCount(3, $this->bottle->getHandlers());
        $this->setURL('/demo');
        $resp = $this->bottle->getHandler()->run();
        $this->assertEquals('key: value', $resp->__toString());
        $this->assertEquals(200, $resp->getCode());
    }

    public function testDepInjection() {
        function injectionHandler($response, $request, $param) {
            return ['text' => '$response is a '.get_class($response).PHP_EOL
                             .'$request is a '.get_class($request).PHP_EOL
                             .'$param is '.$param];
        }

        $route = '/depinjection/:param';
        $view = 'depinjection.html';
        $this->bottle->addHandler('injectionHandler', $route, $view);
        $this->setURL('/depinjection/param_value');
        $resp = $this->bottle->getHandler()->run();
        $this->assertEquals('$response is a Bottle_Response'.PHP_EOL
                           .'$request is a Bottle_Request'.PHP_EOL
                           .'$param is param_value', $resp->__toString());
    }

    public function test404Fallback() {
        function nopeHandler() {
            return [];
        }
        $route = '/nope';
        $view = 'demo.html';  // will not be used
        $this->bottle->addHandler('nopeHandler', $route, $view);
        $this->setURL('/yep');
        $resp = $this->bottle->getHandler()->run();
        $this->assertEquals(404, $resp->getCode(),
                            'There shouldn\'t be a handler for this URL');
    }

    public function test500Fallback() {
        function derpHandler() {
            throw new Exception('DERP');
        }
        $route = '/derp';
        $view = 'demo.html';  // will not be used
        $this->bottle->addHandler('derpHandler', $route, $view);
        $this->setURL('/derp');
        $h = $this->bottle->getHandler();
        $this->assertInstanceOf('Bottle_Handler_Exception', $h);
        $resp = $h->run();
        $this->assertEquals(500, $resp->getCode());
    }

    public function testMultipleURLs() {
        function handler($request) {
            return ['key' => 'value'];
        }
        $routes = ['/url1', '/second/url'];
        $view = 'demo.html';
        $this->bottle->addHandler('handler', $routes, $view);
        $this->setURL('/url1');
        $resp = $this->bottle->getHandler()->run();
        $this->assertEquals('key: value', $resp->__toString());

        $this->setURL('/second/url');
        $resp = $this->bottle->getHandler()->run();
        $this->assertEquals('key: value', $resp->__toString());
    }

    public function testMultipleHandlers() {
        function firstHandler() {
            return ['key' => 'first'];
        }

        function secondHandler() {
            return ['key' => 'second'];
        }

        $route = '/multiple';
        $view = 'demo.html';
        $this->bottle->addHandler('firstHandler', $route, $view);
        $this->bottle->addHandler('secondHandler', $route, $view);
        $this->setURL('/multiple');
        $resp = $this->bottle->run();
        $this->assertEquals('key: first', $resp->__toString());
    }

    public function testForward() {
        function forwardHandler() {
            throw new Bottle_Exception_Forward();
        }

        function forwardedHandler() {
            return ['key' => 'value'];
        }

        $route = '/forward';
        $view = 'demo.html';
        $this->bottle->addHandler('forwardHandler', $route, $view);
        $this->bottle->addHandler('forwardedHandler', $route, $view);
        $this->setURL('/forward');
        $resp = $this->bottle->run();
        $this->assertEquals('key: value', $resp->__toString());
    }

}
