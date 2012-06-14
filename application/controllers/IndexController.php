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
	
	
	OpenContext_SocialTracking::update_referring_link('home', $this->_request->getRequestUri(), @$_SERVER['HTTP_USER_AGENT'], @$_SERVER['HTTP_REFERER']);
	
	$useragent = @$_SERVER['HTTP_USER_AGENT'];
    	if(strstr($useragent,"MSIE 6")||strstr($useragent,"MSIE 7")||strstr($useragent,"MSIE")||strstr($useragent,"Android")){
	    return $this->render('ieindex'); // re-render the estimate form
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
	
	echo $robots;
		
    }
    
    
    
    public function rssProjectsRss2PhpAction() {
		$this->_helper->viewRenderer->setNoRender();
		$host = OpenContext_OCConfig::get_host_config();
		$reDirectURI = $host."/projects/.atom";
		header('Location: '.$reDirectURI);
		exit;
    }
    
    
   
}


