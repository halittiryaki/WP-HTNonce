# WP-HTNonce
Protyping for a improved management of Wordpress Nonces.
Providing an extensible validator implementation for the built-in WordPress Nonces eco-system.

Installation
------------

HTNonce is implemented as a WordPress Plugin with composer support.

1. Checkout

⋅⋅* Simply clone the project directly from the GitHub repository and to install dependencies run:

    ```bash
        $ composer install
    ```

⋅⋅* Or add the following line to the `require` section of your project's existing `compoers.json` file:

    ```json
        "require": {
            "ht/wp-htnonce": "master"
        }
    ```
then to install the dependencies run :
    
    ```bash
        $ composer update
    ```

2. Install Wordpress Plugin

Follow the instructions in the `Manual Plugin Installation` section at:

[Wordpress Pluging Installation](https://codex.wordpress.org/Managing_Plugins)


Usage
-----

The usage is straight forward. For detailed informations on the internal functionings, check the inline-documentations inside the class source files.


Create nonce with context name `delete-post:16` if not used, throw exception if context name in use by another nonce:
    ```php
        $nonce = new HTNonce('delete-post:16');
    ```

Create nonce with context name `delete-post:16` if not used, load existing if context name already in use by another nonce:
    ```php
        $nonce = new HTNonce('delete-post:16', HTNonce::OPTION_FORCELOAD);
    ```
or
    ```php
        $nonce = new HTNonce::get_nonce('delete-post:16');
    ```

Create nonce with context name `delete-post:16` if not used, overwrite if context name already in use by another nonce:
    ```php
        $nonce = new HTNonce('delete-post:16', HTNonce::OPTION_FORCECREATE);
    ```
or
    ```php
        $nonce = new HTNonce::new_nonce('delete-post:16');
    ```

     ( $name )


Create nonce with context name `delete-post:16` and default option (throw exception if context name in use), with a one-time usage validator:
    ```php
        $nonce = new HTNonce('delete-post:16', NULL, array(new HTNonceOnceValidator()));
    ```

***

After having successfully initialized a HTNonce instance, following methods can utilized:

Create url for current nonce instance:
    ```php
        $my_url = $nonce->create_url('http://www.mysite.com/posts/15?action=delete');
    ```
This will return a url like `http://www.mysite.com/posts/15?action=delete&HTN_=z3asv3rt2d`


To create html inputs for the current nonce, simply call:
    ```php
        $my_inputs = $nonce->create_input();
    ```
Or to directly render the input controls into the html output:
    ```php
        $nonce->render_input();
    ```

A validation of the action context provided by the current instance is achieved by:
    ```php
        $valid = $nonce->validate();
    ```
A `false` result means invalid, `1` means nonce is still valid and was created max. 12 hours ago, `2` means nonce is still valid and was created more than 12 hours ago.


If you whish to validate the current request by its http payload, simply call:
    ```php
        $valid = $nonce->validate_request();
    ```
Ajax requests will be handled automatically.

You can also override the default field name to look for in the payload and also the action to take if the nonce is invalid:
    ```php
        $valid = $nonce->validate_request('my_query_field', false);
    ```
This call will look for a valid nonce hash in `my_query_field` and won't `die()` if it is invalid.

***

Final Notes
-----

This WordPress plugin was written for demonstration purposes and is only to be considered as a non-functional prototype. 
Nevertheless, any parts of this software can be used for free and for any purpose without any asks for permission. 
Have phun!