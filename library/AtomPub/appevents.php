<?php

require_once("httpresource.php");

class EventHTTPResource extends HTTPResource{

	private $listener_list = array();

	public function addEventListener($eventname, $object, $method_name) {
		if ( !array_key_exists($eventname, $this->listener_list) ) {
			$this->listener_list[$eventname] = array();
		}
		
		array_push($this->listener_list[$eventname], array(
				"name" => $eventname,
				"object" => $object,
				"method" => $method_name
			)
		);

	}
	public function dispatchEvent($event) {
		if (!array_key_exists($event->name, $this->listener_list)) {
			return;
		}
		foreach ( $this->listener_list[$event->name] as $listener ) {
			$o = $listener["object"];
			$method = $listener["method"];
			
			$o->$method($event);
		}
	}
}

class Event {
	public $name;
	
	public function __construct($name) {
		$this->name = $name;
	}
}

class HTTPEvent extends Event {
	public $response;
	public $request;
	
	public function __construct($name, $request, $response) {
		parent::__construct($name);
		
		$this->request = $request;
		$this->response = $response;
	}
}

class APPEvent extends Event{
	public $entry;
	
	public function __construct($name, $entry) {
		parent::__construct($name);
		
		$this->entry = $entry;
	}
}

?>