<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

// increase the memory limit
//ini_set("memory_limit", "2048M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");


class aboutController extends Zend_Controller_Action
{   
     
    public function indexAction()
    {
		  OpenContext_SocialTracking::update_referring_link('about', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
    }
    
    public function usesAction() {
		
		
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('about - uses', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        //$this->view->result = $result;
                
    }
    
    public function conceptsAction() {
		
		
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('about - concepts', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        //$this->view->result = $result;
                
    }
    
   
    public function technologyAction() {
		
		
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('about - tech', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        //$this->view->result = $result;
                
    }
    
    public function servicesAction() {
		
		
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('about - services', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        //$this->view->result = $result;
                
    }
    
    public function bibliographyAction() {
		
		
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('about - biblio', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        //$this->view->result = $result;
                
    }
    
    
    public function peopleAction() {
		
		
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('about - people', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        //$this->view->result = $result;
                
    }
    
    public function staffAction() {
		
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('about - people', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        return $this->render('people');
                
    }
    
    public function sponsorsAction() {
		
		
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('about - sponsors', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        //$this->view->result = $result;
                
    }
    
    public function publishingAction() {
		
		
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('about - publishing', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        //$this->view->result = $result;
                
    }
    
    
    public function privacyAction() {
		
		
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('about - privacy', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        //$this->view->result = $result;
                
    }
    
    public function intellectualPropertyAction() {
		
		
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('about - IP', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        //$this->view->result = $result;
                
    }
    
    public function aboutNewAction() {
		
		
        $this->view->ok = true;
	$this->view->blubbie = "Blubbie";
        //OpenContext_SocialTracking::update_referring_link('about - IP', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        //$this->view->result = $result;
                
    }
    
    
    //this displays a form for estimating publication costs
     public function estimateAction() {
        $this->view->ok = true;
        OpenContext_SocialTracking::update_referring_link('about - estimate',
							  $this->_request->getRequestUri(),
							  @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        
		  //use if there are validation problems
		  $this->view->problems = false;
		  $this->view->badRequests = false;
		  
		  $form = new EstimateForm;
		  $this->view->numFieldsValues = $form->valArray;        
    }
    
    
    
    
    //invokes publication costs estimation form
    public function getForm(){
		  /*
		  OpenContext_SocialTracking::update_referring_link('about - estimate-results',
								$this->_request->getRequestUri(),
							  @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
		  */
        return new EstimateForm(array(
            'action' => '/about/results',
            'method' => 'post',
        ));
    }
    
    
    
    
    
    //validates publication costs estimation form results
    //if not valid, returns user to the estimation form with validation error message
    //if valid, shows results and emails the user
    public function resultsAction() {
		
        OpenContext_SocialTracking::update_referring_link('about - results', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);        
        //$this->view->result = $result;
        
		  $request = $this->getRequest();

        // Check if we have a POST request
        if (!$request->isPost()) {
            return $this->_helper->redirector('index');
        }

        // Get our form and validate it
        $form = $this->getForm();
		  $validForm = true;
		  $goodNums = true;
		  $problems = array();
        if (!$form->isValid($request->getPost())) {
				$validForm = false;
		  if(!$form->name->isValid($request->getPost())){
				$problems["name"] = true;
		  }
		  if(!$form->phone->isValid($request->getPost())){
				$problems["phone"] = true;
		  }
		  if(!$form->projname->isValid($request->getPost())){
				$problems["projname"] = true;
		  }
		  if(!$form->email->isValid($request->getPost())){
				$problems["email"] = true;
		  }
		  if(!$form->license->isValid($request->getPost())){
				$problems["license"] = true;
		  }
		  if(!isset($_REQUEST["license"])){
				$validForm = false;
				$problems["license"] = true;
		  }
	    
		  $formValidation = $form->more_form_validation($problems, $validForm, $goodNums);
		  $problems = $formValidation["problems"];
		  
		  //work around for email validation error
		  $problems = $form->email_validation_recheck($_REQUEST["email"], $problems);
	 }
	 else{
		  $formValidation = $form->more_form_validation($problems, $validForm, $goodNums);
		  $problems = $formValidation["problems"];
		  $validForm = $formValidation["validForm"];
		  $goodNums = $formValidation["goodNums"];
	 }
	
	
	if(!$validForm){
	    $problemsJSON = urlencode(Zend_Json::encode($problems)); 
            $badRequestJSON = urlencode(Zend_Json::encode($_REQUEST));
	    
	    //$this->_helper->viewRenderer->setNoRender();
	    
	    //header("Location: ".@$_SERVER['HTTP_REFERER']."?invalid=true&problems=".$problemsJSON."&request=".$badRequestJSON);
	    $problemsView = array();
	    foreach($problems as $key=>$value){
		$problemsView[$key] = $form->validation_error_messages($key);
	    }
	    $this->view->problems = $problemsView;
	    $this->view->badRequests = $_REQUEST;
	    $this->view->numFieldsValues = $form->valArray;
	    
            return $this->render('estimate'); // re-render the estimate form
        }
	else{
	    $problems = false;
	    //$this->_helper->viewRenderer->setNoRender();
	    $num_years = $_REQUEST["num_years"];
	    $num_sets = $_REQUEST["num_sets"];
	    $num_tabs = $_REQUEST["num_tabs"];
	    
	    $num_images = $_REQUEST["num_images"];
	    $num_vids = $_REQUEST["num_vids"];
	    $num_docs = $_REQUEST["num_docs"];
	    $num_gis = $_REQUEST["num_gis"];
	    $num_other = $_REQUEST["num_other"];
	    
	    $dataArray = array("num_years" => $num_years,
			       "num_sets" => $num_sets,
			       "num_tabs" => $num_tabs,
			       "num_images" => $num_images,
			       "num_vids" =>  $num_vids,
			       "num_docs" => $num_docs,
			       "num_gis" => $num_gis,
			       "num_other"=> $num_other
			       );
	    
	    $mediaArray = array("num_images" => $form->getValueText("num_images", $num_images),
				"num_vids" => $form->getValueText("num_vids", $num_vids),
				"num_docs" => $form->getValueText("num_docs", $num_docs),
				"num_gis" => $form->getValueText("num_gis", $num_gis),
				"num_other" => $form->getValueText("num_other", $num_other));
	    
	    
	    $baseCost = 250; // 250 dollars for base cost
	    $maxCost = 7500; // maximum cost
	    
	    $imageCost = 5; //base cost for images
	    $vidCost = 10; //base cost for audio, video
	    $docsCost = 20; //base cost for docs
	    $gisCost = 50; //base cost for GIS
	    $otherCost = 50; //base cost for other
	    
	    $cost = ($baseCost *  ($num_years/2)) + ($baseCost * ($num_sets /2)) + ($baseCost * ($num_tabs / 5));
	    //echo "Data: ". $cost. "<br/>";
	    
	    
	    $cost = $cost + ($imageCost *(($num_images+10)/15));
	    //echo "+Images: ". $cost. "<br/>";
	    $cost = $cost + ($vidCost *(($num_vids)/20));
	    //echo "+Vids: ". $cost. "<br/>";
	    $cost = $cost + ($docsCost *(($num_docs)/25));
	    //echo "+Docs: ". $cost. "<br/>";
	    $cost = $cost + ($gisCost *(($num_gis)/2));
	    //echo "+GIS: ". $cost. "<br/>";
	    $cost = $cost + ($otherCost *(($num_other)/2));
	    //echo "+Other: ". $cost. "<br/>";
	    
	    //echo "Estimated: ". $cost. "<br/>";
	    if($baseCost > $cost){
		$cost = $baseCost;
	    }
	    if($maxCost < $cost){
		$cost = $maxCost;
		$maxLimit = true;
	    }
	    else{
		$maxLimit = false;
	    }
	    
	    $licenseArray = array("cc0"=>"Creative Commons Zero, Public Domain Dedication",
				  "by" =>"Creative Commons Attribution License");
	    
	    $licenseNoteArray = array("cc0"=>"This choice maximizes the potential for scientific reuse and interoperability of this dataset. It is the standard adopted by leading data-sharing initiatives such as Dryad (http://datadryad.org), a major data dissemination service for evolution, ecology, and related fields.",
				     
				  "by" =>"This license choice has been endorsed for the dissemination of open research and educational content by a number of leading scientific publishers, including the Public Library of Science (http://PloS.org) and others.",
				  
				  "other" => "Although this license permits copying of content, it complicates scientific reuse and interoperability of data. Its use is appropriate if other needs outweigh scientific concerns.");
	    
		  $licenseSel = $_REQUEST["license"];
		  $licenseNote = $licenseNoteArray[$licenseSel];
		  
		  if($licenseSel == "other"){
				$license = $_REQUEST["rlicense"];
				$licenseExp = $_REQUEST["restrict_com"];
		  }
		  else{
				$license = $licenseArray[$licenseSel];
				$licenseExp = false;
		  }
		  
		  if(isset($_REQUEST["diss"])){
				$cost = $cost*.75;
				$diss = true;
				$dissText = "(Doctoral Dissertation / Graduate Student Project)";
		  }
		  else{
				$diss = false;
				$dissText = "";
		  }
		  
		  $costHundred = round($cost,-2);
		  $costTen = round($cost,-1);
		  $costRemainder = $costTen - $costHundred;
		  
		  $finalCost = $costHundred;
		  if($costRemainder >= 30){
				$finalCost = $costHundred + 50;
		  }
		  if($costRemainder <= -30){
				$finalCost = $costHundred - 50;
		  }
	    
	    /*
	    echo $cost."<br/>";
	    echo $costTen."<br/>";
	    echo $costHundred."<br/>";
	    echo $finalCost."<br/>";
	    */
	    
	    if(substr_count($_REQUEST["comment"], "If you have additional comments ")>0){
				$comment = "(None)";
	    }
	    else{
				$comment = $_REQUEST["comment"];
	    }
	    
		  $estimateID = substr(md5($_REQUEST["email"].(microtime())),0,10);
		  
		  
		  $this->view->cost = $finalCost;
		  $this->view->max = $maxLimit;
		  $this->view->dataset = $dataArray;
		  $this->view->media = $mediaArray;
		  $this->view->projname = $_REQUEST["projname"];
		  $this->view->name = $_REQUEST["name"];
		  $this->view->email = $_REQUEST["email"];
		  $this->view->phone = $_REQUEST["phone"];
		  $this->view->license = $license;
		  $this->view->licenseNote = $licenseNote;
		  $this->view->licenseExp = $licenseExp;
		  $this->view->comment = $comment;
		  $this->view->diss = $diss;
		  $this->view->estimateID = $estimateID;
	 
		  $saveEstimate = array("id"=> $estimateID,
				  "cost"=> $finalCost,
				  "max" => $maxLimit,
				  "dataset" => $dataArray,
				  "media" => $mediaArray,
				  "name" => $_REQUEST["name"],
				  "email" => $_REQUEST["email"],
				  "phone" => $_REQUEST["phone"],
				  "projname" => $_REQUEST["projname"],
				  "diss" => $diss,
				  "license" => $license,
				  "licenseNote" => $licenseNote,
				  "licenseExp" => $licenseExp,
				  "comment" => $comment);
	    
		  $frontendOptions = array(
                'lifetime' => NULL, // cache lifetime, measured in seconds, 7200 = 2 hours
                'automatic_serialization' => true
		  );
		    
		  $backendOptions = array(
				'cache_dir' => './estimate_cache/' // Directory where to put the cache files
		  );
		    
		  $cache = Zend_Cache::factory('Core',
				 'File',
				 $frontendOptions,
				 $backendOptions);
	    
		  $cache->save($saveEstimate, $estimateID); //save result to the cache
	    
	    
	    
		  $emailBody = '
Dear '.$saveEstimate['name'].',

Thank you for your interest in publishing your data with Open Context. The cost for publishing and archiving digital content for the project "'.$saveEstimate["projname"].'" is estimated at $'.$finalCost.'. The reference number for this estimate is: '.$estimateID.' (generated on '.date("F j, Y, g:i a").'). However, due to the uncertainties of estimating project complexity, please note that this estimate does not imply or constitute a binding agreement.

If you are an NSF or NEH grant-seeker, please use this reference number in the Data Management Plan portion of your grant application. Other granting programs may want to see similar management and / or dissemination plans. You may use or adapt the following description of Open Context’s services and benefits for sharing and preserving your project’s data:

//-----

Digital content generated by this project will be published in Open Context (http://opencontext.org). Publication in Open Context ensures that the data are freely available and openly licensed so they can be reused and combined with research collections from elsewhere on the Web. All project data will be documented with relevant metadata (“information about information”), citation information, and a permanent URL to maximize reach and potential for reuse. All content published in Open Context is archived by the California Digital Library, a leader in the preservation of scientific data. These features enhance the quality, discoverability, and interoperability of project data beyond what can be achieved by simply posting data to a website.

------//


The following provides additional detail about other features and design attributes Open Context provides to maximize your project’s digital content:


//-----

(1) Deep Linking: Every item of the project dataset in Open Context will have its own Web Address (URL). This policy of “one webpage per potsherd” enables very specific referencing and citation of Open Context content. Citation is further encouraged by dynamic generation of suggested citation text, and by support of metadata standards used in Zotero, a popular reference management tool. In addition, deep linking enables users to reference and tag project content and apply sophisticated and more formal semantic Web standards.

(2) Open Access: Open Context requires no login to access, download, or copy data into another system. Its stated policy to refrain from monitoring individual user activities is consistent with the American Library Association’s code of professional ethics to protect patron rights to privacy, confidentiality, and academic freedom. The absence of a login barrier also allows Open Context content to be fully indexed by commercial search engines, further enhancing discoverability and impact.

(3) Machine-Readable Data and Services: Open Context data and querying services come in a variety of data formats and protocols (Atom, GeoRSS, KML, ArchaeoML/XML, JSON, CSV, OAI-PMH). These measures help ensure that project content can flow into other applications that may visualize it in new ways or combine it with data from other sources.

(4) Open Licensing: Project data will be released under Creative Commons (http://creativecommons.org) copyright licenses. These licenses open the door to future research, instruction, and other applications. These standard licenses explicitly grant permissions for reuse of content, provided attribution is given to content producers. To facilitate interoperability, the licenses are expressed as standard, machine-readable metadata, using the RDFa format. Creative Commons licenses and Open Context’s machine-readable data help insure this project’s content can be moved to other applications and archives. Because content is not “trapped” in Open Context, Open Context will enable, not inhibit, future uses of these data as new systems emerge.

(5) Data Publishing and Editorial Review: Open Context has an editorial process to review datasets prior to publication. Rather than simply being “shared,” datasets disseminated through Open Context are first reviewed, edited, and annotated for formalized publication and archiving. Contributing researchers and editors collaborate to identify, track, and resolve issues, clean data, and create needed documentation. This gives datasets more value as intelligible and high-quality scholarly outputs.

(6) Linked Data for Interoperability: Open Context participates in a distributed, multi-disciplinary information management ecosystem that includes digital libraries, repositories, and many high-quality information resources. As a collaborative participant in this ecosystem, Open Context uses “Linked Open Data” methods to make its content easier to relate to the content published by others. Linked Open Data (LOD) centers on the use of Web URIs to express meaning and structure in a machine-readable manner. A highly networked and distributed approach to annotating data, LOD represents current best-practice in data sharing. By adopting and promoting LOD approaches, Open Context participates (and will do so more fully) in a “Web of Data,” a distributed and collaborative information ecosystem that sees wide participation and innovation.

(7) Digital Library Archival Support: Open Context has archival support from the California Digital Library (CDL), one of the world leaders in digital preservation and curation. The CDL continually accessions Open Context content via Web services connecting Open Context and the CDL Merritt digital repository. Through use of these Web services, the Merritt repository gains publication and update information, as well as other Dublin Core metadata needed for accession. Upon accession into the Merritt repository, Merritt mints persistent identifiers (ARKs and DOIs). Both DOIs and ARKs comply with DataCite requirements for persistent identification of resources, enabling Open Context to use the DataCite standard. With DataCite, citations to Open Context data can be tracked using digital library infrastructure and services, eventually enabling future determinations of citation impact factors.

(8) Robust Version Control: Version control for datasets will begin in pre-publication editing, cleanup and documentation stages and continue after publication. Open Refine, software Open Context uses to coordinate edits, tracks versions and enables roll-backs of changes. The entire history of data edits is stored, and can be retrieved and archived as a compressed JSON file. This revision history can be archived along with the final published version of a dataset in Merritt. [Note: Because of privacy concerns, Open Context only requires archiving of finalized datasets and leaves archiving of draft versions of data to the discretion of contributors.] Following publication, Open Context uses GitHub for public version control and issue tracking of data. The Merritt digital repository also stores a representation of all versions of content accessioned from Open Context. Thus, Merritt ensures the continued accessibility and preservation of earlier versions of data published with Open Context.


----//


Your Project Information: 
Project Name: '.$saveEstimate["projname"].' '.$dissText.'
Estimated Cost: $'.$finalCost.'
Reference ID: '.$estimateID.'
Proposed License: '.$license.'
License Note: '.$licenseNote.' 

Researcher Comments: ['.$comment.'] ['.$licenseExp.']

Estimated Scale and Complexity of Project Data:
Number of Years: '.$num_years.'
Number of Specialists: '.$num_sets.'
Number of Tables for Publication: '.$num_tabs.'
Number of Image Files: '.$mediaArray["num_images"].'
Number of Audio and Video Files: '.$mediaArray["num_vids"].'
Number of Documents (HTML, text, PDF): '.$mediaArray["num_docs"].'
Number of GIS layers: '.$mediaArray["num_gis"].'
Number of Other Specialized Datasets: '.$mediaArray["num_other"].'


Please remember that this estimate is based on the assumption that your data are properly documented and prepared for publication. You must also use appropriate technical and documentation standards for specific types of specialized data (for details see: http://ads.ahds.ac.uk/project/goodguides/g2gp.html). The cost estimate is also based on the assumption that your data will require little cleaning. To help improve data quality, be sure to use appropriate error-correction and validation processes as you collect your data. This will make your own analyses more reliable. It will also facilitate publication and future reuse of research data. For more on data quality and documentation requirements please see: http://opencontext.org/about/publishing

Thank you for considering Open Context for publishing and archiving your project’s data! Please retain a copy of this email for future reference. If you have questions or when you are ready to publish your content, please contact us at publish@opencontext.org. 

- The Open Context Editorial Team
';
	    $requesterEmail = $saveEstimate["email"];
	    $requesterName = $saveEstimate["name"];
	    
	    $mail = new Zend_Mail();
	    //$google = false;
	    $google = true;
	    
	    if($google){
		try {
		    $configMail = array('auth' => 'login',
			'username' => OpenContext_OCConfig::get_PublishUserName(true),
			'password' => OpenContext_OCConfig::get_PublishPassword(true), 'port' => 465, 'ssl' => 'ssl');
		    //$transport  = new Zend_Mail_Transport_Smtp('mail.opencontext.org', $configMail);
		    $transport  = new Zend_Mail_Transport_Smtp('smtp.gmail.com', $configMail);
		    
		    $mail->setBodyText($emailBody);
		    $mail->setFrom(((OpenContext_OCConfig::get_PublishUserName(true)).'@gmail.com'), 'Open Context Publishing');
		    //$mail->setReplyTo('publishing@opencontext.org', 'Open Context Publishing');
		    //Reply-To
		    $mail->addHeader('Reply-To', 'publish@opencontext.org');
		    $mail->addHeader('X-Mailer', 'PHP/' . phpversion());
		    //$mail->addHeader('Return-Path', 'publishing@opencontext.org');
		    //$mail->addHeader('X-MailGenerator', 'Open Context Publication Fee Estimation Form');
		    //$mail->addTo("'".$requesterEmail."'", "'".$requesterName."'");
		    $mail->addTo($requesterEmail, $requesterName);
		    $mail->addCc('skansa@alexandriaarchive.org', 'Sarah Kansa');
		    $mail->addBcc('ekansa@ischool.berkeley.edu', 'Eric Kansa');
		    $mail->setSubject('Open Context Est. [Reference ID: '.$estimateID.']: '.substr($saveEstimate["projname"],0,15));
		    $mail->send($transport);
		    $this->view->mailError = "";
		} catch (Zend_Exception $e) {
		    $this->view->mailError = "We had an email problem!
		    Please contact publishing@opencontext.org to let us know there's a bug! <br/><span class='tinyText>'".$e."</span>";
		} 
	    }
	    else{
		try {
		    $configMail = array('auth' => 'login',
			'username' => OpenContext_OCConfig::get_PublishUserName(),
			'password' => OpenContext_OCConfig::get_PublishPassword(), 'port' => 26);
		    //$transport  = new Zend_Mail_Transport_Smtp('mail.opencontext.org', $configMail);
		    $transport  = new Zend_Mail_Transport_Smtp('mail.opencontext.org', $configMail);
		    
		    $mail->setBodyText($emailBody);
		    $mail->setFrom('publishing@opencontext.org', 'Open Context Publishing');
		    //$mail->setReplyTo('publishing@opencontext.org', 'Open Context Publishing');
		    //Reply-To
		    $mail->addHeader('Reply-To', 'publishing@opencontext.org');
		    $mail->addHeader('X-Mailer', 'PHP/' . phpversion());
		    //$mail->addHeader('Return-Path', 'publishing@opencontext.org');
		    $mail->addHeader('X-MailGenerator', 'Open Context Publication Fee Estimation Form');
		    //$mail->addTo("'".$requesterEmail."'", "'".$requesterName."'");
		    $mail->addTo($requesterEmail, $requesterName);
		    $mail->addCc('skansa@alexandriaarchive.org', 'Sarah Kansa');
		    $mail->addBcc('ekansa@ischool.berkeley.edu', 'Eric Kansa');
		    $mail->setSubject('Open Context Est. [Reference ID: '.$estimateID.']: '.substr($saveEstimate["projname"],0,15));
		    $mail->send($transport);
		    $this->view->mailError = "";
		} catch (Zend_Exception $e) {
		    $this->view->mailError = "We had an email problem!
		    Please contact publishing@opencontext.org to let us know there's a bug! <br/><span class='tinyText>'".$e."</span>";
		}
	    }
	    
	}
	    
    }
    
    
    
    
    
    
    
    public function feedListAction() {
		
	$this->_helper->viewRenderer->setNoRender();
	$atomURI = $_REQUEST["uri"];
        $itemList = OpenContext_ReadResultAtom::FeedGetURIs($atomURI);
        
	
	$host = OpenContext_OCConfig::get_host_config();
	
	//echo Zend_Json::encode($itemList);
        
	/* 
	echo "<br/>";
	echo "<br/>";
	echo "DELETE FROM published_docs <br/>";
	echo "WHERE ";
	*/
	$i=0;
	foreach($itemList["results"] as $badURI){
	    
	    $badID = str_replace("http://opencontext.org/subjects/", "", $badURI);
	    $badID = str_replace("http://opencontext.org/media/", "", $badID);
	    $badID = str_replace("http://opencontext/media/", "", $badID);
	    /*
	    if($i>0){
		echo "<br/> OR "; 
	    }
	    else{
		echo "<br/>";
	    }
	    echo " item_uuid = '$badID' ";
	    */
	    $doURL = "http://opencontext.org/publish/docadd?type=media&doUpdate=true&id=".$badID;
	    //$doURL = "http://opencontext.org/publish/docadd?type=space&doUpdate=true&id=".$badID;
	    //$doURL = "http://opencontext/publish/docadd?type=media&doUpdate=true&id=".$badID;
	    //$doURL = "http://opencontext/publish/docadd?type=space&doUpdate=true&id=".$badID;
	    echo "<br/><a href='".$doURL."'>".$badID."</a>";
	    
	    $output = file_get_contents($doURL);
	    echo "<br/>".$output."<br/><br/>";
	    
	    
	    $i++;
	}    
	
	
    }
    
    
    public function tileAction() {
		
	$this->_helper->viewRenderer->setNoRender();
	$geo = $_REQUEST["geo"];
	$geoArray = explode(",", $geo);
	$lat = $geoArray[0];
	$lon = $geoArray[1];
	
        $tile = OpenContext_GeoTile::assignTile($lat, $lon);
                
	echo "<br/><br/><br/>".$tile;
	$table = OpenContext_GeoTile::printTile($tile);
	echo "<br/>".$table;

	
    }
    
    
    
}

