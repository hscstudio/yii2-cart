Shopping cart for Yii 2
=======================

This extension is improvisation of omnilight/yii2-shopping-cart. It's add shopping cart systems for Yii framework 2.0. 
It have feature for save to some medium, they are session (default), cookie, localStorage, database, and multiple storage. 

What's is the meaning of the multiple storage? 
It's feature that can handle two storage where it will save cart data to storage 1 if user is guest, and save to storage 2 if user is logged user. 

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist hscstudio/yii2-cart "*"
```

or add

```json
"hscstudio/yii2-cart": "*"
```

to the `require` section of your composer.json.

If You plan to save cart data into database, so You should create table cart.
```
CREATE TABLE `cart` (
  `id` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);
```

or use migration

```
yii migrate --migrationPath=@hscstudio/cart/migrations
```

How to use
----------

In your model:
```php
class Product extends ActiveRecord implements ItemInterface
{
    use ItemTrait;

    public function getPrice()
    {
        return $this->price;
    }

    public function getId()
    {
        return $this->id;
    }
}
```

In your controller:
```php
	public function actionCreate($id)
    {
        $product = Product::findOne($id);
        if ($product) {
            \Yii::$app->cart->create($product);
            $this->redirect(['index']);
        }
    }
    public function actionIndex()
    {
        $cart = \Yii::$app->cart;
        $products = $cart->getItems();
        $total = $cart->getCost();
        return $this->render('index', [
            'products' => $products,
            'total' => $total,
        ]);
    }
    public function actionDelete($id)
    {
        $product = Product::findOne($id);
        if ($product) {
            \Yii::$app->cart->delete($product);
            $this->redirect(['index']);
        }
    }
    public function actionUpdate($id, $quantity)
    {
        $product = Product::findOne($id);
        if ($product) {
            \Yii::$app->cart->update($product, $quantity);
            $this->redirect(['index']);
        }
    }
	
	public function actionCheckout(){
		\Yii::$app->cart->checkOut(false);
		$this->redirect(['index']);
	}
```

Also you can use cart as global application component:

```php
[
    'components' => [
        'cart' => [
			'class' => 'hscstudio\cart\Cart',
		],
    ]
]
```

Possible values of storage are 
- hscstudio\cart\CookieStorage
- hscstudio\cart\SessionStorage
- hscstudio\cart\LocalStorage
- hscstudio\cart\DatabaseStorage
- hscstudio\cart\MultipleStorage

Example configuration for MultipleStorage.

```php
[
    'components' => [
        'cart' => [
			'class' => 'hscstudio\cart\Cart',
			'storage' => [
				'class' => 'hscstudio\cart\MultipleStorage',
				'storages' => [
					['class' => 'hscstudio\cart\SessionStorage'],
					[
						'class' => 'hscstudio\cart\DatabaseStorage',
						'table' => 'cart',
					],
				],
			]
		],
    ]
]
```

If You use Multiple Storage, so You should add bootstrap in configuration file:

```php
    'bootstrap' => [
		...
		'hscstudio\cart\CartBootstrap'
	],
```

Or You can create and use Your own storageClass, it's should extends abstract class of hscstudio\cart\Storage.
It is look like :
```
<?php

namespace app\foo;

use hscstudio\cart\Storage;

class ExampleStorage extends Storage
{
	public function read(Cart $cart)
	{
		// read cart data
	}
	
	public function write(Cart $cart)
	{
		// write cart data
	}
	
	public function lock($drop, Cart $cart)
	{
		// lock cart data, only for db
	}
}
```

And use it in the following way:

```php
\Yii::$app->cart->create($product, 1);
```

In order to get number of items in the cart:

```php
$itemsCount = \Yii::$app->cart->getCount();
```

In order to get total cost of items in the cart:

```php
$total = \Yii::$app->cart->getCost();
```

If user have finished, and do checkout, so wen use following code

```php
\Yii::$app->cart->removeAll(); // will remove data
// or 
\Yii::$app->cart->checkOut(); // will remove data
// or
\Yii::$app->cart->checkOut(false); // will keep data, only update status to 1 and regenerate session ID
```

Using discounts
---------------

Discounts are implemented as behaviors that could attached to the cart or it's items. To use them, follow this steps:

1. Define discount class as a subclass of hscstudio\cart\DiscountBehavior
```php
// app/components/MyDiscount.php

class MyDiscount extends DiscountBehavior
{
    /**
     * @param CostCalculationEvent $event
     */
    public function onCostCalculation($event)
    {
        // Some discount logic, for example
        $event->discountValue = 100;
    }
}
```

2. Add this behavior to the cart:

```php
$cart->attachBehavior('myDiscount', ['class' => 'app\components\MyDiscount']);
```

If discount is suitable not for the whole cart, but for the individual item, than it is possible to attach
discount to the cart position itself:

```
$cart->getItemById($itemId)->attachBehavior('myDiscount', ['class' => 'app\components\MyDiscount']);
```

Note, that the same behavior could be used for both cart and item classes.

3. To get total cost with discount applied:

```php
$total = \Yii::$app->cart->getCost(true);
```

4. During the calculation the following events are triggered: 
- `Cart::EVENT_COST_CALCULATION` once per calculation.
- `ItemInterface::EVENT_COST_CALCULATION` for each item in the cart.
 
You can also subscribe on this events to perform discount calculation:

```php
$cart->on(Cart::EVENT_COST_CALCULATION, function ($event) {
    $event->discountValue = 100;
});
```
