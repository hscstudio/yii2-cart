<?php

namespace hscstudio\cart;

abstract class Storage extends \yii\base\Object
{
	abstract public function read(Cart $cart);
	
	abstract public function write(Cart $cart);
	
	abstract public function lock($drop, Cart $cart);
	
	/**
     * Sets cart from serialized string
     * @param string $serialized
     */
    public function unserialize($serialized, Cart $cart)
    {
        $cart->items = unserialize($serialized);
    }
	
	/**
     * Returns items as serialized items
     * @return string
     */
    public function serialize(Cart $cart)
    {
        return serialize($cart->items);
    }
}