<?php

namespace Syringe;

use Interop\Container\ContainerInterface;

/**
 * The dependency injction container
 *
 * Class Container
 * @package Syringe
 */
class Container implements ContainerInterface
{
    /**
     * @var array
     */
    protected static $instanceBindings;

    /**
     * @var array
     */
    protected static $valueBindings;

    /**
     * @var array
     */
    protected static $classBindings;

    /**
     * Shared services which have been instantiated by the container
     *
     * @var array
     */
    protected static $instances;

    /**
     * @var array
     */
    protected static $extensions;

    /**
     * Bind a class or parameter to the container
     *
     * @param string $key
     * @param callable|object|mixed $value
     */
    public function bind(string $key, $value)
    {
        if (is_object($value)) {
            $this->bindInstance($key, $value);
        } else if (interface_exists($key) || class_exists($key)) {
            $this->bindClass($key, $value);
        } else if (is_callable($value)) {
            $this->bindClass($key, $value);
        } else if (is_string($value) && class_exists($value)) {
            $this->bindClass($key, $value);
        } else {
            $this->bindValue($key, $value);
        }
    }

    /**
     * Bind a class to the container
     *
     * @param string $key
     * @param $value
     */
    public function bindClass(string $key, $value)
    {
        self::$classBindings[$key] = $value;
    }

    /**
     * Bind an object instance to the container
     *
     * @param string $key
     * @param $object
     */
    public function bindInstance(string $key, $object)
    {
        self::$instanceBindings[$key] = $object;
        unset(self::$instances[$key]);
    }

    /**
     * Bind a primitive value to the cntainer
     *
     * @param string $key
     * @param mixed $value
     */
    public function bindValue(string $key, $value)
    {
        self::$valueBindings[$key] = $value;
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        if (isset(self::$valueBindings[$key])) {
            $result = self::$valueBindings[$key];
            if (is_callable($result)) {
                $result = call_user_func_array($result(), [$this]);
            }
        } else if (isset(self::$instanceBindings[$key])) {
            //check if already instantiated
            if (isset(self::$instances[$key])) {
                $result = self::$instances[$key];
            } else if (is_callable($result = self::$instanceBindings[$key])) {
                $result = call_user_func_array($result, [$this]);
                self::$instances[$key] = $result;
            } else if (is_string($result) && class_exists($result)) {
                // if object name
                $result = new $result();
            }
            //if object
        } else if (isset(self::$classBindings[$key])) {
            if (is_callable($result = self::$classBindings[$key])) {
                $result = call_user_func_array($result, [$this]);
            } else if (is_string($result) && class_exists($result)) {
                $result = new $result();
            }
            // if primitive
        } else {
            throw new ContainerValueNotFoundException("You haven't bound anything for $key");
        }

        //execute any extensions
        if(isset(self::$extensions[$key])) {
            foreach (self::$extensions[$key] as $extra) {
                $result = call_user_func_array($extra, [$result, $this]);
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return isset(self::$valueBindings[$key]) || isset(self::$instanceBindings[$key]) || isset(self::$classBindings[$key]);
    }

    /**
     * Modify an already bound value
     *
     * @param string $key
     * @param callable $func
     */
    public function extend(string $key, callable $func)
    {
        if(!$this->has($key)) {
            throw new ContainerValueNotFoundException("You haven't bound anything for $key");
        }
        if(isset(self::$extensions[$key])) {
            self::$extensions[$key][] = $func;
        } else {
            self::$extensions[$key] = [$func];
        }
    }

    /**
     * Retrieve the original raw binding for a bound value (without evaluating)
     *
     * @param string $key
     * @return mixed
     */
    public function raw(string $key)
    {
        if (isset(self::$valueBindings[$key])) {
            $result = self::$valueBindings[$key];
        } else if (isset(self::$instanceBindings[$key])) {
            $result = self::$instanceBindings[$key];
        } else if (isset(self::$classBindings[$key])) {
            $result = self::$classBindings[$key];
        } else {
            throw new ContainerValueNotFoundException("You haven't bound anything for $key.");
        }

        return $result;
    }
}