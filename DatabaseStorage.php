<?php
/**
 * @link https://www.github.com/hscstudio/yii2-cart
 * @copyright Copyright (c) 2016 HafidMukhlasin.com
 * @license http://www.yiiframework.com/license/
 */
 
namespace hscstudio\cart;

/**
 * DatabaseStorage is extended from Storage Class
 * 
 * It's specialty for handling read and write cart data into database
 *
 * Usage:
 * Configuration in block component look like this
 *		'cart' => [
 *			'class' => 'hscstudio\cart\Cart',
 *			'storage' => [
 *				'class' => 'hscstudio\cart\DatabaseStorage',
 *				'table'	=> 'cart',
 *			]
 *		],
 *
 * @author Hafid Mukhlasin <hafidmukhlasin@gmail.com>
 * @since 1.0
 *
*/

class DatabaseStorage extends Storage
{
	protected $db;
	
	public $table = 'cart';
	
	public function init()
	{
		$this->db = \Yii::$app->db;
	}
	
	public function read(Cart $cart)
	{	
		if($data=$this->select($cart)){
			$this->unserialize($data['value'],$cart);
		}
	}
	
	public function write(Cart $cart)
	{
		if($this->select($cart)){
			$this->update($cart);
		}
		else{
			$this->insert($cart);
		}		
	}
	
	public function lock($drop, Cart $cart)
	{
		if($data=$this->select($cart)){
			if($drop){
				$qry = $this->query("delete", 
					"(user_id	= ".\Yii::$app->user->id." or id = '".\Yii::$app->session->getId()."') and 
					 name	 	= '".$cartId."' and 
					 status 		= 0");
			}
			else{
				$qry = $this->query("update", 
					"(user_id	= ".\Yii::$app->user->id." or id = '".\Yii::$app->session->getId()."') and 
					 name	 	= '".$cartId."' and 
					 status 		= 0",
					"status 	= 1");				
				$this->db->createCommand($qry)->execute();
				\Yii::$app->session->regenerateID(true);
			}
			$this->db->createCommand($qry)->execute();			
		}
	}
	
	public function select(Cart $cart){
		$qry = "SELECT * FROM  ".$this->table." WHERE 
					 (user_id	= ".\Yii::$app->user->id." or id = '".\Yii::$app->session->getId()."') and 
					  name	 	= '".$cart->id."' and 
					  status 	= 0";
		return $this->db->createCommand($qry)->queryOne();
	}
	
	public function insert(Cart $cart){
		$this->db->createCommand()->insert($this->table, [
				"id"		=>	\Yii::$app->session->getId(),
				"user_id"	=>	\Yii::$app->user->id,
				"name"		=> 	$cart->id,
				"value"		=>	$this->serialize($cart),
				"status"	=> 	0,
				])->execute();			
	}
	
	public function update(Cart $cart){				
		$this->db->createCommand()->update($this->table, [
					'value' => $this->serialize($cart)
				], 
				"(user_id	= ".\Yii::$app->user->id." or id = '".\Yii::$app->session->getId()."') and 
				  name		= '".$cart->id."' and 
				  status 	= 0")
				->execute();
	}
}