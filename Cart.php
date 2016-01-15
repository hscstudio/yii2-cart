<?php

namespace hscstudio\cart;

use Yii;
use yii\base\Component;
use yii\base\Event;
use yii\di\Instance;
use yii\web\Session;
use yii\web\Cookie;


/**
 * Class Cart
 * @property CartPositionInterface[] $positions
 * @property int $count Total count of positions in the cart
 * @property int $cost Total cost of positions in the cart
 * @property bool $isEmpty Returns true if cart is empty
 * @property string $hash Returns hash (md5) of the current cart, that is uniq to the current combination
 * of positions, quantities and costs
 * @property string $serialized Get/set serialized content of the cart
 * @package \hscstudio\cart
 */
class Cart extends Component
{
    /** Triggered on position put */
    const EVENT_POSITION_PUT = 'putPosition';
    /** Triggered on position update */
    const EVENT_POSITION_UPDATE = 'updatePosition';
    /** Triggered on after position remove */
    const EVENT_BEFORE_POSITION_REMOVE = 'removePosition';
    /** Triggered on any cart change: add, update, delete position */
    const EVENT_CART_CHANGE = 'cartChange';
    /** Triggered on after cart cost calculation */
    const EVENT_COST_CALCULATION = 'costCalculation';

    /**
     * session (default) cart will be automatically stored in and loaded from session.
     * cookie cart will be automatically stored in and loaded from cookie.
	 * database cart will be automatically stored in and loaded from database.
	 * cookie_database cart will be automatically stored in and loaded from cookie (if guest) or database (if user).
     * @var string
     */
    public $storeIn = 'session';
    /**
     * Data component
     * @var string|data
     */
    protected $data = 'data';
	/**
     * Table name for db
     * @var string|table
     */
    public $table = 'cart';
    /**
     * Shopping cart ID to support multiple carts
     * @var string
     */
    public $cartId = __CLASS__;
    /**
     * @var CartPositionInterface[]
     */
    protected $_positions = [];

    public function init()
    {
        $this->loadData();
    }

