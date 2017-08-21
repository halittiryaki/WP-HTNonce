<?php

/**
* This Interface has to be implemented for all custom HTNonce Validators.
* Static helper functions provided by HTNonce Class can be accessed to read/write context session values by context name.
*/
interface IHTNoncesValidator {

    /**
    * This callback will be invoked only once upon nonce creation, to give the validator a way to process its own internals and make preparations before a validate() invoke occurs. 
    *
    * @param string $context_name The action context name for which session variables are accessible
    * @return bool A false return will cancel the registration of the custom validator, so no validation invokes will occur.
    */
    public function on_register ( $context_name );

    /**
    * This callback will be invoked after a WordPress Nonce is proven valid by the default validation implementation.
    *
    * @param string $context_name The action context name for which session variables are accessible
    * @return bool A false return will prove the nonce as invalid, skipping any further custom validations for this action
    */
    public function validate ( $context_name );

}