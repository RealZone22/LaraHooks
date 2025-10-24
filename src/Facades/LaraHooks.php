<?php

namespace RealZone22\LaraHooks\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RealZone22\LaraHooks\LaraHooks
 */
class LaraHooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RealZone22\LaraHooks\LaraHooks::class;
    }
}
