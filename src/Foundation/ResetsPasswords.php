<?php

namespace Sarav\Multiauth\Foundation;

use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Contracts\Auth\PasswordBroker as Broker;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

trait ResetsPasswords
{

    protected $user = null;

    /**
     * Returns the type of user
     *
     * @return string
     */
    protected function user() {

        $this->checkUser();

        return $this->user;
    }

    /**
     * Checks User has been set or not. If not throw an exception
     * @return null
     */
    public function checkUser() {

        if (!$this->user) {
            throw new \InvalidArgumentException('First parameter should not be empty');
        }

        $app = app();

        if (!array_key_exists($this->user, $app->config['auth.multi'])) {
            throw new \InvalidArgumentException('Undefined property '.$this->user.' not found in auth.php multi array');
        }

    }

    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\Http\Response
     */
    public function getEmail()
    {
        return view($this->user().'.password');
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postEmail(Request $request, Broker $password, TokenRepositoryInterface $tokens)
    {

        $this->validate($request, ['email' => 'required|email']);

        $app = app();

        $class = str_ireplace('App\Http\Controllers\\', '', get_called_class());

        view()->composer($app->config['auth.password.email'], function($view) use ($class) {
            $view->with('action', $class.'@getReset');
        });

        $user = $this->resolveUserBy($request->only('email'));
        if ($user == null) {
            return redirect()->back()->with('status', trans(Broker::INVALID_USER));
        }

        $token = $tokens->create($user);
        $password->emailResetLink($user, $token, function (Message $message) {
            $message->subject($this->getEmailSubject());
        });

        return redirect()->back()->with('status', trans(Broker::RESET_LINK_SENT));

    }

    /**
     * Use the specified user provider to look for an existing user record
     *
     * @param  string $email
     * @return \Illuminate\Contracts\Auth\CanResetPassword
     *
     * @throws \UnexpectedValueException
     */
    protected function resolveUserBy($email)
    {
        $model = app()->config['auth.multi.' . $this->user . '.model'];
        $class = '\\'.ltrim($model, '\\');
        $obj = new $class;
        $user = $obj->newQuery()->where('email', $email)->first();
        if ($user && ! $user instanceof CanResetPasswordContract) {
            throw new \UnexpectedValueException('User must implement CanResetPassword interface.');
        }
        return $user;
    }

    /**
     * Get the e-mail subject line to be used for the reset link email.
     *
     * @return string
     */
    protected function getEmailSubject()
    {
        return isset($this->subject) ? $this->subject : 'Your Password Reset Link';
    }

    /**
     * Display the password reset view for the given token.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function getReset($token = null)
    {
        if (is_null($token)) {
            throw new NotFoundHttpException;
        }

        return view($this->user().'.reset')->with('token', $token);
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postReset(Request $request, Broker $password, TokenRepositoryInterface $tokens)
    {
        $this->validate($request, [
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        $credentials = $request->only(
            'email', 'password', 'password_confirmation', 'token'
        );

        $email = $request['email'];

        $user = $this->resolveUserBy($email);
        if ($user == null) {
            return $this->redirectWith($email, Broker::INVALID_USER);
        }


        if (! $password->validateNewPassword($credentials)) {
            return $this->redirectWith($email, Broker::INVALID_PASSWORD);
        }

        if (! $tokens->exists($user, $credentials['token'])) {
            return $this->redirectWith($email, Broker::INVALID_TOKEN);
        }

        $this->resetPassword($user, $credentials['password']);
        $tokens->delete($credentials['token']);

        return redirect($this->redirectPath());
    }

    /**
     * Utility function to respond to the reset password form
     *
     * @param  string $email
     * @param  string $response message translation key
     * @return \Illuminate\Http\Response
     */
    protected function redirectWith($email, $response)
    {
        return redirect()->back()
                        ->withInput(['email' => $email])
                        ->withErrors(['email' => trans($response)]);
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        $user->password = bcrypt($password);

        $user->save();

        Auth::login($this->user(), $user);
    }

    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        if (property_exists($this, 'redirectPath')) {
            return $this->redirectPath;
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : $this->user().'/home';
    }
}
