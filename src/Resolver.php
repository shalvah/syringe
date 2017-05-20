<?php
/**
 * Created by PhpStorm.
 * User: J
 * Date: 20/05/2017
 * Time: 09:57
 */

namespace Syringe;


use Syringe\Exceptions\UnableToResolveException;

class Resolver
{
    /**
     * The Syringe container instance used by the resolver
     *
     * @var Container
     */
    private $cont;

    /**
     * Config option - should the container pass null for unresolved parameters?
     *
     * @var bool
     */
    private $defaultNull = false;

    /**
     * Creates a new Resolver.
     *
     * @param array $config Settings to be used when resolving
     */
    public function __construct(array $config = [])
    {
        $this->cont = new Container();
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Call a class method, automatically resolving its dependencies
     *
     * @param string $classname
     * @param string $methodname
     * @param array $overrideArgs Associative array with parameter names as keys, and their desired values as the values.
     *       When calling $methodname, the Resolver will not attempt tp resolve any parameter names you specify here.
     *       Rather, it will pass in the specified value
     * @return mixed The result of the method call
     */
    public function call(string $classname, string $methodname, array $overrideArgs = [])
    {
        $classOverrideArgs = $overrideArgs["class"] ?? [];
        $instance = $this->resolveClass($classname, $classOverrideArgs);
        $methodOverrideArgs = $overrideArgs["method"] ?? [];
        return $this->resolveMethod($instance, $methodname, $methodOverrideArgs);
    }

    /**
     * Resolve a class dependencies and instantiate it (constructor injection)
     *
     * @param string $classname
     * @param array $constructorOverrideArgs Associative array with parameter names as keys, and their desired values as the values.
     *       When calling the class constructor, the Resolver will not attempt tp resolve any parameter names you specify here.
     *       Rather, it will pass in the specified value
     * @return \stdClass
     */
    public function resolveClass(string $classname, array $constructorOverrideArgs = [])
    {
        // if there is a binding for this class in the container, use that
        if ($this->cont->has($classname)){
            return $this->cont->get($classname);
        }

        $class = new \ReflectionClass($classname);

        // if the class constructor doesnt take any parameters, its easy
        $params = $class->getConstructor()->getParameters();
        if (count($params) < 1) {
            return new $classname();
        }

        $resolvedDependencies = [];

        // inject  the class dependencies
        // first try phpdoc
        if ($this->checkDoc($class)) {
            $resolvedDependencies = $this->resolveViaDoc($class, $constructorOverrideArgs, $resolvedDependencies);
        }
        // then try typehints
        $resolvedDependencies = $this->resolveViaTypehints($class, $constructorOverrideArgs, $resolvedDependencies);
        return $class->newInstanceArgs($resolvedDependencies);
    }

    /**
     * Call a method on a resolved class instance
     *
     * @param \StdClass $instance The class instance
     * @param string $methodname
     * @param array $methodOverrideArgs Associative array with parameter names as keys, and their desired values as the values.
     *       When calling the class method, the Resolver will not attempt tp resolve any parameter names you specify here.
     *       Rather, it will pass in the specified value
     * @return mixed
     */
    public function resolveMethod(\StdClass $instance, string $methodname, array $methodOverrideArgs = [])
    {
        $reflectedClass = new \ReflectionClass($instance);

        // if the method  doesnt take any parameters, its easy
        $params = $reflectedClass->getConstructor()->getParameters();
        $resolvedDependencies = [];
        if (count($params) < 1) {
            $resolvedDependencies = [];
        } else {
            // first try phpdoc
            if ($this->checkDoc($reflectedClass, $methodname)) {
                $resolvedDependencies = $this->resolveViaDoc($reflectedClass, $methodOverrideArgs, $resolvedDependencies, $methodname);
            }
            // then typehints
            $resolvedDependencies = $this->resolveViaTypehints($reflectedClass, $methodOverrideArgs, $resolvedDependencies, $methodname);
        }
        return call_user_func_array([$instance, $methodname], $resolvedDependencies);
    }

    /**
     * Resolve method dependencies via @Inject directives in its PHPdoc
     *
     * @param \ReflectionClass $class
     * @param array $overrideArgs
     * @param array $args Already resolved dependencies
     * @param string $methodname An empty string means the constructor
     * @return array Resolved dependencies, with parameter names as keys
     */
    private function resolveViaDoc(\ReflectionClass $class, array $overrideArgs, array $args, string $methodname= "")
    {
        $doc = ($methodname === "") ? $class->getConstructor()->getDocComment()
            : $class->getMethod($methodname)->getDocComment();
        $parts = explode(" @Inject ", $doc);
        array_shift($parts); // remove the part before the Inject tag

        //get binding keys from inject tags
        $dependencies = array_map(function ($item) {
            preg_replace("/\\n/", " ", $item);
            $subParts = explode(" ", trim($item));
            return [
                "binding" => trim($subParts[0]),
                "param" => trim($subParts[1])
            ];
        }, $parts);

        foreach ($dependencies as $dependency) {
            // if a value was specified for the parameter, use that instead
            if (array_key_exists($dependency["param"], $overrideArgs)) {
                $args[$dependency["param"]] = $overrideArgs[$dependency["param"]];
                continue;
            }

            // if the binding key is a class name, resolve it
            if (class_exists($dependency["binding"])) {
                $args[$dependency["param"]] = $this->resolveClass($dependency["binding"]);
            } else {
                // if not, look for it in the container
                $args[$dependency["param"]] = $this->cont->get($dependency["binding"]);
            }
        }
        return $args;
    }

    /**
     * Resolve method dependencies via typehints on its methods
     *
     * @param \ReflectionClass $class
     * @param array $overrideArgs
     * @param array $args
     * @param string $methodname
     * @return array Resolved dependencies, with parameter names as keys
     * @throws UnableToResolveException
     */
    private function resolveViaTypehints(\ReflectionClass $class, array $overrideArgs, array $args, string $methodname = "")
    {
        $params = ($methodname === "") ? $class->getConstructor()->getParameters()
            : $class->getMethod($methodname)->getParameters();

        foreach ($params as $param) {
            $paramName = $param->getName();
            if (isset($args[$paramName])) {
                continue;
            }

            // if a value was specified for the parameter, use that instead
            if (array_key_exists($paramName, $overrideArgs)) {
                $args[$paramName] = $overrideArgs[$paramName];
                continue;
            } else if ($param->hasType()) {
                $paramType = $param->getType();
                if ($paramType->isBuiltin()) {
                    if ($this->cont->has($paramName)) {
                        $args[$paramName] = $this->cont->get($paramName);
                    }
                } else if ((new \ReflectionClass($paramType->__toString()))
                    ->isUserDefined()
                ) {
                    $this->resolveClass($paramType->__toString());
                } else if ($this->defaultNull) {
                    $args[$paramName] = null;
                } else {
                    throw new UnableToResolveException("Could not resolve parameter $paramName ({$paramType->__toString()}) of class {$class->name}");
                }
            } else {
                // parameter doesnt have a type,
                // and you didnt give me a default;
                // last resort is to check the bindings
                if ($this->cont->has($paramName)) {
                    $args[$paramName] = $this->cont->get($paramName);
                }
            }
        }
        return $args;
    }

    /**
     * Checks if a method uses the @Inject tag
     *
     * @param \ReflectionClass $class
     * @param string $methodname
     * @return bool
     */
    private function checkDoc(\ReflectionClass $class, string $methodname = "")
    {
        $doc = ($methodname === "") ? $class->getConstructor()->getDocComment()
            : $class->getMethod($methodname)->getDocComment();
        return (strpos($doc, " @Inject ") !== false);
    }

}