<?php

namespace hscstudio\cart;

class DbStorage extends Storage
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
			$exe = $this->db->createCommand("
					SELECT * FROM ".$cart->table." 
					WHERE 
						id = '".$cart->sessionId."' and 
						name='".$cart->id."' and 
						status=0 
					LIMIT 1	
				")    
				->queryOne();
			if($exe){
				$this->unserialize($exe['value'],$cart);
			}
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
		}
	}
	
	public function write(Cart $cart)
	{
		if ($this->user->isGuest){
			$data = $this->db->createCommand("
					SELECT * FROM ".$cart->table." 
					WHERE 
						'".$cart->sessionId."' and
						name='".$cart->id."' and 
						status=0 
					LIMIT 1	
				")           
				->queryOne();
			if($data){
				$this->db->createCommand()->update($cart->table, [  
					'value' => $this->serialize($cart),
				], "
					id = '".$cart->sessionId."' and 
					name='".$cart->id."' and 
					status=0 
				")->execute();
			}
			else{					
				$this->db->createCommand()->insert($cart->table, [ 
					'id' => $cart->sessionId,
					'name' => $cart->id,    
					'value' => $this->serialize($cart),
					'status' => 0
				])->execute();
			}
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
			$data = $this->db->createCommand("
					SELECT * FROM ".$cart->table." 
					WHERE 
						(id = '".$this->session->getId()."') and
						name='".$cart->id."' and 
						status=0 
					LIMIT 1	
				")           
				->queryOne();
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
		}
		
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