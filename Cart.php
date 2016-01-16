<?php

namespace hscstudio\cart;

use Yii;
use yii\base\Component;
use yii\base\Event;
use yii\di\Instance;
use yii\web\Session;
use yii\web\Cookie;
use hscstudio\cart\LocalStorage;


/**
 * Class Cart
 * @property ItemInterface[] $items
 * @property int $count Total count of items in the cart
 * @property int $cost Total cost of items in the cart
 * @property bool $isEmpty Returns true if cart is empty
 * @property string $hash Returns hash (md5) of the current cart, that is uniq to the current combination
 * of items, quantities and costs
 * @property string $serialized Get/set serialized content of the cart
 * @package \hscstudio\cart
 */
class Cart extends Component
{
    /** Triggered on item put */
    const EVENT_ITEM_PUT = 'putItem';
    /** Triggered on item update */
    const EVENT_ITEM_UPDATE = 'updateItem';
    /** Triggered on after item remove */
    const EVENT_BEFORE_ITEM_REMOVE = 'removeItem';
    /** Triggered on any cart change: add, update, delete item */
    const EVENT_CART_CHANGE = 'cartChange';
    /** Triggered on after cart cost calculation */
    const EVENT_COST_CALCULATION = 'costCalculation';

    /**
     * session (default) cart will be automatically stored in and loaded from session.
     * cookie cart will be automatically stored in and loaded from cookie.
     * localStorage cart will be automatically stored in and loaded from localStorage.
	 * database cart will be automatically stored in and loaded from database.
	 * cookieDatabase cart will be automatically stored in and loaded from cookie (if guest) or database (if user).
	 * sessionDatabase cart will be automatically stored in and loaded from session (if guest) or database (if user).
     * @var string
     */
    //public $storeIn = 'session';
	
    public $storageClass = 'hscstudio\cart\SessionStorage';
	
	/**
     * @var storage
     */
    protected $storage;
    /**
     * Data component
     * @var string|data
     */
    //protected $data = 'data';
	/**
     * Table name for db
     * @var string|table
     */
    public $table = 'cart';
    /**
     * Shopping ID to support multiple carts
     * @var string
     */
    public $id = 'cart1';
    /**
     * @var ItemInterface[]
     */
    public $items = [];
	/**
     * @var sessionId
     */
	public $sessionId;

    public function init()
    {
        $this->sessionId = Yii::$app->session->getId();
		$this->storage = new $this->storageClass();		
		$this->load();
    }

    /**
     * Loads cart from data
     */
    public function load()
    {
		$this->storage->read($this);	
	}

    /**
     * Saves cart to the data
     */
    public function save()
    {
		$this->storage->write($this);
    }
	
	/**
     * Checkout cart
     */
    public function checkOut($drop)
	{		
		if(!empty($this->storage->db)){
			$this->storage->lock($drop, $this);	
		}
		else{
			$this->deleteAll();
		}		
		
	}
    
	/**
     * @param ItemInterface $item
     * @param int $quantity
     */
    public function create($item, $quantity = 1)
    {
        if (isset($this->items[$item->getId()])) {
            $this->items[$item->getId()]->setQuantity(
                $this->items[$item->getId()]->getQuantity() + $quantity);
        } else {
            $item->setQuantity($quantity);
            $this->items[$item->getId()] = $item;
        }
        $this->trigger(self::EVENT_ITEM_PUT, new CartActionEvent([
            'action' => CartActionEvent::ACTION_ITEM_PUT,
            'item' => $this->items[$item->getId()],
        ]));
        $this->trigger(self::EVENT_CART_CHANGE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_ITEM_PUT,
            'item' => $this->items[$item->getId()],
        ]));
        $this->save();
    }
	
    /**
     * @param ItemInterface $item
     * @param int $quantity
     */
    public function update($item, $quantity)
    {
        if ($quantity <= 0) {
            $this->delete($item);
            return;
        }

        if (isset($this->items[$item->getId()])) {
            $this->items[$item->getId()]->setQuantity($quantity);
        } else {
            $item->setQuantity($quantity);
            $this->items[$item->getId()] = $item;
        }
        $this->trigger(self::EVENT_ITEM_UPDATE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_UPDATE,
            'item' => $this->items[$item->getId()],
        ]));
        $this->trigger(self::EVENT_CART_CHANGE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_UPDATE,
            'item' => $this->items[$item->getId()],
        ]));
        $this->save();
    }

    /**
     * Delete item from the cart
     * @param ItemInterface $item
     */
    public function delete($item)
    {
        $this->deleteById($item->getId());
    }

    /**
     * Delete items from the cart by ID
     * @param string $id
     */
    public function deleteById($id)
    {
        $this->trigger(self::EVENT_BEFORE_ITEM_REMOVE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_BEFORE_REMOVE,
            'item' => $this->items[$id],
        ]));
        $this->trigger(self::EVENT_CART_CHANGE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_BEFORE_REMOVE,
            'item' => $this->items[$id],
        ]));
        unset($this->items[$id]);
        $this->save();
    }

    /**
     * Delete all items
     */
    public function deleteAll()
    {
        $this->items = [];
        $this->trigger(self::EVENT_CART_CHANGE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_REMOVE_ALL,
        ]));
		$this->save();
    }

    /**
     * Returns item by it's id. Null is returned if item was not found
     * @param string $id
     * @return ItemInterface|null
     */
    public function getItemById($id)
    {
        if ($this->hasItem($id))
            return $this->items[$id];
        else
            return null;
    }

    /**
     * Checks whether cart item exists or not
     * @param string $id
     * @return bool
     */
    public function hasItem($id)
    {
        return isset($this->items[$id]);
    }

    /**
     * @return ItemInterface[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param ItemInterface[] $items
     */
    public function setItems($items)
    {
        $this->items = array_filter($items, function (ItemInterface $item) {
            return $item->quantity > 0;
        });
        $this->trigger(self::EVENT_CART_CHANGE, new CartActionEvent([
            'action' => CartActionEvent::ACTION_SET_ITEMS,
        ]));
        $this->save();
    }

    /**
     * Returns true if cart is empty
     * @return bool
     */
    public function getIsEmpty()
    {
        return count($this->items) == 0;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        $count = 0;
        foreach ($this->items as $item)
            $count += $item->getQuantity();
        return $count;
    }

    /**
     * Return full cart cost as a sum of the individual items costs
     * @param $withDiscount
     * @return int
     */
    public function getCost($withDiscount = false)
    {
        $cost = 0;
        foreach ($this->items as $item) {
            $cost += $item->getCost($withDiscount);
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
     * of items, quantities and costs. This helps us fast compare if two carts are the same, or not, also
     * we can detect if cart is changed (comparing hash to the one's saved somewhere)
     * @return string
     */
    public function getHash()
    {
        $data = [];
        foreach ($this->items as $item) {
            $data[] = [$item->getId(), $item->getQuantity(), $item->getWeight(), $item->getPrice()];
        }
        return md5(serialize($data));
    }


}
