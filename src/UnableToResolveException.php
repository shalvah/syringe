<?php
/**
 * Created by PhpStorm.
 * User: J
 * Date: 19/05/2017
 * Time: 23:53
 */

namespace Syringe;


class UnableToResolveException extends \Exception
{

    /**
     * UnableToResolveException constructor.
     * @param string $string
     */
    public function __construct($string)
    {
    }
}