<?php

/**
 * A simple router for your PHP applications. Inspired by Dan Van Kooten's Altorouter 
 * 
 * https://github.com/dannyvankooten/AltoRouter
 * 
 * @version 0.1
 * @author Mike Timms
 */
 
namespace Junction;

use Exception;

class Router {
    
    /**
     * Contains all the registered routes
     * 
     * @var array
     */ 
    private $__routes = [];
    
    private $__groupRoute = '';
    
    /**
     * Handle the request made to the "front controller".
     * Take apart the path requested and try to find a matching
     * route.
     * 
     * @param bool $executePayload - Whether to execute the payload or return it. Default is to execute
     * @throws Exception
     * @return mixed
     */ 
    public function handleRequest($executePayload = true) {
        $path = $this->__getPath($_SERVER['REQUEST_URI']);
        $method = $_SERVER['REQUEST_METHOD'];
        
        foreach ($this->__routes[$method] as $route) {
            $vars = [];
            $matched = 0;
            
            foreach ($route['path'] as $index => $part) {
                // handle a path segment
                if ($part['type'] === 'segment' && isset($path[$index])) {
                    if ($path[$index] == $part['value']) {
                        $matched++;
                        continue;
                    } else {
                        break;
                    }
                } 
                
                // handle a path variable
                if ($part['type'] === 'var' && (isset($path[$index]) || $part['optional'] == true)) {
                    // validate the variable
                    $valid = true;
                    
                    if (isset($route['validation'])) {
                        foreach ($route['validation'][$part['varName']] as $validator) {
                            if (is_callable($validator)) {
                                $valid = $valid && call_user_func_array($validator, [$path[$index]]);
                            }
                        }
                    }
                    
                    if ($valid) {
                        $vars[$part['varName']] = isset($path[$index]) ? $path[$index] : null;
                        $matched++;
                        continue;
                    } 
                }
            }
            
            // did we match all the elements?
            // TODO: Work on this. Currently this will match /hello/:name with /hello/mike, but also /hello/mike/timms
            // these should really be considered different routes, unless the route was /hello/:name/*
            // so $matched == count() is OK, IFF the final route part is '*' OR $matched also == count($path)?
            if ($matched == count($route['path'])) {
                // execute the payload, passing in the $vars array as arguments
                if ($executePayload) {
                    return call_user_func_array($route['payload'], $vars);
                }
                
                // return the payload and any values in $vars for the user to do with as they please
                return [
                    'payload' => $route['payload'],
                    'vars' => $vars,
                ];
            }
        }
        
        // no matching routes
        throw new Exception('No matching route found');
    }
    
    /**
     * Given a path break it up and return the array
     * 
     * @param string $path - The path to work on
     * @return array
     */
    private function __getPath($path) {
        $path = trim($path, '/');
        $path = explode('/', $path);
        return $path;
    }
    
    /**
     * Register a route, along with (optional) validation and the payload to run/return
     * 
     * @param string $path - A string describing the path to match of the format "METHOD path/:var/:optional_var? AS NamedRoute"
     * @param mixed $validation - Either an array with keys matching route variables and entries as functions to validate the value, OR a callable to be used as $payload
     * @param callable $payload - A callable that will be run or returned if the route is matched
     * @throws Exception
     * @return mixed
     */
    public function add($path, $payload = null) {
        $path = trim($path, '/');
        $path = explode(' ', $path);
        $method = $path[0];
        $name = isset($path[3]) ? $path[3] : null;
        $path = $path[1];
        $path = $this->__groupRoute . $path;
        
        $path = $this->__getPath($path);
    
        foreach ($path as &$part) {
            if (strpos($part, ':') === 0) {
                // variable
                $varName = substr($part, strpos($part, ':') + 1);
                $optional = strpos($varName, '?') !== false ? true : false;
                $varName = str_replace('?', '', $varName);
                
                $part = [
                    'type' => 'var',
                    'varName' => $varName,
                    'value' => $part,
                    'optional' => $optional,
                ];
            } else {
                $part = [
                    'type' => 'segment',
                    'value' => $part,
                ];
            }
        }
        
        if (!is_callable($payload)) {
            throw new Exception('Route payload must be a function.');
        }
        
        if ($name === null) {
            $this->__routes[$method][] = [
                'path' => $path, 
                'payload' => $payload,
            ];
        }
        
        if ($name !== null) {
            $this->__routes[$method][$name] = [
                'path' => $path, 
                'payload' => $payload,
            ];
        }
        
        $this->currentPath = [
            'method' => $method,
            'path' => $path,
        ];
        
        return $this;
    }
    
    /**
     * When matching a route validate the path variable $varName by passing it to
     * $callable. If callable returns truthy the valid, else invalid
     * 
     * @param string $varName - The name of the variable in the route
     * @param callable $callable - The validation function
     * @return object
     */
    public function validate($varName, $callable) {
        $route = array_pop($this->__routes[$this->currentPath['method']]);
        $route['validation'][$varName][] = $callable;
        $this->__routes[$this->currentPath['method']][] = $route;
        
        return $this;
    }
    
    public function group($groupRoute, $callback) {
        $groupRoute = trim($groupRoute, '/');
        $restoreGroupRoute = $this->__groupRoute;
        
        $this->__groupRoute = $groupRoute;
        call_user_func($callback, $this);
        
        $this->__groupRoute = $restoreGroupRoute;
    }
}