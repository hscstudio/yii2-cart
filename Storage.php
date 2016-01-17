<?php
/**
 * @link https://www.github.com/hscstudio/yii2-cart
 * @copyright Copyright (c) 2016 HafidMukhlasin.com
 * @license http://www.yiiframework.com/license/
 */
 
namespace hscstudio\cart;

/**
 * Abstract Class Storage
 *
 * It's basic class that should extended for create storage
 *
 * @author Hafid Mukhlasin <hafidmukhlasin@gmail.com>
 * @since 1.0
 *	
 * @property string $serialized Get/set serialized content of the cart
 */
 
abstract class Storage extends \yii\base\Object
{
	
	/**
	* Abstract function for read cart data from storage.
	* @param Cart $cart
	*/
	abstract public function read(Cart $cart);
	
	/**
	* Abstract function for write cart data from storage.
	* @param Cart $cart
	*/
	abstract public function write(Cart $cart);
	
	/**
	* Abstract function for lock cart data from storage.
	* @param Cart $cart
	*/
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