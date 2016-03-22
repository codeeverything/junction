# Junction
### A simple PHP router

Junction is a simple PHP router. Right now it's created as a personal project out of interest, but I plan to use it as the default router in my micro-RESTful PHP framework [Planck](https://www.github.com/codeeverything/planck-mvc).


## Open Source

Comments and suggestions welcome. This open source, so get forking and PRing if you want! :)

## Example Usage

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

You can validate the path variables in your route by providing a list of validation callbacks

```php
$this->router->add('GET /hello/:name', [
    'name' => [
        function ($value) {
            // only accept short names
            return strlen($value) < 5;
        },
    ],
], function ($name) {
    return 'Hello, ' . $name;
});
```

Validation errors will raise an ```Exception``` detailing the variable that failed validation and the invalid value.