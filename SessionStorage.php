<?php

namespace hscstudio\cart;

class SessionStorage extends Storage
{
	public function read(Cart $cart)
	{
		$session = \Yii::$app->session;
		if (isset($session[$cart->id]))
			$this->unserialize($session[$cart->id],$cart);
	}
	
	public function write(Cart $cart)
	{
		$session = \Yii::$app->session;
		$session[$cart->id] = $this->serialize($cart);
	}
	
	public function lock($drop, Cart $cart)
	{
		// not implemented, only for db
	}
}