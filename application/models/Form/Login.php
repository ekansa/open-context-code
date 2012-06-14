<?php

class Form_Login extends Zend_Dojo_Form   
{   
    public function init()   
    {   
        // Dojo-enable the form:   
        Zend_Dojo::enableForm($this);   
        $this->setName('Login');   
  
        $this->setAction('/user/login')   
          ->setMethod('post');   
             
        // EMAIL       
        $this->addElement(   
                'ValidationTextBox',    
                'email',    
                array(   
                    'value'      => '',   
                    'label'      => 'Email address : ',   
                    'trim'       => true,   
                 'lowercase'  => true,   
                 'required'   => true,   
                 'regExp'         => '^.{4,}$',   
                 'invalidMessage' => 'Insert your email',   
                 'validators' => array(   
                       'EmailAddress',   
                       array('StringLength', false, 4)   
                        ),   
                 'filters'  => array('StringToLower'),   
                )   
            );   
        
                     
            // PASSWORD       
            $this->addElement(   
                'PasswordTextBox',    
                'password',    
                array(   
                    'label'          => 'Password',   
                    'required'       => true,   
                    'trim'           => true,   
                    'regExp'         => '^.{4,}$',   
                    'invalidMessage' => 'Invalid password',   
                 'validators'  => array(array('StringLength', false, 4))   
                )   
            );   
           
        
        // SUBMIT   
        $this->addElement(   
            'SubmitButton',    
            'submit',    
            array(   
                'required'   => false,   
                'ignore'     => true,   
                'label'      => 'Submit Button!',   
            )   
        );   
    }   
}  
