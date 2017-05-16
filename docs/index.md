# Syringe
Syringe is a small dependency injection container for PHP.

Dependency injection is a programming practice in which classes which depend on other classes or values automatically Have these dependencies passed to them instead of having to create them. These useful classes and values are often known as services and parameters respectively. For more on dependency injection, see [here](https://code.tutsplus.com/tutorials/dependency-injection-in-php--net-28146).

## Getting a container instance
To bind to the container, you need to obtain an instance of `Syringe\Container`:
```php
$c = new \Syringe\Container();
```

## Binding to the container
### Binding Primitives
Primitive types include array, int, float, and string. To bind them, simply pass the appropriate key and value to the `bindValue` function. 
```php
$c->bindValue("user.name", "John");
$c->bindValue("user.grades", ["math" => 90, "english" => 75]);
```

You may also pass in a closure that returns a value:
```php
$c->bindValue("now.time", function ($c) {
  return microtime(true);
}):
```
### Binding Classes
To bind a class, simply pass the appropriate key and value to the `bindClass` function. 

You may pass a class name as value:
```php
$c->bindClass("logger", \Monolog\Logger::class);
```

Or a closure returning an instance of that class
```php
$c->bindClass("logger", function($c) {
  $configArray = [];
  return new \Monolog\Logger($configArray);
});
```

You can also use a class name as a key (useful for constructor injection):
```php
$c->bindClass("\Support\LoggerInterface", \Monolog\Logger::class);
```

### Binding Shared Services
If you wish the same instance of a class (for example, a database connection) to be returned whenever you access the service, you should use the `bindInstance` method:

You may pass in the already instantiateD object:
````php
$pdo = new PDO(...);
$c->bindInstance("database", $pdo);
````

Or a closure which returns an object instance. The closure will be called once, the first time you request the service. Subsequent requests will receive the same service.
```php
$c->bindInstance("database", function ($c) {
  return new PDO("...");
});
```

### The `bind` method
As an alternative to all the options listed above, you may use the  `bind` method to Bins classes or values. This method accepts a wide variety of formats, and tries to automatically figure out the type of valuue being bound:
```php
$c->bind("user.name", "John");
$c->bind("user.grades", ["math" => 90, "english" => 75]);

$c->bind("logger", \Monolog\Logger::class);
$c->bind("\Support\LoggerInterface", function($c) {
  $configArray = [];
  return new \Monolog\Logger($configArray);
});
```

Note that when using `bind`, The only way to bind shared services is by passing in the object instance
```php
$pdo = new PDO(...);
$c->bind("db.conn", $pdo);

//this will bind a non-shared class
$c->bind("db.conn", function ($c) {
  return new PDO("...");
});
```

### Accessing Other Bindings in Closures
When binding a value via a closure, the slosure takes one parameter: a Container object. You may interact with the container as normal (except binding) from within the closure
```php
$c->bind("user.details", function ($c) {
  $name = $c->get("user.name");
  $age = $c->get("user.age");
  #return [$name, $age];
});
```

### Extending a value defintion
If you wish to modify a service definition, apart from re-binding it, you may use the `extend` method, which takes the key and a closure as parameters. The closure takes the value as a first parameter and the container instance as the second.
```php
$c->bind("storage", \Redis\RedisStorage::class):
$c->extend("storage", function ($storage, $c) {
  $storage->setSomething(true);
});
```

## Retrieving Values from the Container
To retrieve a value from the container, simply call `get` with the key as parameter
```php
$c->bind("db.conn", function ($c) {
  return new PDO("...");
});
$pdo = $c->get("db.conn");
$pdo->query("...");
```

If you have not bound any values to that key, a `ContainerValueNotFoundException` will be thrown.

To check if any value has been bound to a specified key, use the `has` method:
```php
if ($c->has("logger") {
  $logger = $c->get("logger");
}
```

### Accessing the Raw Definition
If you wish to access the raw definition for a service or parameter, you should use the `raw` method:

```php
$c->bindValue("now.time", function ($c) {
  return microtime(true);
}):
//this will return the closure
$getTime = $c->raw("now.time");

$getTime();
```