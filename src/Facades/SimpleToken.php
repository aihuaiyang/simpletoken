<?php

namespace Huaiyang\SimpleToken\Facades;

use Illuminate\Support\Facades\Facade;

class SimpleToken extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'simpletoken';
    }
}