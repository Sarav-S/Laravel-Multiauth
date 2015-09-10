<?php

namespace Sarav\Multiauth;

use Closure;
use InvalidArgumentException;

abstract class Manager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * The array of created "drivers".
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * Configuration array for authentication
     * 
     * @var array
     */
    protected $config = [];

    /**
     * Holds the current user trying to login
     * 
     * @var string
     */
    protected $name;

    /**
     * Create a new manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    abstract public function getDefaultDriver();

    /**
     * Get a driver instance.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function driver($driver = null)
    {
        $driver = $driver ?: $this->getDefaultDriver();

        $this->drivers[$driver] = $this->createDriver($driver);

        return $this->drivers[$driver];
    }

    /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        $method = 'create'.ucfirst($driver).'Driver';

        // We'll check to see if a creator method exists for the given driver. If not we
        // will check for a custom driver creator, which allows developers to create
        // drivers using their own customized driver creator Closure to create it.
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        } elseif (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Driver [$driver] not supported.");
    }

    /**
     * Call a custom driver creator.
     *
     * @param  string  $driver
     * @return mixed
     */
    protected function callCustomCreator($driver)
    {
        return $this->customCreators[$driver]($this->app);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string    $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Get all of the created "drivers".
     *
     * @return array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * Returns the auth configuration array 
     * 
     * @return array
     */
    public function getConfiguration() 
    {
        return $this->app['config']['auth.multi'];
    }

    public function resetConfig() {
        $this->name = ""; // Initializing to empty string to prevent accessing old data
        $this->config = []; // Initializing to empty array to prevent accessing old data
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {   

        $config = $this->getConfiguration();

        /**
         * Temporary field to hold parameters
         * 
         * @var array
         */
        $temp = $parameters;

        $this->resetConfig();
        
        /**
         * Fetches first result and saves to $model
         * 
         * @var string|array
         */
        $model = array_shift($temp);

        foreach ($config as $key => $value) {
            // Comparing the key and model 
            if ($key === $model && is_string($model)) {
                $this->config = $value;
                $this->name   = $key;
            }
        }

        if ((!$this->name && !count($this->config))) {
            // If configuration is not set, then fallback to
            // default configuration settings
            $this->setDefaultConfig();

        } else {
            array_shift($parameters);
        }

        return call_user_func_array([$this->driver(), $method], $parameters);
    }

    /**
     * This initiallzes the default config setting
     * 
     * @param array $config
     */
    public function setDefaultConfig($config = []) {

        if( !count($config) ) $config = $this->app['config']['auth.multi'];

        $default = $this->app['config']['auth.default'];

        if (!$default) {
            $this->name   = current(array_keys($config));
            $this->config = array_shift($config);
        }

        if (array_key_exists($default, $config)) {
            $this->name   = $default;
            $this->config = $config[$default];
        }
    }
}
