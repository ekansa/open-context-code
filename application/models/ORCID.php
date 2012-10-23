<?php

/*
 Interacts with the ORCID API
 to request publication and grant information for ORCID users. 
*/

class ORCID {
 
//database content 
public $orcID;
public $profileObj; //object from JSON response
public $APIresponse;
const APIuri = "http://orcid.org/"; //base uri API


//get an ORCID from an ORCID URI
function uriToORCID($orcidURI){
    $this->orcID = str_replace("http://orcid.org/", "", $orcidURI);
}


function getProfile($orcidURI){
    $this->uriToORCID($orcidURI);
    $jsonString = false;
    $jsonString = $this->APIgetProfile();
    if(!$jsonString){
        return false;
    }
    else{
        $this->profileObj = Zend_Json::decode($jsonString);
        if(!$this->profileObj){
            $response = $this->APIresponse;
            $jsonString = $response->getBody();
            $jsonString = $this->gzdecode($jsonString);
            $this->profileObj = Zend_Json::decode($jsonString);
            if(!$this->profileObj){
                $this->profileObj = "no decode";
                return false;
            }
            else{
                 return true;
            }
        }
        else{
            return true;
        }
    }
}


//this function gets a public profile
function APIgetProfile($bodyOnly = true){

    $requestURI = self::APIuri.$this->orcID."/orcid-record";
   
    $client = new Zend_Http_Client($requestURI);
    $client->setHeaders('Accept', 'application/orcid+json');
    
    $response = $client->request("GET"); //send the request, using the POST method
    $this->APIresponse = $response;
    if(!$response){
        //error in API request
        return false;
    }
    else{
        //good request
        if(!$bodyOnly){
            return $response;
        }
        else{
            return $response->getBody();
        }
    }
   
}


function gzdecode($data){
  $g=tempnam('/tmp','ff');
  @file_put_contents($g,$data);
  ob_start();
  readgzfile($g);
  $d=ob_get_clean();
  return $d;
}



}//end class

?>
