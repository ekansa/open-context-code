<?php

require_once 'Zend/Validate/Abstract.php';

class FKK_Validate_EmailSame extends Zend_Validate_Abstract {
    const NOT_MATCH = 'emailConfirmationNotMatch';
    


    protected $_messageTemplates = array(
        self::NOT_MATCH => "Email confirmation does not match"
    );

    /**
     * The fields that the current element needs to match
     *
     * @var array
     */
    protected $_fieldsToMatch = array();

    /**
     * Constructor of this validator
     *
     * The argument to this constructor is the third argument to the elements' addValidator
     * method.
     *
     * @param array|string $fieldsToMatch
     */
    public function __construct($fieldsToMatch = array()) {
        if (is_array($fieldsToMatch)) {
            foreach ($fieldsToMatch as $field) {
                $this->_fieldsToMatch[] = (string) $field;
            }
        } else {
            $this->_fieldsToMatch[] = (string) $fieldsToMatch;
        }
    }


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

        foreach ($this->_fieldsToMatch as $fieldName) {
            if (!isset($context[$fieldName]) || $value !== $context[$fieldName]) {
                $error = true;
                $this->_error(self::NOT_MATCH);
                break;
            }
        }

        return !$error;
    }
}

?>