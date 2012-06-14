<?php

class Form_Upload extends Zend_Dojo_Form   
{
    private $description;
    private $projectID;
    private $projectUUID;
    private $submit;
    private $file;
    
    public function Form_Upload($projectID, $projectUUID)
    {
        Zend_Loader::loadClass('Zend_Form_Element_Text');
        Zend_Loader::loadClass('Zend_Form_Element_Hidden');
        Zend_Loader::loadClass('App_Form_Element_File');
        Zend_Loader::loadClass('Zend_Form_Element_Submit');
        $this->addElementPrefixPath('App', 'App/');
        parent::__construct(null);
        $this->setName('upload');
        $this->setAction('uploaddata/submit');
        $this->setMethod('post');
        $this->setAttrib('enctype', 'multipart/form-data');
        
        $this->description = new Zend_Form_Element_Text('description');
        $this->description->setLabel('Description')
                  ->setRequired(true)
                  ->addValidator('NotEmpty');
                  
        $this->projectID = new Zend_Form_Element_Hidden('projectID');
        $this->projectID->setValue($projectID);
        
        $this->projectUUID = new Zend_Form_Element_Hidden('projectUUID');
        $this->projectUUID->setValue($projectUUID);

        $this->file = new App_Form_Element_File('excelfile');
        $this->file->setLabel('File');
        
        $this->submit = new Zend_Form_Element_Submit('submit');
        $this->submit->setLabel('Upload');             

        //$this->cancel = new Zend_Form_Element_Submit('submit');
        //$this->cancel->setLabel('Cancel');
        
        $this->addElements(
            array(
                $this->description,
                $this->projectID,
                $this->projectUUID,
                $this->file,
                $this->submit
            )
        );
            

    } 

    /*public function init()   
    {
        Zend_Loader::loadClass('App_Form_Element_File');
        // Dojo-enable the form:   
        Zend_Dojo::enableForm($this);   
        $this->setName('Upload Data');
        $this->setAttrib('enctype', 'multipart/form-data');
  
        $this->setAction('uploaddata')   
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
              
        $file = new App_Form_Element_File('file');
        $file->setLabel('File')
                 ->setRequired(true)
                 ->addValidator('NotEmpty');
                 
        $this->addElement($file);
        
                     
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
    }*/   
}  
