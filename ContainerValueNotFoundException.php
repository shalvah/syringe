<?php
/**
 * Created by PhpStorm.
 * User: J
 * Date: 15/05/2017
 * Time: 00:02
 */

namespace Syringe;


use Exception;
use Psr\Container\NotFoundExceptionInterface;

class ContainerValueNotFoundException extends Exception implements NotFoundExceptionInterface
{

    /**
     * ContainerValueNotFoundException constructor.
     * @param string $string
     */
    public function __construct($string)
    {
    }
}