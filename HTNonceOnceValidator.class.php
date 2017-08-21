<?php

/**
* A simple one-time nonce validator implementation.
*/
class HTNonceOnceValidator implements IHTNonceValidator {
    
    /**
    * Set the once flag to false on registration.
    *
    * @param string $context_name The action context name for which session variables are accessible
    * @return bool
    */
    public function on_register( $context_name ) {
        HTNonce::set_context_sess_var($context_name, 'oncenonce', false);
    }
    
    /**
    * Determines if the nonce is still valid based on its session flag.
    *
    * @param string $context_name The action context name for which session variables are accessible
    * @return bool
    */
    public function validate( $context_name ) {
        if ( ! HTNonce::has_context_sess_var($context_name) ) { // nonce is not flagged for one-time validation, pass it back as valid
            return true;
        }

        if ( HTNonce::get_context_sess_var($context_name, 'oncenonce') === true ) { // the one time nonce is marked as already validated before, nonce is invalid
            return false;
        } 
        
        HTNonce::set_context_sess_var($context_name, 'oncenonce', true); // mark one time nonce as validated
        return true;
    }

}