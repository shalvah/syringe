<?php

namespace Syringe;

use interop\Container\ContainerInterface;

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
    protected static $instances;

    /**
     * @var array
     */
    protected static $values;

    /**
     * @var array
     */
    protected static $classes;

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
        if (is_callable($value)) {
            $this->bindClass($key, $value);
        } else if (is_object($value)) {
            $this->bindSingleton($key, $value);
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
        self::$classes[$key] = $value;
    }

    /**
     * Bind an object instance to the container
     *
     * @param string $key
     * @param $object
     */
    public function bindSingleton(string $key, $object)
    {
        self::$instances[$key] = $object;
    }

    /**
     * Bind a primitive value to the cntainer
     *
     * @param string $key
     * @param mixed $value
     */
    public function bindValue(string $key, $value)
    {
        self::$values[$key] = $value;
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        if (isset(self::$values[$key])) {
            if (class_exists($result = self::$values[$key])) {
                $result = new $result();
            }
        } else if (isset(self::$instances[$key])) {
            if(is_callable($result = self::$instances[$key])) {
                $result = call_user_func_array($result, [$this]);
            }
        } else if (isset(self::$classes[$key])) {
            if (is_callable($result = self::$classes[$key])) {
                $result = call_user_func_array($result, [$this]);
            } else if (class_exists($result)) {
                $result = new $result();
            }
        } else {
            throw new ContainerValueNotFoundException("You haven't bound anything for $key.");
        }

        //execute any extensions
        if(isset(self::$extensions[$key])) {
            foreach (self::$extensions[$key] as $extra) {
                $result = call_user_func_array($extra, [$result, $this]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return isset(self::$values[$key]) || isset(self::$instances[$key]) || isset(self::$classes[$key]);
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
     * @param string $key
     * @param callable $func
     */
    public function once(string $key, callable $func)
    {
        self::$instances[$key] = $func;
    }

    /**
     * Retrieve the original raw binding for a bound value (without evaluating)
     *
     * @param string $key
     * @return mixed
     */
    public function raw(string $key)
    {
        if (isset(self::$values[$key])) {
            $result = self::$values[$key];
        } else if (isset(self::$instances[$key])) {
            $result = self::$instances[$key];
        } else if (isset(self::$classes[$key])) {
            $result = self::$classes[$key];
        } else {
            throw new ContainerValueNotFoundException("You haven't bound anything for $key.");
        }

        return $result;
    }
}