    /**
     * Loads cart from data
     */
    public function loadData()
    {
		if($this->storeIn=='cookie'){
			$this->data = Yii::$app->request->cookies;
			if (isset($this->data[$this->cartId]))
				$this->setSerialized($this->data[$this->cartId]);
		}
		else if($this->storeIn=='database'){
			$db = Yii::$app->db;
			$session = Yii::$app->session;
			$cartId = str_replace('\\','',$this->cartId);
			if (Yii::$app->user->isGuest){
				$this->data = $db->createCommand("
						SELECT * FROM ".$this->table." 
						WHERE 
							id = '".$session->getId()."' and 
							name='".$cartId."' and 
							status=0 
						LIMIT 1	
					")    
					->queryOne();
				if($this->data){
					$this->setSerialized($this->data['value']);
				}
			}
			else{
				$user_id = Yii::$app->user->id;
				$this->data = $db->createCommand("
						SELECT * FROM ".$this->table." 
						WHERE 
							(user_id=".$user_id." or id = '".$session->getId()."') and 
							name='".$cartId."' and 
							status=0 
						LIMIT 1	
					")    
					->queryOne();
				if($this->data){
					$this->setSerialized($this->data['value']);
				}
			}
		}
		else if($this->storeIn=='cookie_database'){
			if (Yii::$app->user->isGuest){
				$this->data = Yii::$app->request->cookies;
				if (isset($this->data[$this->cartId]))
					$this->setSerialized($this->data[$this->cartId]);
			}
			else{
				$db = Yii::$app->db;
				$session = Yii::$app->session;
				$cartId = str_replace('\\','',$this->cartId);
				$user_id = Yii::$app->user->id;
				$this->data = $db->createCommand("
						SELECT * FROM ".$this->table." 
						WHERE 
							(user_id=".$user_id." or id = '".$session->getId()."') and 
							name='".$cartId."' and 
							status=0 
						LIMIT 1	
					")    
					->queryOne();
				if($this->data){
					$this->setSerialized($this->data['value']);
				}
				else{
					$this->data = Yii::$app->request->cookies;
					if (isset($this->data[$this->cartId]))
						$this->setSerialized($this->data[$this->cartId]);
					
				}				
			}
		}
		else{
			$this->data = Yii::$app->session;
			if (isset($this->data[$this->cartId]))
				$this->setSerialized($this->data[$this->cartId]);
		}
        
    }

    /**
     * Saves cart to the data
     */
    public function saveData()
    {
		if($this->storeIn=='cookie'){
			$this->data = Yii::$app->response->cookies;			
			$this->data->add(new \yii\web\Cookie([    
				'name' => $this->cartId,    
				'value' => $this->getSerialized(),
			]));
		}
		else if($this->storeIn=='database'){
			$db = Yii::$app->db;
			$session = Yii::$app->session;
			$cartId = str_replace('\\','',$this->cartId);
			//$session->destroy();			
			if (Yii::$app->user->isGuest){
				$data = $db->createCommand("
						SELECT * FROM ".$this->table." 
						WHERE 
							'".$session->getId()."' and
							name='".$cartId."' and 
							status=0 
						LIMIT 1	
					")           
					->queryOne();
				if($data){
					$db->createCommand()->update($this->table, [  
						'value' => $this->getSerialized(),
					], "
						id = '".$session->getId()."' and 
						name='".$cartId."' and 
						status=0 
					")->execute();
				}
				else{					
					$db->createCommand()->insert($this->table, [ 
						'id' => $session->getId(),
						'name' => $cartId,    
						'value' => $this->getSerialized(),
						'status' => 0
					])->execute();
				}
			}
			else{
				$user_id = Yii::$app->user->id;
				$data = $db->createCommand("
						SELECT * FROM ".$this->table." 
						WHERE 
							(user_id=".$user_id." or id = '".$session->getId()."') and
							name='".$cartId."' and 
							status=0 
						LIMIT 1	
					")           
					->queryOne();
				if($data){
					$db->createCommand()->update($this->table, [    
							'value' => $this->getSerialized(),
						], " 
							user_id=".$user_id." and 
							name='".$cartId."' and 
							status=0  ")
						->execute();
				}
				else{
					$db->createCommand()->insert($this->table, [ 
						'id' => $session->getId(),
						'user_id' => $user_id, 
						'name' => $cartId,       
						'value' => $this->getSerialized(),
						'status' => 0
					])->execute();
				}
			}
		}
		else if($this->storeIn=='cookie_database'){
			if (Yii::$app->user->isGuest){
				$this->data = Yii::$app->response->cookies;			
				$this->data->add(new \yii\web\Cookie([    
					'name' => $this->cartId,    
					'value' => $this->getSerialized(),
				]));
			}
			else{
				$db = Yii::$app->db;
				$session = Yii::$app->session;
				$cartId = str_replace('\\','',$this->cartId);
				$user_id = Yii::$app->user->id;
				$data = $db->createCommand("
						SELECT * FROM ".$this->table." 
						WHERE 
							(user_id=".$user_id." or id = '".$session->getId()."') and
							name='".$cartId."' and 
							status=0 
						LIMIT 1	
					")           
					->queryOne();
				if($data){
					$db->createCommand()->update($this->table, [    
							'value' => $this->getSerialized(),
						], " 
							user_id=".$user_id." and 
							name='".$cartId."' and 
							status=0  ")
						->execute();
				}
				else{
					$db->createCommand()->insert($this->table, [ 
						'id' => $session->getId(),
						'user_id' => $user_id, 
						'name' => $cartId,       
						'value' => $this->getSerialized(),
						'status' => 0
					])->execute();
				}
			}
		}
		else{
			$this->data = Yii::$app->session;
			$this->data[$this->cartId] = $this->getSerialized();
		}
    }
	
	/**
     * Checkout cart
     */
    public function checkOut()
	{		
		if(in_array($this->storeIn,['database','cookie_database'])){
			$db = Yii::$app->db;
			$session = Yii::$app->session;
			$cartId = str_replace('\\','',$this->cartId);
			if (Yii::$app->user->isGuest){
				$data = $db->createCommand("
						SELECT * FROM ".$this->table." 
						WHERE 
							(id = '".$session->getId()."') and
							name='".$cartId."' and 
							status=0 
						LIMIT 1	
					")           
					->queryOne();
			}
			else{
				$user_id = Yii::$app->user->id;
				$data = $db->createCommand("
						SELECT * FROM ".$this->table." 
						WHERE 
							(user_id=".$user_id." or id = '".$session->getId()."') and
							name='".$cartId."' and 
							status=0 
						LIMIT 1	
					")           
					->queryOne();
			}
			
			if($data){
				if($drop){
					$db->createCommand()->delete($this->table, " 
							user_id=".$user_id." and 
							name='".$cartId."' and 
							status=0  ")
						->execute();
				}
				else{
					$db->createCommand()->update($this->table, [    
							'status' => 1,
						], " 
							user_id=".$user_id." and 
							name='".$cartId."' and 
							status=0  ")
						->execute();
					$session->regenerateID(true);
				}
				
			}
		}
	}
	
    /**
     * Sets cart from serialized string
     * @param string $serialized
     */
    public function setSerialized($serialized)
    {
        $this->_positions = unserialize($serialized);
    }

    /**
     * @param CartPositionInterface $position
     * @param int $quantity
     */
    public function put($position, $quantity = 1)
    {
        if (isset($this->_positions[$position->getId()])) {
            $this->_positions[$position->getId()]->setQuantity(
                $this->_positions[$position->getId()]->getQuantity() + $quantity);
        } else {
            $position->setQuantity($quantity);
            $this->_positions[$position->getId()] = $position;
        }
        $this->trigger(self::EVENT_POSITION_PUT, new CartActionEvent([
            'action' => CartActionEvent::ACTION_POSITION_PUT,
            'position' => $this->_positions[$position->getId()],
        ]));
        $this->trigger(self::EVENT_CART_CHANGE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_POSITION_PUT,
            'position' => $this->_positions[$position->getId()],
        ]));
        $this->saveData();
    }

    /**
     * Returns cart positions as serialized items
     * @return string
     */
    public function getSerialized()
    {
        return serialize($this->_positions);
    }

    /**
     * @param CartPositionInterface $position
     * @param int $quantity
     */
    public function update($position, $quantity)
    {
        if ($quantity <= 0) {
            $this->remove($position);
            return;
        }

        if (isset($this->_positions[$position->getId()])) {
            $this->_positions[$position->getId()]->setQuantity($quantity);
        } else {
            $position->setQuantity($quantity);
            $this->_positions[$position->getId()] = $position;
        }
        $this->trigger(self::EVENT_POSITION_UPDATE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_UPDATE,
            'position' => $this->_positions[$position->getId()],
        ]));
        $this->trigger(self::EVENT_CART_CHANGE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_UPDATE,
            'position' => $this->_positions[$position->getId()],
        ]));
        $this->saveData();
    }

    /**
     * Removes position from the cart
     * @param CartPositionInterface $position
     */
    public function remove($position)
    {
        $this->removeById($position->getId());
    }

    /**
     * Removes position from the cart by ID
     * @param string $id
     */
    public function removeById($id)
    {
        $this->trigger(self::EVENT_BEFORE_POSITION_REMOVE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_BEFORE_REMOVE,
            'position' => $this->_positions[$id],
        ]));
        $this->trigger(self::EVENT_CART_CHANGE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_BEFORE_REMOVE,
            'position' => $this->_positions[$id],
        ]));
        unset($this->_positions[$id]);
        $this->saveData();
    }

    /**
     * Remove all positions
     */
    public function removeAll($delete=true)
    {
        $this->_positions = [];
        $this->trigger(self::EVENT_CART_CHANGE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_REMOVE_ALL,
        ]));
		if($delete){
			$this->saveData();			
		}
		else{	
			$this->checkOut();
		}
    }

    /**
     * Returns position by it's id. Null is returned if position was not found
     * @param string $id
     * @return CartPositionInterface|null
     */
    public function getPositionById($id)
    {
        if ($this->hasPosition($id))
            return $this->_positions[$id];
        else
            return null;
    }

    /**
     * Checks whether cart position exists or not
     * @param string $id
     * @return bool
     */
    public function hasPosition($id)
    {
        return isset($this->_positions[$id]);
    }

    /**
     * @return CartPositionInterface[]
     */
    public function getPositions()
    {
        return $this->_positions;
    }

    /**
     * @param CartPositionInterface[] $positions
     */
    public function setPositions($positions)
    {
        $this->_positions = array_filter($positions, function (CartPositionInterface $position) {
            return $position->quantity > 0;
        });
        $this->trigger(self::EVENT_CART_CHANGE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_SET_POSITIONS,
        ]));
        $this->saveData();
    }

    /**
     * Returns true if cart is empty
     * @return bool
     */
    public function getIsEmpty()
    {
        return count($this->_positions) == 0;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        $count = 0;
        foreach ($this->_positions as $position)
            $count += $position->getQuantity();
        return $count;
    }

    /**
     * Return full cart cost as a sum of the individual positions costs
     * @param $withDiscount
     * @return int
     */
    public function getCost($withDiscount = false)
    {
        $cost = 0;
        foreach ($this->_positions as $position) {
            $cost += $position->getCost($withDiscount);
        }
        $costEvent = new CostCalculationEvent([
            'baseCost' => $cost,
        ]);
        $this->trigger(self::EVENT_COST_CALCULATION, $costEvent);
        if ($withDiscount)
            $cost = max(0, $cost - $costEvent->discountValue);
        return $cost;
    }

    /**
     * Returns hash (md5) of the current cart, that is unique to the current combination
     * of positions, quantities and costs. This helps us fast compare if two carts are the same, or not, also
     * we can detect if cart is changed (comparing hash to the one's saved somewhere)
     * @return string
     */
    public function getHash()
    {
        $data = [];
        foreach ($this->positions as $position) {
            $data[] = [$position->getId(), $position->getQuantity(), $position->getWeight(), $position->getPrice()];
        }
        return md5(serialize($data));
    }


}
