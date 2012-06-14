<?php


require_once 'Zend/Form/Element/Text.php';

class FKK_FormElementText extends Zend_Form_Element_Text {
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