<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

error_reporting(E_ALL ^ E_NOTICE);

class ContextsController extends Zend_Controller_Action {

// TODO: write an a zend action helper to handle access to solr rather than repeat code in each action

	public function indexAction() {

	}

	public function atomAction() {
		// get the default_context_path 
		$default_context_path = $this->_request->getParam('default_context_path');
		$item_label = $this->_request->getParam('item_label');
		
		// handle root level items
		if (!$default_context_path) {
			$path_query = "default_context_path:ROOT + item_label:" . $item_label;
		} else {
			$path_query = "default_context_path:\"" . $default_context_path . "/\" + item_label:\"" . $item_label . "\"";
		}
		// Connection to solr server
		$solr = new Apache_Solr_Service('localhost', 8180, '/solr');

		// test the connection to the solr server
		if ($solr->ping()) {
			try {
				$response = $solr->search($path_query, 0, 1, array (/* you can include other parameters here */));

				foreach (($response->response->docs) as $doc) {

				$atom = simplexml_load_string($doc->atom_full);
				if ($atom) {
					$this->view->atom = $atom;
				} else {
				
				$this->view->atom = null;
				
				
				}
				
				}
			} catch (Exception $e) {
				echo $e->getMessage(), "\n";

			}

		} else {
			//die("unable to connect to the solr server. exiting...");
		}

	}
	
	public function viewAction() {
		
		// get the context path and item lable from the uri
		$default_context_path =  $this->_request->getParam('default_context_path');
		$item_label = $this->_request->getParam('item_label');
		
		// handle root level items
		if (!$default_context_path) {
			$path_query = "default_context_path:ROOT + item_label:" . $item_label;
			
		} else {
		
			$path_query = "default_context_path:\"" . $default_context_path . "/\" + item_label:\"" . $item_label . "\"";
			//default_context_path:"Turkey/Domuztepe/II/Lot 705/" + item_label:"DT 0001"
		}
		
		// Connection to solr server
		$solr = new Apache_Solr_Service('localhost', 8180, '/solr');

		// test the connection to the solr server
		if ($solr->ping()) {
			try {
				$response = $solr->search($path_query, 0, 1, array (/* you can include other parameters here */));

				foreach (($response->response->docs) as $doc) {

				$atom = simplexml_load_string($doc->atom_full);
				   
				if ($atom) {
					$this->view->atom = $atom;
				} else {
				
				$this->view->atom = null;
				
				
				}
				
				}
			} catch (Exception $e) {
				echo $e->getMessage(), "\n";

			}

		} else {
			//die("unable to connect to the solr server. exiting...");
		}
		
	
		
	}
	


}