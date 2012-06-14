<?php

require_once 'Zend/Form.php';
//require_once 'Zend/Form/Element/Text.php';

class FKK_RegisterUserForm extends Zend_Form {
    public function init() {
        /* Set the action and method. The action is generated using the view's url helper */
        $displayName = new FKK_FormElementText('displayName');
        $displayName->setLabel('Name (publicly viewable)');
        $displayName->setDescription('Your name, as you want to be publicly known on Open Context');
        $displayName->setRequired(true);
       
        $mail = new FKK_FormElementText('mail');
        $mail->setLabel('Mail');
        $mail->setDescription('Your email address');
        $mail->setRequired(true);
        $mail->addValidator('EmailAddress', true);
        $mail->addValidator('EmailConfirmation', false, array('mailRepeat'));

        $mailRepeat = new FKK_FormElementText('mailRepeat');
        $mailRepeat->setLabel('Mail (repeat)');
        $mailRepeat->setDescription('Please confirm your email address');
        $mailRepeat->setRequired(true);
        $mailRepeat->addValidator('EmailAddress', true);
        $mailRepeat->addValidator('EmailConfirmation', false, array('mail'));

        $password = new FKK_FormElementPassword('password');
        $password->setLabel('Password');
        $password->setDescription('Your password. Must be between 4 and 20 characters.');
        $password->setRequired(true);
        $password->addValidator('StringLength', true, array(4, 20));
        $password->addValidator('PasswordConfirmation', false, array('passwordRepeat'));

        $passwordRepeat = new FKK_FormElementPassword('passwordRepeat');
        $passwordRepeat->setLabel('Password (repeat)');
        $passwordRepeat->setDescription('Please confirm your password');
        $passwordRepeat->setRequired(true);
        $passwordRepeat->addValidator('StringLength', false, array(4, 20));
        $passwordRepeat->addValidator('PasswordConfirmation', false, array('password'));

        $submit = $this->createElement('submit', 'registerUser');
        $submit->setLabel('Continue Registration');

        $this->addElements(array($displayName, $mail, $mailRepeat, $password, $passwordRepeat, $submit));
    }
}



?>