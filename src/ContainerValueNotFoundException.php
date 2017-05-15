<?php

namespace Syringe;


use Exception;
use Interop\Container\Exception\NotFoundException;

class ContainerValueNotFoundException extends Exception implements NotFoundException
{
}