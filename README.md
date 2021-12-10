# RocketGeek Akismet API Wrapper for WordPress Plugins

This is a code library for WordPress plugins to make quick use of the Akismet API. You can include this library according to the instructions and use the Akismet API in your plugin (or theme).

You will need to have an API key for Akismet to work. Regular users of a plugin (or other app) using this library will need a basic API key. You will need a developer's API key for development.

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Prerequisites

It is assumed that you are already running WordPress.  If so, you just need to include this library in your plugin (or theme).

The library relies on Akismet's API. See Akismet's API documentation for more information.

### Using the library

Copy the entire rocketgeek-akismet-api folder to your project. I like to do this in an "includes" subdirectory. For example:

```
include_once( YOUR_PLUGIN_PATH . 'includes/libraries/rocketgeek-akismet-api/rocketgeek-akismet-api.php' );
```

Once the library is included, you can call it in your project.

```
$my_object = new RocketGeek_Akismet_API;
```

Initializing it like the above will initialize with all default settings.  You can override certain defaults by passing them as an array of arguments when you initilize the library as follows:

 * default_enabled: A boolean that determines if the default validation will run hooked to "registration_errors" (set to false to turn this off).
 * api_key: String containing the API key. You only need to pass this in the arguments if you are relying on custom storage of the API key in your application.
 * api_key_option: Name of the WP option value the API key will be saved under. Defaults to "rktgk_akismet_api_key". Pass a value to save the API key under a custom option name.
 * text_domain: Text domain for translation. Only needed if "default_enabled" is true (which is the default), and then only if you are custom translating the error message.
 * test_akismet: Set 'test_akisment' to true for testing, otherwise leave as default (false).
 
Example of initializing with custom settings:

```
$args = array(
	'default_enabled' => false,
	'test_akismet' => true,
	'api_key_option' => 'my_custom_api_key_option_name',
);
$my_object = new RocketGeek_Akismet_API( $args );
```

Once initialized in your application, there are some variables you can access and some methods you can use.

`$my_object->get_api_key()` Retrieves the saved API key.  (Note: if Akismet's WordPress plugin is installed, it will default to that plugin's API key so you don't need to save it a second time for your custom application).

`$my_object->reg_validate( $args )` Validates a registration to see if Akismet thinks it is spam.  All `$args` are optional:
* 'user_ip' The user's IP address, defaults to use `$my_object->get_user_ip()`
* 'user_email' Optional, no default
* 'user_login' Optional, no default

`$my_object->get_user_ip()` Gets the user's IP address.

`$my_object->verify_key( $key )` Validates an Akisment API key.

`$my_object->save_key( $key )` Saves a given API key. Default save is to option name "rktgk_akismet_api_key" unless `$api_key_option` is passed with the init arguments or Akismet's WP plugin is installed, activated, and has an API key saved.

### Using the static class

Include the static class in your project:

```
include_once( YOUR_PLUGIN_PATH . 'includes/libraries/rocketgeek-akismet-api/rocketgeek-akismet-api-static.php' );
```

Once the class is included, you can initialize it in your project.

```
RocketGeek_Akismet_API::init();
```

Similar to the object method, there are static defaults. You can override them when initializing.  For example:

```
$args = array(
	'default_enabled' => false,
	'test_akismet' => true,
	'api_key_option' => 'my_custom_api_key_option_name',
);
RocketGeek_Akismet_API::init( $args );
```

Once initialized in your application, there are some variables you can access and some methods you can use.

`RocketGeek_Akismet_API::get_api_key()` Retrieves the saved API key.  (Note: if Akismet's WordPress plugin is installed, it will default to that plugin's API key so you don't need to save it a second time for your custom application).

`RocketGeek_Akismet_API::reg_validate( $args )` Validates a registration to see if Akismet thinks it is spam.  All `$args` are optional:
* 'user_ip' The user's IP address, defaults to use `RocketGeek_Akismet_API::get_user_ip()`
* 'user_email' Optional, no default
* 'user_login' Optional, no default

`RocketGeek_Akismet_API::get_user_ip()` Gets the user's IP address.

`RocketGeek_Akismet_API::verify_key( $key )` Validates an Akisment API key.

`RocketGeek_Akismet_API::save_key( $key )` Saves a given API key. Default save is to option name "rktgk_akismet_api_key" unless `$api_key_option` is passed with the init arguments or Akismet's WP plugin is installed, activated, and has an API key saved.


## Built With

* [WordPress](https://make.wordpress.org/)

## Contributing

I do accept pull requests. However, make sure your pull request is properly formatted. Also, make sure your request is generic in nature. In other words, don't submit things that are case specific - that's what forks are for. The library also has hooks that follow WP standards - use 'em.

## Versioning

I use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/rocketgeek/jquery_tabs/tags). 

## Authors

* **Chad Butler** - [ButlerBlog](https://github.com/butlerblog)
* **RocketGeek** - [RocketGeek](https://github.com/rocketgeek)

## License

This project is licensed under the Apache-2.0 License - see the [LICENSE.md](LICENSE.md) file for details.

I hope you find this project useful. If you use it your project, attribution is appreciated.
