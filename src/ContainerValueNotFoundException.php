<?php

namespace Syringe;


use Exception;
use Interop\Container\Exception\NotFoundException;

class ContainerValueNotFoundException extends Exception implements NotFoundException
{

    /**
     * ContainerValueNotFoundException constructor.
     * @param string $string
     */
    public function __construct($string)
    {
    }
}