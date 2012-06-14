<?php

class OpenContext_UserMessages {
	
	const solrDown = false; // has solr been shut down
	const solrDownSoon = false; // is there a planned shut down of solr
	
	const downStart = "March 3, 2011"; //date of the start of down time
	const downEnd = "March 4, 2011"; //date of the start of down time
	const dateFormat = "l, F j, o";
	
	public static function getSolrDownMessage(){
		$downStart = date(self::dateFormat, strtotime(self::downStart));
		$downEnd = date(self::dateFormat, strtotime(self::downEnd));
		
		$timeNow = time();
		if(strtotime($downEnd) < $timeNow){
			$nowDate = date("Y-m-d",  $timeNow);
			$nowDateArray = explode("-", $nowDate);
			$nowDay = $nowDateArray[2];
			if($nowDay < 26){
				$downEnd = $nowDateArray[0]."-".$nowDateArray[1]."-".($nowDateArray[2] + 2);
			}
			else{
				$downEnd = $nowDateArray[0]."-".($nowDateArray[1]+1)."-01";
			}
			
			$downEnd = date(self::dateFormat, strtotime($downEnd));
		}
		
		
		$newFeatures = OpenContext_UserMessages::getNewSolrFeatures();
		
		$message = 
		"
		<div id='downMessage' class='bodyText'>
			<p>Our team is busy upgrading Open Context's query and browse features. In order to complete this
			upgrade, we had to disable query features. We estimate that
			the upgrade will be completed on <strong>".$downEnd."</strong>.
			</p>
			<br/>
			
			".$newFeatures."
			
		</div>
		";
		
		return $message;
	}
	
	
	public static function getNewSolrFeatures($indexNumber = 0){
		
		$messageArray = array();
		
		$messageArray[0] =
		"
		<div id='newFeatures'>
		<p>This upgrade will give Open Context the following new capabilities:</p>
		<ul>
			<li><strong>Query Hierarchic Taxonomies</strong>: Open Context will support queries 
			on custom hierarchic taxonomies that may exist in published dataset. A search on a 'high level' (parent) node
			in a taxonomy will also return items described by 'child' nodes in that taxonomy.
			</li>
			<li><strong>Linked Data</strong>: Open Context will not support <a href='http://www.w3.org/TR/rdf-sparql-query/'
			title='Introduction to this standard'/>SPARQL</a> queries (a Semantic Web standard for querying
			RDF data), but it will have enhanced querying capabilities for 'Linked Data'. Open Context will support queries for
			properties identified by a URI and for objects identified by a URI. The initial application of these querying capabilities
			will be to support searches based on Linked Data references to <a href='http://pleiades.stoa.org/'>Pleiades</a>.
			</li>
			<li><strong>More Comprehensive Indexing</strong>: Before this upgrade, Open Context key-word searches where limited to either
			locations/objects or descriptions of media files. However, in monitoring how users search Open Context, it becamse clear that
			we needed to offer more comprehesive search capabilities extending beyond Open Context's published data. Users also need
			to be able to search and find content describing how to use Open Context, its publishing guidelines, and its policies.
			After this upgrade, Open Context will support much more comprehesive searches across Open Context's documentation, project
			and collection descriptions, descriptions of data tables, as well as the data and media files that it publishes.
			</li>
		</ul>
		</div>
		";
		
		return $messageArray[$indexNumber];
	}//end function
	
	
	public static function getSolrDownSoonMessage(){
		if(self::solrDownSoon){
			$downStart = date(self::dateFormat, strtotime(self::downStart));
			$downEnd = date(self::dateFormat, strtotime(self::downEnd));
			$message = "
			<div id='downSoon'>
				<p>Open Context will be upgraded shortly. Between <strong>".$downStart."</strong>
				and <strong>".$downEnd."</strong> search and query features will be off-line.
				<a href='../about/announcements'>Click here</a> to see
				how the upgrade will improve Open Context. </p>		
			</div>
			";
		}
		else{
			$message = "";
		}
		
		return $message;
	}//end function
	
	public static function isSolrDown(){
		
		return self::solrDown;
		
	}//end function
	
	
	public static function isSolrDownSoon(){
		
		return self::solrDownSoon;
		
	}//end function
	
	//gets date of end of service interruption in HTTP format
	public static function httpEndDate(){
		return date('D, d M Y H:i:s \G\M\T', strtotime(self::downEnd));
	}
	
}//end class declaration

?>
