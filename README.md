# Laravel-Multiauth

A Simple Laravel Package for handling multiple authentication

- **Laravel**: 5.1.*
- **Author**: Sarav
- **Author Homepage**: http://sarav.co

##Step 1 : Require Composer package##

Open your terminal and navigate to your laravel folder. Now run the following command

	composer require sarav/laravel-multiauth dev-master


##Step 2 : Replacing default auth service provider##


Replace 
	"Illuminate\Auth\AuthServiceProvider::class"
with 
    "Sarav\Multiauth\MultiauthServiceProvider"

##Step 3 : Modify auth.php##

Modify auth.php file from the config directory to something like this

	'multi' => [
	    'user' => [
	        'driver' => 'eloquent',
	        'model'  => App\User::class,
	        'table'  => 'users'
	    ],
	    'admin' => [
	        'driver' => 'eloquent',
	        'model'  => App\Admin::class,
	        'table'  => 'admins'
	    ]
	 ],


Note : I have set second user as admin here. Feel free to change yours but don't forget to add its respective driver, model and table.


We are done! Now you can simply login user/admin like the following code

	\Auth::loginUsingId("user", 1); // Login user with id 1

	\Auth::loginUsingId("admin", 1); // Login user with id 1

	// Attempts to login user with email id johndoe@gmail.com 
	\Auth::attempt("user", ['email' => 'johndoe@gmail.com', 'password' => 'password']);

	// Attempts to login admin with email id johndoe@gmail.com
	\Auth::attempt("admin", ['email' => 'johndoe@gmail.com', 'password' => 'password']); 


Simply pass the first parameter as key which you have configured in auth.php to perform authentication for either user or admin.

For more information <a href="http://sarav.co/blog/multiple-authentication-in-laravel-5-1-continued/" target="_blank">check out this article</a>.


