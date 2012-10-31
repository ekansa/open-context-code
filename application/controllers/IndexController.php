<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class indexController extends Zend_Controller_Action
{   
      
    public function indexAction()
    {
	
		  $host = OpenContext_OCConfig::get_host_config();
		  if(substr_count($_SERVER['HTTP_HOST'], "www.")>0){
				$reDirectURI = $host;
				header('Location: '.$reDirectURI);
				exit;
		  }
    }
    
    public function robotsAction() {
		  $this->_helper->viewRenderer->setNoRender();
		  $host = OpenContext_OCConfig::get_host_config();

		  $robots = "
		  User-agent:*\r\n
		  Crawl-Delay:0.5\r\n
		  Disallow:/cgi-bin/\r\n
		  Disallow:/tmp/\r\n
		  Disallow:/sets/\r\n
		  Disallow:/lightbox/\r\n
		  Disallow:/search/\r\n
		  Disallow: /*&\r\n
		  Sitemap: http://opencontext.org/data/siteMap-1.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-2.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-3.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-4.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-5.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-6.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-7.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-8.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-9.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-10.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-11.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-12.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-13.xml \r\n
		  Sitemap: http://opencontext.org/data/siteMap-14.xml \r\n
		  ";
		  
		  header("Content-Type: text/plain");
		  echo $robots;
		
    }
    
	 //this is for the timemap js, for some reason it likes to request __history__.html
	 public function historyAction() {
		  $this->_helper->viewRenderer->setNoRender();
		  header("Content-Type: text/plain");
		  echo true;
	 }
    
    public function rssProjectsRss2PhpAction() {
		$this->_helper->viewRenderer->setNoRender();
		$host = OpenContext_OCConfig::get_host_config();
		$reDirectURI = $host."/projects/.atom";
		header('Location: '.$reDirectURI);
		exit;
    }
    
    
   
}


