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
    
    private $__routes = [];
    
    public function handleRequest($executePayload = true) {
        $path = $this->__getPath($_SERVER['PATH_INFO']);
        $method = $_SERVER['REQUEST_METHOD'];
        
        $vars = [];
        
        foreach ($this->__routes[$method] as $route) {
            $matched = 0;
            foreach ($route['path'] as $index => $part) {
                if ($part['type'] === 'segment' && $path[$index] == $part['value']) {
                    // echo "Good, part $index matches";
                    $matched++;
                    continue;
                }
                
                if ($part['type'] === 'var' && (isset($path[$index]) || $part['optional'] == true)) {
                    // echo "Good, we found a value for var {$part['varName']} at path index $index: {$path[$index]}";
                    
                    // validate the variable
                    $valid = true;
                    foreach ($part['validation'] as $validator) {
                        if (is_callable($validator[0])) {
                            $valid = $valid && call_user_func_array($validator[0], [$path[$index]]);
                        }
                    }
                    
                    if ($valid) {
                        $vars[$part['varName']] = $path[$index];
                        $matched++;
                        continue;
                    } else {
                        throw new Exception("Route variable {$part['varName']} with value {$path[$index]} could not be validated.");
                    }
                }
            }
            
            // did we match all the elements?
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
        http_response_code(404);
        throw new Exception('Not found');
    }
    
    private function __getPath($path) {
        $path = trim($path, '/');
        $path = explode('/', $path);
        return $path;
    }
    
    public function add($path, $validation, $payload = null) {
        if (is_callable($validation)) {
            $payload = $validation;
            $validation = [];
        }
        
        if(!is_array($validation)) {
            throw new Exception('Router::add validation must be an array');
        }
        
        $path = explode(' ', $path);
        $method = $path[0];
        $name = isset($path[3]) ? $path[3] : null;
        $path = $path[1];
        
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
                    'validation' => $validation,
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
            $this->__routes[$method][] = [
                'path' => $path, 
                'payload' => $payload,
            ];
        }
    }
}