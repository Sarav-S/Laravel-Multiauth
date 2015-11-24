<?php

namespace Sarav\Multiauth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Guard as OriginalGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface as SessionStore;

class Guard extends OriginalGuard {

    protected $name;
    
    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Symfony\Component\HttpFoundation\Session\SessionInterface  $session
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return void
     */
    public function __construct(UserProvider $provider, SessionStore $session, $name, Request $request = null)
    {
        $this->name = $name;
        parent::__construct($provider, $session, $request);
    }

    /**
     * Get a unique identifier for the auth session value.
     *
     * @return string
     */
    public function getName()
    {
        return 'login_' . $this->name . '_' . md5(get_class($this));
    }

    /**
     * Get the name of the cookie used to store the "recaller".
     *
     * @return string
     */
    public function getRecallerName()
    {
        return 'remember_' . $this->name . '_' . md5(get_class($this));
    }

    /**
     * Set current authentication type
     *
     * @param string
     * @return \Sarav\Multiauth\Guard
     */
    public function with($name)
    {
      $this->name = $name;
      return $this;
    }
}
