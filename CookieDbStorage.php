<?php

namespace hscstudio\cart;

class CookieDbStorage extends Storage
{
	protected $db;
	
	protected $user;
	
	public function init()
	{
		$this->db = \Yii::$app->db;
		$this->user = \Yii::$app->user;
	}
	
	public function read(Cart $cart)
	{							
		if ($this->user->isGuest){
			$cookie = \Yii::$app->request->cookies;
			if (isset($cookie[$cart->id]))
				$this->unserialize($cookie[$cart->id], $cart);
		}
		else{
			$exe = $this->db->createCommand("
					SELECT * FROM ".$cart->table." 
					WHERE 
						(user_id=".$this->user->id." or id = '".$cart->sessionId."') and 
						name='".$cart->id."' and 
						status=0 
					LIMIT 1	
				")    
				->queryOne();
			if($exe){
				$this->unserialize($exe['value'],$cart);
			}
			else{
				$cookie = \Yii::$app->request->cookies;
				if (isset($cookie[$cart->id]))
					$this->unserialize($cookie[$cart->id], $cart);				
			}	
		}
	}
	
	public function write(Cart $cart)
	{
		if ($this->user->isGuest){
			$cookies = \Yii::$app->response->cookies;
			$cookies->add(new \yii\web\Cookie([    
				'name' => $cart->id,    
				'value' => $this->serialize($cart),
			]));
		}
		else{
			$data = $this->db->createCommand("
					SELECT * FROM ".$cart->table." 
					WHERE 
						(user_id=".$this->user->id." or id = '".$cart->sessionId."') and
						name='".$cart->id."' and 
						status=0 
					LIMIT 1	
				")           
				->queryOne();
			if($data){
				$this->db->createCommand()->update($cart->table, [    
						'value' => $this->serialize($cart),
					], " 
						user_id=".$this->user->id." and 
						name='".$cart->id."' and 
						status=0  ")
					->execute();
			}
			else{
				$this->db->createCommand()->insert($cart->table, [ 
					'id' => $cart->sessionId,
					'user_id' => $this->user->id, 
					'name' => $cart->id,       
					'value' => $this->serialize($cart),
					'status' => 0
				])->execute();
			}
		}		
	}
	
	public function lock($drop, Cart $cart)
	{
		if ($this->user->isGuest){
			$cart->items = [];
			$cookies = \Yii::$app->response->cookies;
			$cookies->add(new \yii\web\Cookie([    
				'name' => $cart->id,    
				'value' => $this->serialize($cart),
			]));
		}
		else{
			$data = $this->db->createCommand("
					SELECT * FROM ".$cart->table." 
					WHERE 
						(user_id=".$this->user->id." or id = '".$cart->sessionId."') and
						name='".$cart->id."' and 
						status=0 
					LIMIT 1	
				")           
				->queryOne();
				
			if($data){
				if($drop){
					$this->db->createCommand()->delete($cart->table, " 
							user_id=".$this->user->id." and 
							name='".$cart->id."' and 
							status=0  ")
						->execute();
				}
				else{
					$this->db->createCommand()->update($cart->table, [    
							'status' => 1,
						], " 
							user_id=".$this->user->id." and 
							name='".$cart->id."' and 
							status=0  ")
						->execute();
					Yii::$app->session->regenerateID(true);
				}
				
			}	
		}
	}
}