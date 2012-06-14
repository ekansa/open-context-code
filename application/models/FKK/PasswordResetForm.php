<?php

require_once 'Zend/Form.php';
//require_once 'Zend/Form/Element/Text.php';

class FKK_PasswordResetForm extends Zend_Form {
    public function init() {
        
        $mail = new FKK_FormElementText('mail');
        $mail->setLabel('Mail');
        $mail->setDescription('Your email address');
        $mail->setRequired(true);
        $mail->addValidator('EmailAddress', true);
        $mail->addValidator('EmailResetOK', false, array('mailRepeat'));

        $mailRepeat = new FKK_FormElementText('mailRepeat');
        $mailRepeat->setLabel('Mail (repeat)');
        $mailRepeat->setDescription('Please confirm your email address');
        $mailRepeat->setRequired(true);
        $mailRepeat->addValidator('EmailAddress', true);
        $mailRepeat->addValidator('EmailSame', false, array('mail'));

        $submit = $this->createElement('submit', 'resetPass');
        $submit->setLabel('Reset Password');

        $this->addElements(array($mail, $mailRepeat, $submit));
    }
}



?>