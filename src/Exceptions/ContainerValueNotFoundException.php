<?php

namespace Syringe\Exceptions;


use Exception;
use Interop\Container\Exception\NotFoundException;

class ContainerValueNotFoundException extends Exception implements NotFoundException
{
}