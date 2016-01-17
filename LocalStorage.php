<?php
/**
 * @link https://www.github.com/hscstudio/yii2-cart
 * @copyright Copyright (c) 2016 HafidMukhlasin.com
 * @license http://www.yiiframework.com/license/
 */
 
namespace hscstudio\cart;

/**
 * LocalStorage is extended from Storage Class
 * 
 * It's specialty for handling read and write into HTML5 LocalStorage
 *
 * Usage:
 * Configuration in block component look like this
 *		'cart' => [
 *			'class' => 'hscstudio\cart\Cart',
 *			'storage' => [
 *				'class' => 'hscstudio\cart\LocalStorage',
 *			]
 *		],
 *
 * @author Hafid Mukhlasin <hafidmukhlasin@gmail.com>
 * @since 1.0
 *
 * Inspired from https://github.com/kajyr/LocalStorage 
 *
*/

class LocalStorage extends Storage
{
	private $data;
	private $file;

	public function init() {
		parent::init();
		$session = \Yii::$app->session;
		$file = $session->has('localStorageFile')?$session->get('localStorageFile'):$session->getId();
		$this->file = ".json";
		if (file_exists($this->file)) {
			$this->data = json_decode(file_get_contents($this->file));
		} else {
			$this->data = new \stdClass();
		}
	}

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