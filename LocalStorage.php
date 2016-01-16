<?php

namespace hscstudio\cart;

/* 
Inspired from
https://github.com/kajyr/LocalStorage 
*/

class LocalStorage extends Storage
{
	private $data;
	private $file;
	
	public function read(Cart $cart)
	{		
		if ($this->has($cart->id))
			$this->unserialize($this->get($cart->id),$cart);
	}
	
	public function write(Cart $cart)
	{
		$this->set($cart->id,$this->serialize($cart));			
	}
	
	public function lock($drop, Cart $cart)
	{
		// not implemented, only for db
	}
	
	public function init() {
		$session = \Yii::$app->session;
		$file = $session->has('localStorageFile')?$session->get('localStorageFile'):$session->getId();
		$this->file = ".json";
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