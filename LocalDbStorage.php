<?php

namespace hscstudio\cart;

class LocalDbStorage extends Storage
{	
	protected $db;
	
	protected $user;
	
	private $data;
	
	private $file;
	
	public function init()
	{
		$this->db = \Yii::$app->db;
		$this->user = \Yii::$app->user;
		
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
		if ($this->user->isGuest){
			if ($this->has($cart->id))
				$this->unserialize($this->get($cart->id),$cart);
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
				if ($this->has($cart->id))
					$this->unserialize($this->get($cart->id),$cart);
			}
		}
	}
	
	public function write(Cart $cart)
	{
		if ($this->user->isGuest){
			$this->set($cart->id,$this->serialize($cart));		
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
			$this->set($cart->id,$this->serialize($cart));
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