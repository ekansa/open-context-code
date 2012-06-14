<?php


require_once 'Zend/Form/Element/Password.php';

class FKK_FormElementPassword extends Zend_Form_Element_Password {
    public function init() {
        $this->setDisableLoadDefaultDecorators(true);
        $this->addPrefixPath('FKK_Validate', 'FKK/Validate/', 'validate');

        $this->addDecorator('ViewHelper');
        $this->addDecorator('Errors');
        $this->addDecorator('Description', array('escape' => false, 'class' => 'fieldDescription'));
        $this->addDecorator('HtmlTag', array('tag' => 'dd'));
        $this->addDecorator('Label', array('requiredSuffix' => ' *', 'tag' => 'dt', 'class' => 'fieldLabel'));
    }
}



?>