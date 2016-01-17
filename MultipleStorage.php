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
	public $storages = [];

	public function init()
	{
		if (empty($this->storages)) {
			$this->storages = [
				['class' => SessionStorage::class],
				['class' => DbStorage::class],
			];
		}
		
		$this->storages = array_map(function ($storage) {
			return Instance::ensure($storage, Storage::class);
		}, $this->storages);
		
		$session = \Yii::$app->session;
		if(!$session->has('needSynchronize'))
			$session->set('needSynchronize',\Yii::$app->user->isGuest ? 1 : 0 );
	}

	public function chooseStorage()
	{
		return \Yii::$app->user->isGuest ? $this->storages[0] : $this->storages[1];
	}

	public function read(Cart $cart)
	{
		$this->chooseStorage()->read($cart);
		if($cart->getIsEmpty()){
			$session = \Yii::$app->session;
			if($session->get('needSynchronize')==1 and !\Yii::$app->user->isGuest){
				$this->storages[0]->read($cart);
				$obj = clone $cart;
				$this->storages[0]->lock(true, $cart);
				$cart = $obj;
				$this->storages[1]->write($cart);
				$session->set('needSynchronize',0);	
			}
			else{
				$this->chooseStorage()->read($cart);
			} 				
		}
		
	}

	public function write(Cart $cart)
	{
		$session = \Yii::$app->session;
		if($session->get('needSynchronize')==1 and !\Yii::$app->user->isGuest){
			$obj = clone $cart;
			$this->storages[0]->lock(true,$cart);
			$cart = $obj;
			$session->set('needSynchronize',0);			
		}
		$this->chooseStorage()->write($cart);
	}

	public function lock($drop, Cart $cart)
	{
		$this->chooseStorage()->lock($drop, $cart);
	}
}