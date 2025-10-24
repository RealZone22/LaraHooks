<?php

namespace RealZone22\LaraHooks;

class Callback
{
    protected $function;

    protected $parameters = [];

    protected $run = true;

    public function __construct($function, $parameters = [])
    {
        $this->setCallback($function, $parameters);
    }

    public function setCallback($function, $parameters)
    {
        $this->function = $function;
        $this->parameters = $parameters;
    }

    public function call($parameters = null)
    {
        if ($this->run && is_callable($this->function)) {
            $this->run = false;
            $params = $parameters ?: $this->parameters;

            if (is_array($params) && ! array_is_list($params)) {
                $params = [$params];
            }

            return call_user_func_array($this->function, $params);
        }
    }

    public function reset()
    {
        $this->run = true;
    }
}
