<?php

namespace Syringe;

use Closure;
use Psr\Container\ContainerInterface;

/**
 * Class Container
 * @package Syringe
 */
class Container implements ContainerInterface
{
    /**
     * @var
     */
    protected static $singletons;
    /**
     * @var
     */
    protected static $values;
    /**
     * @var
     */
    protected static $services;
    /**
     * @var
     */
    protected static $extras;

    /**
     * Bind a service or parameter to the container
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
     * @param string $key
     * @param $func
     */
    public function bindClass(string $key, $func)
    {
        self::$services[$key] = $func;
    }

    /**
     * @param string $key
     * @param $object
     */
    public function bindSingleton(string $key, $object)
    {
        self::$singletons[$key] = $object;
    }

    /**
     * @param string $key
     * @param $value
     */
    public function bindValue(string $key, $value)
    {
        self::$values[$key] = $value;
    }

    /**
     * @param $key
     */
    public function get($key)
    {
        if (isset(self::$values[$key])) {
            $value = self::$values[$key];
            if (class_exists($value)) {
                $result = new $value();
            } else {
                $result = $value;
            }
        } else if (isset(self::$singletons[$key])) {
            $result = self::$singletons[$key];
            if(is_callable($result)) {
                $result = call_user_func_array($result, [$this]);
            }
        } else if (isset(self::$services[$key])) {
            $result = call_user_func_array(self::$services[$key], [$this]);
        } else {
            throw new ContainerValueNotFoundException("You haven't bound anything for $key.");
        }

        //execute any extensions
        if(isset(self::$extras[$key])) {
            foreach (self::$extras[$key] as $extra) {
                $result = call_user_func_array($extra, [$result, $this]);
            }
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset(self::$values[$key]) || isset(self::$singletons[$key]) || isset(self::$services[$key]);
    }

    /**
     * Modify an already bound key
     *
     * @param string $key
     * @param Closure $func
     */
    public function extend(string $key, callable $func)
    {
        if(!$this->has($key)) {
            throw new ContainerValueNotFoundException("You haven't bound anything for $key");
        }
        if(isset(self::$extras[$key])) {
            self::$extras[$key][] = $func;
        } else {
            self::$extras[$key] = [$func];
        }
    }

    /**
     * @param string $key
     * @param callable $func
     */
    public function once(string $key, callable $func)
    {
        self::$singletons[$key] = $func;
    }

    /**
     * @param string $key
     * @return array
     */
    public function raw(string $key)
    {
        if (isset(self::$values[$key])) {
            $result = self::$values[$key];
        } else if (isset(self::$singletons[$key])) {
            $result = self::$singletons[$key];
        } else if (isset(self::$services[$key])) {
            $result = self::$services[$key];
        } else {
            throw new ContainerValueNotFoundException("You haven't bound anything for $key.");
        }

        $all = [$result];
        //add any extensions
        if(isset(self::$extras[$key])) {
            foreach (self::$extras[$key] as $extra) {
                $all[] = $extra;
            }
        }
        return $all;
    }
}