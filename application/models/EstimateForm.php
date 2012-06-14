<?php

class EstimateForm extends Zend_Form
{
    
    public $valArray = array("num_images" => array("0" => "None",
						"100" => "1-100",
						"1000" => "100-1000",
						"5000" => "1000-5000",
						"20000" => "5000-20000",
						"40000" => "20000+"),
			  "num_vids" => array("0" => "None",
						"25" => "1-25",
						"100" => "25-100",
						"500" => "100-500",
						"1000" => "500+"),
			  "num_docs" => array("0" => "None",
						"10" => "1-10",
						"50" => "10-50",
						"100" => "50+"),
			  "num_gis" => array("0" => "None",
						"5" => "1-5",
						"10" => "5-10",
						"50" => "10-50",
						"100" => "50+"),
			  "num_other" => array("0" => "None",
						"5" => "1-5",
						"10" => "5-10",
						"50" => "10-50",
						"100" => "50+")
			 );
    
    
    
    public function init()
    {
        
        $projname = $this->addElement('text', 'projname', array(
            'required' => true,
            'label'    => 'Name',
        ));
        
        $name = $this->addElement('text', 'name', array(
            'required' => true,
            'label'    => 'Name',
        ));
        
        $diss = $this->addElement('text', 'diss', array(
            'required' => false,
            'label'    => 'Name',
        ));
        
        $email = $this->addElement('text', 'email', array(
            'filters'    => array('StringTrim', 'StringToLower'),
            'validators' => array(
                'EmailAddress',
            ),
            'required'   => true,
            'label'      => 'Your Email:',
        ));

        $phone = $this->addElement('text', 'phone', array(
            'filters'    => array('StringTrim', 'StringToLower'),
            'required'   => true,
            'label'      => 'Your Phone Number:',
        ));

        $num_years = $this->addElement('text', 'num_years', array(
            'filters'    => array('StringTrim', 'StringToLower'),
            'required'   => true,
            'label'      => 'Duration of Proposed Project (Years):',
        ));
        
        $num_sets = $this->addElement('text', 'num_sets', array(
            'filters'    => array('StringTrim', 'StringToLower'),
            'required'   => true,
            'label'      => 'Estimated Number of Specialists Datasets (zooarchaeology, lithic analysis, etc.):',
        ));
        
        $num_tabs = $this->addElement('text', 'num_tabs', array(
            'filters'    => array('StringTrim', 'StringToLower'),
            'required'   => true,
            'label'      => 'Estimated Total Number of Tables (for data to be published):',
        ));

        $num_images = $this->addElement('text', 'num_images', array(
            'filters'    => array('StringTrim', 'StringToLower'),
            'required'   => true,
            'label'      => 'Estimated Total Number of Images:',
        ));

        $num_vids = $this->addElement('text', 'num_vids', array(
            'filters'    => array('StringTrim', 'StringToLower'),
            'required'   => true,
            'label'      => 'Estimated Total Number of Audio and Video:',
        ));

        $num_docs = $this->addElement('text', 'num_docs', array(
            'filters'    => array('StringTrim', 'StringToLower'),
            'required'   => true,
            'label'      => 'Estimated Total Number of Docs:',
        ));

        $num_gis = $this->addElement('text', 'num_gis', array(
            'filters'    => array('StringTrim', 'StringToLower'),
            'required'   => true,
            'label'      => 'Estimated Total Number of GIS:',
        ));
        
        $num_other = $this->addElement('text', 'num_other', array(
            'filters'    => array('StringTrim', 'StringToLower'),
            'required'   => true,
            'label'      => 'Estimated Total Number of Other:',
        ));
        
        $comment = $this->addElement('textarea', 'comment', array(
            'required'   => false,
            'label'      => 'Comment:',
        ));

        $license = $this->addElement('text', 'license', array(
            'required'   => true,
            'validators' => array(
                'NotEmpty'
            ),
            'label'      => 'License:',
        ));

        $rlicense = $this->addElement('text', 'rlicense', array(
            'required'   => false,
            'label'      => 'Restrictive License:',
        ));

        $restrict_com = $this->addElement('textarea', 'restrict_com', array(
            'required'   => false,
            'label'      => 'Restrictive License Comment:',
        ));

    }
    
    
    
    //gets array of value ranges for the estimation form
    public function getValueText($type, $value){
	
	$valArray = $this->valArray;
	
	return $valArray[$type][$value]; 
    }
    
    //additional validation checks
    public function more_form_validation($problems, $validForm, $goodNums){
	
	$requestFields = array("num_years",
			       "num_sets",
			       "num_tabs",
			       "num_years",
			       "num_images",
			       "num_vids",
			       "num_docs",
			       "num_gis",
			       "num_other"
			       );
	
	
	foreach($requestFields as $field){
	    if(isset($_REQUEST[$field])){    
		if(!is_numeric($_REQUEST[$field])){
		    $validForm = false;
		    $goodNums = false;
		    $problems[$field] = true;
		}
	    }
	    else{
		$problems[$field] = true;
	    }
	}
	
	return array("problems"=>$problems, "validForm" => $validForm, "goodNums" => $goodNums);    
    }
    
    //returns more specific validation error messages
    public function validation_error_messages($key){
	$problems = array();
	
	$problems["num_years"] = "Please provide a number.";
	$problems["num_sets"] = "Please provide a number.";
	$problems["num_tabs"] = "Please provide a number.";
	$problems["num_images"] = "Please select a value range.";
	$problems["num_vids"] = "Please select a value range.";
	$problems["num_docs"] = "Please select a value range.";
	$problems["num_gis"] = "Please select a value range.";
	$problems["num_other"] = "Please select a value range.";
	$problems["name"] = "Please provide your name.";
	$problems["phone"] = "Please provide your phone number.";
	$problems["projname"] = "Please name your project.";
	$problems["email"] = "Please provide a valid email address.";
	$problems["license"] = "Please indicate a copyright license.";
	
	if(array_key_exists($key, $problems)){
	    $error_message = $problems[$key];
	}
	else{
	    $error_message = "";
	}
	return $error_message;
    }
    
    
    public function email_validation_recheck($email, $problems){
        
        $validator = new Zend_Validate_EmailAddress();
        if ($validator->isValid($email)) {
            if(array_key_exists("email", $problems)){
                unset($problems["email"]);
            }
        }
        else{
        
        }
        
        return $problems;
    }
    
    
    
}
