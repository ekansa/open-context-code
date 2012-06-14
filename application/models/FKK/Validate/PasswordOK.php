<?php

require_once 'Zend/Validate/Abstract.php';

class FKK_Validate_PasswordOK extends Zend_Validate_Abstract {
    const NOT_MATCH = 'passwordNotMatch';

    protected $_messageTemplates = array(
        self::NOT_MATCH => 'This password seems incorrect'
    );

    
    /**
     * Check if the element using this validator is valid
     *
     * This method will compare the $value of the element to the other elements
     * it needs to match. If they all match, the method returns true.
     *
     * @param $value string
     * @param $context array All other elements from the form
     * @return boolean Returns true if the element is valid
     */
    public function isValid($value, $context = null) {
        $value = (string) $value;
        $this->_setValue($value);

        $error = false;
        $user = new Users;
        if(!$user->passwordCheck($value)){
            $error = true;
            $this->_error(self::NO_RECORD);
        }

        return !$error;
    }
}

?>