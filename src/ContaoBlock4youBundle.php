<?php

namespace C4Y\Block4you;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoBlock4youBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}