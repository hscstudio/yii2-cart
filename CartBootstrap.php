<?php
/**
 * @link https://www.github.com/hscstudio/yii2-cart
 * @copyright Copyright (c) 2016 HafidMukhlasin.com
 * @license http://www.yiiframework.com/license/
 */

namespace hscstudio\cart;

use Yii;
use yii\base\BootstrapInterface;
use yii\di\Instance;
use yii\web\User;
use yii\base\Event;

/**
 * Bootstrap class for checking sync between two storages.
 *
 * @author Hafid Mukhlasin <hafidmukhlasin@gmail.com>
 * @since 1.0
 *
 */
class CartBootstrap implements BootstrapInterface
{
	/**
	 * @param \yii\base\Application $app
	 */
	public function bootstrap($app)
	{
		Event::on(User::className(), User::EVENT_AFTER_LOGIN, function () {
			$storage = Instance::ensure(\Yii::$app->cart->storage, MultipleStorage::className());
			if (get_class($storage) == 'hscstudio\cart\MultipleStorage') {
				$cart = Instance::ensure(\Yii::$app->cart, Cart::className());
				$storage->sync($cart);
			}
		});
	}
}