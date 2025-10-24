<?php

namespace RealZone22\LaraHooks;

use Illuminate\Support\Arr;

class LaraHooks
{
    protected $watch = [];
    protected $stop = [];
    protected $mock = [];
    protected $testing = false;

    /**
     * Stop all another hook running.
     *
     * @param string $hook LaraHooks name
     */
    public function stop($hook)
    {
        $this->stop[$hook] = true;
    }

    /**
     * Subscribe to hook.
     *
     * @param string $hook LaraHooks name
     * @param $priority
     * @param $function
     */
    public function listen($hook, $function, $priority = null)
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2];

        if (in_array(Arr::get($caller, 'function'), ['include', 'require'])) {
            $caller = debug_backtrace(null, 4)[3];
        }

        if (empty($this->watch[$hook])) {
            $this->watch[$hook] = [];
        }

        if (!is_numeric($priority)) {
            $priority = null;
        }

        $this->watch[$hook][$priority] = [
            'function' => $function,
            'caller' => [
                'class' => Arr::get($caller, 'class'),
            ],
        ];

        ksort($this->watch[$hook]);
    }

    /**
     * Return the hook answer.
     *
     * @param string $hook LaraHooks name
     * @param array $params
     * @param callable $callback
     * @param string $htmlContent content wrapped by hook
     *
     * @return null|void
     */
    public function get($hook, $params = [], ?callable $callback = null, $htmlContent = '')
    {
        $callbackObject = $this->createCallbackObject($callback, $params);

        $output = $this->returnMockIfDebugModeAndMockExists($hook);
        if ($output) {
            return $output;
        }

        $output = $this->run($hook, $params, $callbackObject, $htmlContent);

        if (!$output) {
            $output = $callbackObject->call();
        }

        unset($callbackObject);

        return $output;
    }

    /**
     * Return a new callback object.
     *
     * @param callable $callback function
     * @param array $params parameters
     *
     * @return Callback
     */
    protected function createCallbackObject($callback, $params)
    {
        return new Callback($callback, $params);
    }

    /**
     * Return the mock value.
     *
     * @param string $hook LaraHooks name
     *
     * @return null|mixed
     */
    protected function returnMockIfDebugModeAndMockExists($hook)
    {
        if ($this->testing) {
            if (array_key_exists($hook, $this->mock)) {
                $output = $this->mock[$hook]['return'];
                unset($this->mock[$hook]);

                return $output;
            }
        }
    }

    /**
     * Run hook events.
     *
     * @param string $hook LaraHooks name
     * @param array $params Parameters
     * @param Callback $callback Callback object
     * @param string $output html wrapped by hook
     *
     * @return mixed
     */
    protected function run($hook, $params, Callback $callback, $output = null)
    {
        if (array_key_exists($hook, $this->watch)) {
            if (is_array($this->watch[$hook])) {
                foreach ($this->watch[$hook] as $function) {
                    if (!empty($this->stop[$hook])) {
                        unset($this->stop[$hook]);
                        break;
                    }

                    $result = call_user_func($function['function'], $callback, $output, $params);
                    if ($result !== null) {
                        $output = $result;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Return all registered hooks.
     *
     * @return array
     */
    public function getHooks()
    {
        $hookNames = (array_keys($this->watch));
        ksort($hookNames);

        return $hookNames;
    }

    /**
     * Return all listeners for hook.
     *
     * @param string $hook
     *
     * @return array
     */
    public function getEvents($hook)
    {
        $output = [];

        foreach ($this->watch[$hook] as $key => $value) {
            $output[$key] = $value['caller'];
        }

        return $output;
    }

    /**
     * For testing.
     *
     * @param string $name LaraHooks name
     * @param mixed $return Answer
     */
    public function mock($name, $return)
    {
        $this->testing = true;
        $this->mock[$name] = ['return' => $return];
    }

    /**
     * Return the listeners.
     *
     * @return array
     */
    public function getListeners()
    {
        return $this->watch;
    }
}
