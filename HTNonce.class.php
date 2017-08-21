<?php
/*
Plugin Name: HTNonce
Plugin URI:  
Description: Helper plugin for the built-in WordPress Nonces eco-system with an extended Validator implementation. 
Version:     0.0.1
Author:      Halit TIRYAKI
Author URI:  http://about.ht
*/

// Wordpress Plugin initialization
add_action( 'plugins_loaded', array( 'HTNonce', 'wpp_init' ));

/**
* Protyping for a improved management of Wordpress Nonces.
*
* @link https://codex.wordpress.org/WordPress_Nonces
*/
Class HTNonce {

    /**
    * The default lifetime for nonces in second.
    *
    * @var int NONCE_LIFETIME
    * @link https://developer.wordpress.org/reference/hooks/nonce_life/
    */
    const NONCE_LIFETIME = DAY_IN_SECONDS;

    /**
    * The default error message used upon nonce validation fail.
    *
    * @var string NONCE_ERROR_MESSAGE
    */
    const NONCE_ERROR_MESSAGE = "You are not supposed to be here!";

    /**
    * Used as a prefix for action names, session keys and also for field names to achieve improved security.
    *
    * @var string NONCE_PREFIX
    */
    const NONCE_PREFIX = 'HTN_';

    /**
    * Default constructor option to throw an exception if a nonce is existing with the same action context name.
    *
    * @var int OPTION_NONE
    */
    const OPTION_NONE = -1;

    /**
    * Constructor option to force a retrieval of a nonce if one is existing with the same action context name.
    * A new one will be created if not.
    *
    * @var int OPTION_FORCELOAD
    */
    const OPTION_FORCELOAD = 0;

    /**
    * Constructor option to force a creation of a new nonce.
    * Resulting in an overwrite if there was already one existing with the same action context name.
    *
    * @var int OPTION_FORCECREATE
    */
    const OPTION_FORCECREATE = 1;

    /**
    * A array cache containing all available class names implementing the IHTNonceValidator Interface. 
    *
    * @var array $validator_implementations {
    *   @type string   
    * }
    */
    private static $validator_implementations;


    /**
    * Helper function to reset all known nonce sessions.
    */
    private static function reset_all_sessions () {
        $_SESSION[self::NONCE_PREFIX] = array();
    }

    /**
    * Helper function to reset a context session.
    *
    * @param string $context_name The action context name of the nonce
    */
    private static function reset_context_sessions ( $context_name ) {
        if ( ! isset($_SESSION[self::NONCE_PREFIX]) ) {
            $_SESSION[self::NONCE_PREFIX] = array();
        }
        $_SESSION[self::NONCE_PREFIX][$context_name] = array();
    }

    /**
    * Helper function to make sure a session for the given context action exists.
    *
    * @param string $context_name The action context name of the nonce
    */
    private static function assert_context_session ( $context_name ) {
        if ( ! isset($_SESSION[self::NONCE_PREFIX]) ) {
            self::reset_all_sessions();
        }
        if ( ! isset($_SESSION[self::NONCE_PREFIX][$context_name]) ) {
            $_SESSION[self::NONCE_PREFIX][$context_name] = array();
        }
    }

    /**
    * Helper function to check whether a session variable is set for the given context action.
    *
    * @param string $context_name The action context name of the nonce
    * @param string $key The session variable key
    * @return boolean
    */
    private static function has_context_sess_var ( $context_name, $key ) {
        self::assert_context_session($context_name);
        return isset($_SESSION[self::NONCE_PREFIX][$context_name][$key]);
    }

    /**
    * Helper function to retrieve a session variable for the given context action.
    *
    * @param string $context_name The action context name of the nonce
    * @param string $key The session variable key
    * @return object
    */
    private static function get_context_sess_var ( $context_name, $key ) {
        return ( self::has_context_sess_var($context_name, $key) ) ?
            $_SESSION[self::NONCE_PREFIX][$context_name][$key] :
            NULL;
    }

    /**
    * Helper function to set a session variable for the given context action.
    *
    * @param string $context_name The action context name of the nonce
    * @param string $key The session variable key
    * @param object $value The session variable value
    */
    private static function set_context_sess_var ( $context_name, $key, $value ) {
        self::assert_context_session($context_name);
        $_SESSION[self::NONCE_PREFIX][$context_name][$key] = $value;
    }

    /**
    * This function serves as a callback in the wordpress nonce ecosystem for additional nonce validations.
    * Any valid HTNonceValidator instances implementing the IHTNonceValidator interface that were passed to the HTNonce constructor,
    * will be invoked inside this callback.
    * Only one false result of a IHTNonceValidator->validate() will result in an unvalid nonce.
    *
    * @link https://codex.wordpress.org/Function_Reference/wp_verify_nonce
    * @param string $action The action context name of the nonce
    * @param bool|int $result false = invalid, 1 = nonce generated max 12 hours ago, 2 = nonce generated longer than 12 hours ago
    * @return boolean
    */
    private static function run_session_validators ( $action, $result ) { 
        if ( $result === false ) { // already resulted as invalid, no need for further validation
            return false;
        }
        $validators = self::get_context_sess_var($action, 'validators');
        foreach ( $validators as $validator ) {
            if ( ! in_array($validator, self::$validator_implementations) ) {
                /*
                * somehow?! a class which doesnt implement the HTCNoncesValidatorInterface did make it's way into our session.
                * just log into php error file and proceed without further actions
                */
                error_log('Given HTNonce validator <' . $validator . '> for context (' . $action . ') is of wrong type!', 0);
                continue; // try next validator
            }
            if ( ! (new $validator())->validate() ) {
                return false; // nonce considered invalid by custom validator
            }
        }
        return $result;
    }

    /**
    * Will be called once upon wordpress plugin initialization to fill up the available HTNonceValidators cache.
    */
    private static function load_validator_implementations() {
        $all_classes = get_declared_classes();
        self::$validator_implementations = array();
        foreach ( $all_classes as $class ) {
            $refl = new ReflectionClass($class);
            if ( $refl->implementsInterface('IHTNonceValidator') ) {
                self::$validator_implementations[] = $class;
            }
        }
    }

    /**
    * The entrypoint function which is called once upon Wordpress Plugin initialization.
    * Any global options to be set should take place here.
    */
    public static function wpp_init() {
        // session needed to keep track of handles and any related data ( eg: validators, ..)
        add_action('init', function () {
            if ( ! session_id() ) {
                session_start();
            }
        });

        /*
        * TODO
        * Implement Wordpress Plugin options, constants should be used as fallbacks.
        */

        // set lifetime in seconds
        add_filter( 'nonce_life', function () { 
            return self::NONCE_LIFETIME; 
        } ); 

        // register custom error message
        add_filter('gettext', function ($translation) {
            if ( $translation == 'Are you sure you want to do this?' && ! empty($this->error_message) ) {
                return self::NONCE_ERROR_MESSAGE;
            }
            return $translation;
        } );

        // additional validation checks
        add_action('check_admin_referer', 'run_session_validators', 10, 2);
        add_action('check_ajax_referer', 'run_session_validators', 10, 2);

        // Pre-determine available HTNonce validators.
        self::load_validator_implementations();
    }

    /**
    * Helper function to get a HTNonce instance given the force load option.
    *
    * @see self::OPTION_FORCELOAD
    * @param string $name The action context name for the nonce.
    * @return HTNonce
    */
    public static function get_nonce ( $name ) {
        return new self($name, OPTION_FORCELOAD);
    }

    /**
    * Helper function to get a HTNonce instance given the force create option.
    *
    * @see self::OPTION_FORCECREATE
    * @param string $name The action context name for the nonce.
    * @return HTNonce
    */
    public static function new_nonce ( $name ) {
        return new self($name, OPTION_FORCECREATE);
    }

    /**
    * The handle is the hash returned by the wp_create_nonce() function.
    *
    * @var string $handle
    * @link https://codex.wordpress.org/Function_Reference/wp_create_nonce
    */
    private $handle;
    
    /**
    * The action context name for this instance.
    *
    * @var string $name
    */
    private $name;

    /**
    * Constructor
    *
    * @param string $name The action context name for this instance
    * @param int $option OPTION_FORCELOAD|OPTION_FORCECREATE|OPTION_NONE options supported
    * @param array $validators An array containing instances of validators implementing the IHTNoncesValidator interface
    */
    public function __construct ( $name, $option = NULL, $validators = NULL ) {
        nonce_start($name, $option, $validators);
    }
    
    /**
    * Helper function to intialize this instance.
    *
    * @param string $name The action context name for this instance
    * @param int $option OPTION_FORCELOAD|OPTION_FORCECREATE|OPTION_NONE options supported
    * @param array $validators An array containing instances of validators implementing the IHTNoncesValidator interface
    */
    private function nonce_start ( $name, $option = self::OPTION_NONE, $validators = NULL ) {
        $this->handle = NULL;
        $this->name = $name;

        if ( $validators === NULL ) {
            $validators = array();
        } elseif ( ! is_array($validators) ) {
            throw new Exception("Given validators value of wrong type! (Array expected)");
        }

        if ( isset($_SESSION[self::NONCE_PREFIX][$name]) ) { // A nonce with this id already in use by current user
            if ( $option === self::OPTION_FORCELOAD ) { // option load is given
                $this->handle = $this->get_sess_var('handle'); //load existing nonce handle
            } elseif ( $option === self::OPTION_FORCECREATE ) { // option force create is given
                $this->reset_session(); // unset all session data
            } else { // default to OPTION_NONE
                throw new Exception('A nonce action with this context already exists! (' . $name . ')');
            }
        } 
        // create new nonce
        if ( $this->handle === NULL ) {
            $registered_validators = array();
            foreach ( $validators as $validator ) {
                if ( ! $validator instanceof IHTNonceValidator) {
                    throw new Exception('Given validator does not impement IHTNonceValidator! (' . (is_object($validator) ? get_class($validator) : gettype($validator)) . ')');
                }
                if ( $validator->on_register($this->get_context_id()) !== false ) {
                    $registered_validators[] = get_class($validator);
                }
            }
            //$_SESSION[$id . '_once'] = $once_nonce;
            $this->handle = wp_create_nonce($this->get_context_id());
            $this->set_sess_var('handle', $this->handle);
            $this->set_sess_var('validators', $registered_validators);
        }
    }

    /**
    * Helper function to reset all session data for this action context.
    */
    private function reset_session () {
        self::reset_context_sessions($this->name);
    }

    /**
    * Helper function to make sure a session container for this action context exists.
    */
    private function assert_session () {
        self::assert_context_session($this->name);
    }

    /**
    * Helper function to retrive a session variable.
    */
    private function get_sess_var ( $key ) {
        return self::get_context_sess_var( $this->name, $key);
    }

    /**
    * Helper function to set a session variable.
    */
    private function set_sess_var ( $key, $value ) {
        self::set_context_sess_var( $this->name, $key, $value);
    }

    /**
    * Helper function to compose the HTNonce context id.
    */
    private function get_context_id() {
        return self::NONCE_PREFIX . $this->name;
    }

    /**
    * Creates a nonce url based on given url.
    *
    * @link https://codex.wordpress.org/Function_Reference/wp_nonce_url
    * @return string
    */
    public function create_url ( $bare_url ) {
        return wp_nonce_url($bare_url, $this->get_context_id(), self::NONCE_PREFIX);
    }

    /**
    * Returns a html string containing both nonce hash and referrer.
    *
    * @link https://codex.wordpress.org/Function_Reference/wp_nonce_field
    * @return string
    */
    public function create_input () {
        return wp_nonce_field($this->get_context_id(), self::NONCE_PREFIX, true, false);
    }

    /**
    * Directly outputs the html input fields to the buffer before returning them as string.
    *
    * @link https://codex.wordpress.org/Function_Reference/wp_nonce_field
    * @return string
    */
    function render_input () {
        return wp_nonce_field($this->get_context_id(), self::NONCE_PREFIX, true, true);
    }

    /**
    * Verifies the handle|nonce hash and the context name in the current instance.
    *
    * @link https://codex.wordpress.org/Function_Reference/wp_verify_nonce
    * @return bool|int
    */
    public function validate () {
        return wp_verify_nonce($this->handle, $this->get_context_id());
    }

    /**
    * Verifies the handle|nonce in the request with the context name in the current instance.
    * Ajax requests are handled automatically. 
    *
    * @link https://codex.wordpress.org/Function_Reference/check_ajax_referer
    * @link https://codex.wordpress.org/Function_Reference/check_admin_referer
    * @param bool|string $query_arg The HTTP request field where the nonce hash is to be found, where false means default WP Nonce lookup.
    * @param bool $die Flag to speficy if the request should be immediately killed if the nonce is invalid
    * @return bool|int
    */
    function validate_request ( $query_arg = false, $die = true ) {
        if ( wp_doing_ajax() ) {
            return check_ajax_referer( $this->get_context_id(), $query_arg, $die );
        }
        return check_admin_referer( $this->get_context_id(), $query_arg, $die );
    }

}