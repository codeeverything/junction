<?php

namespace Junction\Test;

use Junction\Router;

class JunctionTest extends \PHPUnit_Framework_TestCase {
    
    public function setup() {
        $this->router = new Router();
    }
    
    public function tearDown() {
        unset($this->router);
    }

    public function testBadSimpleRoute() {
        $this->setExpectedException('Exception');
        $this->router->add('GET /hello', 'not callable');
    }
    
    public function testSimpleRoute() {
        $this->router->add('GET /hello', function () {
            return 'Hello, world';
        });
        
        $_SERVER['PATH_INFO'] = '/hello';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $response = $this->router->handleRequest();
        $this->assertEquals($response, 'Hello, world');
    }
    
    public function testSimpleRouteWithParam() {
        $this->router->add('GET /hello/:name', function ($name) {
            return 'Hello, ' . $name;
        });
        
        // test with name param var passed
        $_SERVER['PATH_INFO'] = '/hello/Joe';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $response = $this->router->handleRequest();
        $this->assertEquals($response, 'Hello, Joe');
        
        // test without name param var passed
        $this->setExpectedException('Exception');
        $_SERVER['PATH_INFO'] = '/hello';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $response = $this->router->handleRequest();
    }
    
    public function testSimpleRouteWithOptionalParam() {
        $this->router->add('GET /hello/:name?', function ($name) {
            $name = isset($name) ? $name : 'world';
            return 'Hello, ' . $name;
        });
        
        // test with name param var passed
        $_SERVER['PATH_INFO'] = '/hello/Joe';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $response = $this->router->handleRequest();
        $this->assertEquals($response, 'Hello, Joe');
        
        // test without name param var passed
        $_SERVER['PATH_INFO'] = '/hello';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $response = $this->router->handleRequest();
        $this->assertEquals($response, 'Hello, world');
    }
    
    public function testSimpleRouteWithValidatedParam() {
        $this->router->add('GET /hello/:name', [
            'name' => [
                function ($value) {
                    return strlen($value) < 5;
                },
            ],
        ], function ($name) {
            return 'Hello, ' . $name;
        });
        
        // test with short name param var passed
        $_SERVER['PATH_INFO'] = '/hello/Joe';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $response = $this->router->handleRequest();
        $this->assertEquals($response, 'Hello, Joe');
        
        // test with longer name param var passed
        $this->setExpectedException('Exception');
        $_SERVER['PATH_INFO'] = '/hello/Alexander';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $response = $this->router->handleRequest();
    }
}