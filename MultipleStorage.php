<?php
/**
 * @link https://www.github.com/hscstudio/yii2-cart
 * @copyright Copyright (c) 2016 HafidMukhlasin.com
 * @license http://www.yiiframework.com/license/
 */
 
namespace hscstudio\cart;

use yii\di\Instance;

/**
 * MultipleStorage is extended from Storage Class
 * 
 * It's specialty for handling Multiple Storage 
 *
 * Usage:
 * Configuration in block component look like this
 *		'cart' => [
 *			'class' => 'hscstudio\cart\Cart',
 *			'storage' => [
 *				'class' => 'hscstudio\cart\MultipleStorage',
 *				'storages' => [
 *					['class' => 'hscstudio\cart\SessionStorage'],
 *					[
 *						'class' => 'hscstudio\cart\DatabaseStorage',
 *						'table' => 'cart',
 *					],
 *				],
 *			]
 *		],
 *
 * @author Hafid Mukhlasin <hafidmukhlasin@gmail.com>
 * @since 1.0
 *
 * Inspired from https://github.com/kajyr/LocalStorage 
 *
*/

class MultipleStorage extends Storage
{
	/**
	 * Array $storage
	 */
	public $storages = [];

	/**
	 *
	 */
	public function init()
	{
		parent::init();
		if (empty($this->storages)) {
			$this->storages = [
				['class' => SessionStorage::class],
				['class' => DatabaseStorage::class],
			];
		}
		
		$this->storages = array_map(function ($storage) {
			return Instance::ensure($storage, Storage::class);
		}, $this->storages);
	}

	/**
	 * @param Cart $cart
	 */
	public function sync(Cart $cart) {
		$this->storages[0]->read($cart);
		$last_cart = clone $cart;
		$this->storages[0]->lock(true, $cart);
		$cart = $last_cart;
		$this->storages[1]->read($cart);
		$this->storages[1]->write($cart);

		/*$this->storages[1]->read($cart);
		$current_cart = clone $cart;

		$this->storages[0]->read($cart);
		$this->storages[0]->lock(true, $cart);
		echo "<h1>Item Storage 2</h1>";
		var_dump($current_cart->items);
		echo "<hr>";
		echo "<h1>Item Storage 1</h1>";
		var_dump($cart->items);
		//
		$cart->items = array_merge($current_cart->items, $cart->items);
		echo "<hr>";
		echo "<h1>Item After Array Merge</h1>";
		var_dump($cart->items);
		$this->storages[1]->write($cart);
		*/
	}

	/**
	 * @return mixed
	 */
	public function chooseStorage()
	{
		return \Yii::$app->user->isGuest ? $this->storages[0] : $this->storages[1];
	}

	/**
	 * @param Cart $cart
	 */
	public function read(Cart $cart)
	{
		$this->chooseStorage()->read($cart);
	}

	/**
	 * @param Cart $cart
	 */
	public function write(Cart $cart)
	{
		$this->chooseStorage()->write($cart);
	}

	/**
	 * @param $drop
	 * @param Cart $cart
	 */
	public function lock($drop, Cart $cart)
	{
		$this->chooseStorage()->lock($drop, $cart);
	}
}