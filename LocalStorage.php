<?php

namespace hscstudio\cart;

/* 
Inspired from
https://github.com/kajyr/LocalStorage 
*/

class LocalStorage
{
	private $data;
	private $file;
	
	function __construct() {
		$this->file = "localconfig.json";
		if (file_exists($this->file)) {
			$this->data = json_decode(file_get_contents($this->file));
		} else {
			$this->data = new \stdClass();
		}
	}
	
	public function get($key) {
		if (isset($this->data->$key)) {
			return $this->data->$key;
		} else {
			return false;
		}
	}
	
	public function set($key, $value) {
		$this->data->$key = $value;
		file_put_contents($this->file, json_encode($this->data));
		return $value;
	}
	
	public function has($key) {
		return (isset($this->data->$key));
	}
}