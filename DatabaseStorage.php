<?php
/**
 * @link https://www.github.com/hscstudio/yii2-cart
 * @copyright Copyright (c) 2016 HafidMukhlasin.com
 * @license http://www.yiiframework.com/license/
 */

namespace hscstudio\cart;

use Yii;
use yii\di\Instance;
use yii\db\Query;

/**
 * DatabaseStorage is extended from Storage Class
 *
 * It's specialty for handling read and write cart data into database
 *
 * Usage:
 * Configuration in block component look like this
 *        'cart' => [
 *            'class' => 'hscstudio\cart\Cart',
 *            'storage' => [
 *                'class' => 'hscstudio\cart\DatabaseStorage',
 *                'table'    => 'cart',
 *            ]
 *        ],
 *
 * @author Hafid Mukhlasin <hafidmukhlasin@gmail.com>
 * @since 1.0
 *
 */
class DatabaseStorage extends Storage
{
	public $db = 'db';

	public $table = 'cart';

	/**
	 *
	 */
	public function init()
	{
		parent::init();
		$this->db = Instance::ensure($this->db, 'yii\db\Connection');
	}

	public function read(Cart $cart)
	{
		if ($data = $this->select($cart)) {
			$this->unserialize($data['value'], $cart);
		}
	}

	public function write(Cart $cart)
	{
		if ($this->select($cart)) {
			$this->update($cart);
		} else {
			$this->insert($cart);
		}
	}

	public function lock($drop, Cart $cart)
	{
		if ($data = $this->select($cart)) {
			if ($drop) {
				$this->db->createCommand()->update($this->table, [
						'and',
						['or',
							['user_id' => Yii::$app->user->id],
							['id' => Yii::$app->session->getId()],
						],
						['name' 	=> $cart->id],
						['status' 	=> 0]
					]
				)->execute();
			} else {
				$this->db->createCommand()->update($this->table, [
						'status' => 1
					],
					[
						'and',
						['or',
							['user_id' => Yii::$app->user->id],
							['id' => Yii::$app->session->getId()],
						],
						['name' 	=> $cart->id],
						['status' 	=> 0]
					]
				)->execute();
				Yii::$app->session->regenerateID(true);
			}
			$this->db->createCommand($qry)->execute();
		}
	}

	/**
	 * @param Cart $cart
	 * @return array|bool
	 */
	public function select(Cart $cart)
	{
		return (new Query())
			->select('*')
			->from($this->table)
			->where(['or', 'user_id = ' . Yii::$app->user->id, 'id = \'' . Yii::$app->session->getId() . '\''])
			->andWhere([
				'name' => $cart->id,
				'status' => 0,
			])
			->orderBy(['id' => SORT_DESC])
			->limit(1)
			->one($this->db);
	}

	/**
	 * @param Cart $cart
	 */
	public function insert(Cart $cart)
	{
		$this->db->createCommand()->insert($this->table, [
			'id' => Yii::$app->session->getId(),
			'user_id' => Yii::$app->user->id,
			'name' => $cart->id,
			'value' => $this->serialize($cart),
			'status' => 0,
		])->execute();
	}

	/**
	 * @param Cart $cart
	 */
	public function update(Cart $cart)
	{
		$this->db->createCommand()->update($this->table, [
				'value' => $this->serialize($cart)
			],
			[
				'and',
				['or',
					['user_id' => Yii::$app->user->id],
					['id' => Yii::$app->session->getId()],
				],
				['name' 	=> $cart->id],
				['status' 	=> 0]
			]
		)->execute();
	}
}