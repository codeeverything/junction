<?php

class Router {
    
    private $__routes = [];
    
    public function handleRequest() {
        $path = $this->__getPath($_SERVER['PATH_INFO']);
        $method = $_SERVER['REQUEST_METHOD'];
        
        // print_r($path);
        
        $vars = [];
        
        foreach ($this->__routes[$method] as $route) {
            $matched = 0;
            foreach ($route['path'] as $index => $part) {
                // print_r($part);
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
                // echo "route matched";
                // print_r($vars);
                return call_user_func_array($route['payload'], $vars);
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
        
        // print_r($path);
        
        if (!is_callable($payload)) {
            throw new Exception('Route payload must be a function.');
        }
        
        $this->__routes[$method][] = [
            'path' => $path, 
            'payload' => $payload,
        ];
        
        // print_r($this->__routes);
    }
}

$router = new Router();
$router->add('GET hello/:name?/:age?', [
        'name' => [
            function ($val) {
                return strlen($val) < 5;
            },
        ],
    ], function ($name, $age) {
    $name = isset($name) ? $name : 'World';
    $age = isset($age) ? $age : 'old';
    echo "Hello, $name. You're $age";
    
    return [
        'foo' => 'bar',
    ];
});

$router->add('POST /hello/:name?/:age?', function ($name, $age) {
    $name = isset($name) ? $name : 'World';
    $age = isset($age) ? $age : 'old';
    echo "Hello, $name. You're $age - so get bent";
});

var_dump($router->handleRequest());