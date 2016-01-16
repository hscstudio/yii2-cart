<?php

namespace hscstudio\cart;

class CookieStorage extends Storage
{
	public function read(Cart $cart)
	{
		$cookies = \Yii::$app->request->cookies;
		if (isset($cookies[$cart->id]))
			$this->unserialize($cookies[$cart->id],$cart);
	}
	
	public function write(Cart $cart)
	{
		$cookies = \Yii::$app->response->cookies;
		$cookies->add(new \yii\web\Cookie([    
			'name' => $cart->id,    
			'value' => $this->serialize($cart),
		]));		
			
	}
	
	public function lock($drop, Cart $cart)
	{
		// not implemented, only for db
	}
}