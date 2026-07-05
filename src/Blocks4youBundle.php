<?php

namespace C4Y\Blocks4you;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Blocks4youBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}