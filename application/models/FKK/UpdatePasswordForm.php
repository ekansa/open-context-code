<?php

require_once 'Zend/Form.php';
//require_once 'Zend/Form/Element/Text.php';

class FKK_UpdatePasswordForm extends Zend_Form {
    
    
    public function init() {
        /* Set the action and method. The action is generated using the view's url helper */
        
        
        $passwordOld = new FKK_FormElementPassword('passwordOld');
        $passwordOld->setLabel('Current Password');
        $passwordOld->setDescription('Your current password. If you have lost this, please reset and a new one will be mailed to you.');
        $passwordOld->setRequired(true);
        $passwordOld->addValidator('StringLength', true, array(4, 20));
    
        $password = new FKK_FormElementPassword('password');
        $password->setLabel('New Password');
        $password->setDescription('Your new password. Must be between 4 and 20 characters.');
        $password->setRequired(true);
        $password->addValidator('StringLength', true, array(4, 20));
        $password->addValidator('PasswordConfirmation', false, array('passwordRepeat'));

        $passwordRepeat = new FKK_FormElementPassword('passwordRepeat');
        $passwordRepeat->setLabel('Password (repeat)');
        $passwordRepeat->setDescription('Please confirm your new password');
        $passwordRepeat->setRequired(true);
        $passwordRepeat->addValidator('StringLength', false, array(4, 20));
        $passwordRepeat->addValidator('PasswordConfirmation', false, array('password'));

        $submit = $this->createElement('submit', 'updatePass');
        $submit->setLabel('Update Password');

        $this->addElements(array($passwordOld, $password, $passwordRepeat, $submit));
        
    }
}



?>