<?php

require_once 'Zend/Form.php';
//require_once 'Zend/Form/Element/Text.php';

class FKK_RegisterUserForm extends Zend_Form {
    public function init() {
        /* Set the action and method. The action is generated using the view's url helper */
        $displayName = new FKK_FormElementText('displayName');
        $displayName->setLabel('Name (publicly viewable)');
        $displayName->setDescription('Edit your name, as you want to be publicly known on Open Context');
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

        $submit = $this->createElement('submit', 'updateUser');
        $submit->setLabel('Update My Account');

        $this->addElements(array($displayName, $mail, $mailRepeat, $submit));
    }
}



?>