<?php

require_once("appuri.php");

class App_Cleaner {
	
	private $base_uri;
	private $uri;
	
	public function __construct($uri, $base_uri) {
		$this->base_uri = $base_uri;
		$this->uri = $uri;
	}
	
	public function make_conforming($doc) {
		
		// Required fields:
		/* id, title, updated, author
		 * sometimes summary or content, but we don't require it.
		 */
		
		// id
		$id_list = $doc->getElementsByTagNameNS("http://www.w3.org/2005/Atom","id");
		if ($id_list->length == 0) {
			$id = $doc->createElementNS("http://www.w3.org/2005/Atom","id");
			$id->appendChild( $doc->createTextNode($this->create_id()) );
			
			$doc->documentElement->appendChild($id);
		} else {
			// always use our own ID.
			$id = $id_list->item(0);
			while ($id->childNodes->length !== 0) {
				$id->removeChild( $id->childNodes->item(0) );
			}
			$id->appendChild( $doc->createTextNode($this->create_id()) );
		}
		
		// title
		$title_list = $doc->getElementsByTagNameNS("http://www.w3.org/2005/Atom","title");
		if ($title_list->length == 0) {
			$title = $doc->createElementNS("http://www.w3.org/2005/Atom","title");
			$title->appendChild( $doc->createTextNode("untitled") );
			
			$doc->documentElement->appendChild($title);
		} else {
			$title = $title_list->item(0);
			if ( $title->textContent == "" ) {
				$title->appendChild( $doc->createTextNode("untitled") );
			}
		}
		
		// updated
		$updated_list = $doc->getElementsByTagNameNS("http://www.w3.org/2005/Atom","updated");
		if ($updated_list->length == 0) {
			$updated = $doc->createElementNS("http://www.w3.org/2005/Atom","updated");
			$updated->appendChild( $doc->createTextNode(date(DATE_ATOM)) );
			
			$doc->documentElement->appendChild($updated);
		} else {
			$updated = $updated_list->item(0);
			if ( $updated->textContent == "" ) {
				$updated->appendChild( $doc->createTextNode(date(DATE_ATOM)) );
			}
		}
		
		// author
		$author_list = $doc->getElementsByTagNameNS("http://www.w3.org/2005/Atom","author");
		if ($author_list->length == 0) {
			$author = $doc->createElementNS("http://www.w3.org/2005/Atom","author");
			$doc->documentElement->appendChild($author);
		} else {
			$author = $author_list->item(0);
		}
		$name_list = $author->getElementsByTagNameNS("http://www.w3.org/2005/Atom","name");
		if ($name_list->length == 0) {
			$name = $doc->createElementNS("http://www.w3.org/2005/Atom","name");
			$name->appendChild( $doc->createTextNode("anonymous") );
			$author->appendChild($name);
		} else {
			$name = $name_list->item(0);
			if ($name == "") {
				$name->appendChild( $doc->createTextNode("anonymous") );
			}
		}
		
	}
	
	public function check_edit_links($new, $original) {
		
		// find links in original
		$links = $original->getElementsByTagNameNS("http://www.w3.org/2005/Atom","link");
		foreach ( $links as $link ) {
			if ( strcasecmp($link->getAttribute("rel"),"edit") == 0 ) {
				$orig_edit = URI::resolve_node( $link->getAttributeNode("href"), $this->uri );
			}
			if ( strcasecmp($link->getAttribute("rel"),"edit-media") == 0 ) {
				$orig_media = URI::resolve_node( $link->getAttributeNode("href"), $this->uri );
			}
		}
		if ( isset($orig_media) ) {
			$content = $original->getElementsByTagNameNS
									("http://www.w3.org/2005/Atom","content")->item(0);
			$content_type = $content->getAttribute("type");
			$content_uri = URI::resolve_node( $content->getAttributeNode("src"), $this->uri );
		}
		
		// find in new
		$links = $new->getElementsByTagNameNS("http://www.w3.org/2005/Atom","link");
		foreach ( $links as $link ) {
			if ( strcasecmp($link->getAttribute("rel"),"edit") == 0 ) {
				$link->setAttribute("href",$orig_edit);
				$new_edit = 1;
			}
			if ( strcasecmp($link->getAttribute("rel"),"edit-media") == 0 ) {
				$link->setAttribute("href",$orig_media);
				$new_media= 1;
			}
		}
		$contents = $new->getElementsByTagNameNS("http://www.w3.org/2005/Atom","content");
		if ($contents->length > 1 ) {
			throw new App_HTTPException("Multiple contents not allowed.",400);
		} else if ($contents->length === 1 && isset($orig_media)) {
			$content = $contents->item(0);
			$content->setAttribute("src",$content_uri);
			$content->setAttribute("type",$content_type);
			$new_content = 1;
		}
		
		// if any of the links are missing -> restore
		if (!isset($new_edit)) {
			$link = $new->createElementNS("http://www.w3.org/2005/Atom","link");
			$link->setAttribute("rel","edit");
			$link->setAttribute("href",$orig_edit);
			$new->documentElement->appendChild($link);
		}
		if (isset($orig_media) && !isset($new_media)) {
			$link = $new->createElementNS("http://www.w3.org/2005/Atom","link");
			$link->setAttribute("rel","edit-media");
			$link->setAttribute("href",$orig_media);
			$new->documentElement->appendChild($link);
		}
		if (isset($orig_media) && !isset($new_content)) {
			$content = $new->createElementNS("http://www.w3.org/2005/Atom","content");
			$content->setAttribute("src",$content_uri);
			$content->setAttribute("type",$content_type);
			$new->documentElement->appendChild($content);
		}
		
	}
	
	
	private function create_id() {
		$domain = explode("/", str_replace("http://","",$this->base_uri));
		if ( is_array($domain) ) {
			$domain = $domain[0];
		}
		$path = explode("/",str_replace($this->base_uri,"",$this->uri));
		$col = $path[0];
		$names = explode(".",$path[count($path)-1]);
		$name = $names[0];
		
		$year = date("Y"); $month = date("m"); $day = date("d");
		$id = "tag:".$domain.",".$year."-".$month."-".$day.":".$col."/".$name;
		
		return $id;
	}
}
