# Junction
### A simple PHP router

Junction is a simple PHP router. Right now it's created as a personal project out of interest, but I plan to use it as the default router in my micro-RESTful PHP framework [Planck](https://www.github.com/codeeverything/planck-mvc).


## Open source

Comments and suggestions welcome. This open source, so get forking and PRing if you want! :)

## Tests

Junction comes with a ~~complete~~ set of tests. 

You can run them with PHPUnit quite simply from your shell:

```
vendor/bin/phpunit
```

## Example usage

### Simple route

Let's define a simple route, with only one path segment

```php
// define the route
$this->router->add('GET /hello', function () {
    // just return a string for the application to then work on
    return 'Hello, world';
});

// ... more routes ...

// handle the request, matching routes if possible
$response = $this->router->handleRequest();
```

The first argument to the ```add()``` funciton is a string which starts with the HTTP verb to match on, followed by the path requested.

### Simple route with path variable

Junction allows you to simply define variables in your route, using a similar placeholder syntax to PDO queries on your DB

```php
// define the route - with required variable "name"
$this->router->add('GET /hello/:name', function ($name) {
    // just return a string for the application to then work on
    return 'Hello, ' . $name;
});

// ... more routes ...

// handle the request, matching routes if possible
$response = $this->router->handleRequest();
```

The variable "name" is passed in as the first argument in your handling function.

### Simple route with optional path variable

```php
// define the route - with optional variable "name"
$this->router->add('GET /hello/:name?', function ($name) {
    // just return a string for the application to then work on
    $name = isset($name) ? $name : 'world';
    return 'Hello, ' . $name;
});

// ... more routes ...

// handle the request, matching routes if possible
$response = $this->router->handleRequest();
```

If the "name" is given then we can use it, but if it's omitted the route will still match and we can fall back on the string "world".

### Variable validation

You can validate the path variables in your route by chaining validation callbacks after you've added your route.

The return value of your validation callback will be evaluated as ```truthy``` or ```falsy```, I'd recommend returning a ```bool``` if you can.

##### Validation on a single route

Let's look at an example of adding some validation:

```php
$this->router->add('GET /hello/:name/:age?', function ($name) {
    return 'Hello, ' . $name;
})->validate('name', function ($name) {
    // only allow strings and shorter names
    return (is_string($name) && strlen($name) < 10);
})->validate('age', function ($age) {
    // only allow ints between 1 and 99
    return (is_int($age) && ($age > 0 && $age < 100));
});
```

##### Validation using shared validation functions

Need to use the same validation for multiple variables? No problem, right now you can simply define a validation function then reference it in your validation callback, for example:

```php
function nameValidator($name) {
    // only allow strings and shorter names
    return (is_string($name) && strlen($name) < 10);
}

$this->router->add('GET /hello/:name', function ($name) {
    return 'Hello, ' . $name;
})->validate('name', nameValidator);
```

If you're worried about polluting the global scope with such functions you can wrap your route definitions within ```call_user_func()``` to create an immediately executing function. Taking the example above we get this:

```php
call_user_func(function () {
    function nameValidator($name) {
        // only allow strings and shorter names
        return (is_string($name) && strlen($name) < 10);
    }
    
    $this->router->add('GET /hello/:name', function ($name) {
        return 'Hello, ' . $name;
    })->validate('name', nameValidator);
});
```

##### Validation errors

Validation errors will raise an ```Exception``` detailing the variable that failed validation and the invalid value.