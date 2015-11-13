<?php

namespace Sarav\Multiauth;

use Sarav\Multiauth\Manager;
use Illuminate\Contracts\Auth\Guard as GuardContract;
use Illuminate\Auth\DatabaseUserProvider;
use Illuminate\Auth\EloquentUserProvider;

class AuthManager extends Manager
{
    /**
     * Holds the Guard instance
     * 
     * @var array
     */
    protected $driver = [];

    public function __construct($app) {
        parent::__construct($app);
    }

    /**
     * Get the default authentication driver name.
     *
     * @return string
     */
    public function getDefaultDriver() {
        
        /**
         * If $this->config['driver'] isnt set, then call the set default
         * config method
         */
        if (!isset($this->config['driver'])) {
            $this->setDefaultConfig();
        }

        return $this->config['driver'];
    }

     /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return mixed
     */
    protected function createDriver($driver) {

        $guard = parent::createDriver($driver);
        
        $guard->setCookieJar($this->app['cookie']);

        $guard->setDispatcher($this->app['events']);

        return $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
    }
        
     /**
     * Call a custom driver creator.
     *
     * @param  string  $driver
     * @return \Illuminate\Contracts\Auth\Guard
     */
    protected function callCustomCreator($driver) {

        $custom = parent::callCustomCreator($driver);

        if ($custom instanceof Guard) return $custom;

        return new Guard($custom, $this->app['session.store'], $this->name);
    }
    
     /**
     * Create an instance of the database driver.
     *
     * @return \Illuminate\Auth\Guard
     */
    public function createDatabaseDriver() {
        $provider = $this->createDatabaseProvider();
        return $this->provideGuardAccess($provider, $this->app['session.store'], $this->name);
    }
    
    /**
     * Create an instance of the database user provider.
     *
     * @return \Illuminate\Auth\DatabaseUserProvider
     */
    protected function createDatabaseProvider() {
        $connection = $this->app['db']->connection();
        $table = $this->config['table'];
        return new DatabaseUserProvider($connection, $this->app['hash'], $table);
    }
    
    /**
     * Create an instance of the Eloquent driver.
     *
     * @return \Illuminate\Auth\Guard
     */
    public function createEloquentDriver() {
        $provider = $this->createEloquentProvider();
        return $this->provideGuardAccess($provider, $this->app['session.store'], $this->name);
    }
    
     /**
     * Create an instance of the Eloquent user provider.
     *
     * @return \Illuminate\Auth\EloquentUserProvider
     */
    protected function createEloquentProvider() {
        $model = $this->config['model'];
        return new EloquentUserProvider($this->app['hash'], $model);
    }
        
    /**
     * Returns the instance of Guard Class
     * 
     * @return Sarav\Multiauth\Guard
     */
    protected function provideGuardAccess($provider, $session, $name) {

        if(!array_key_exists($name, $this->driver)) {
            $this->driver[$name] = new Guard($provider, $session, $name);
        }

        return $this->driver[$name];
    }

     /**
     * Set the default authentication driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->config['driver'] = $name;
    }

}
