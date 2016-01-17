/**
 * Database schema.
 *
 * @author Hafid Mukhlasin <hafidmukhlasin@gmail.com>
 * @since 2.2
 * @link https://www.github.com/hscstudio/yii2-cart
 * @copyright Copyright (c) 2016 HafidMukhlasin.com
 * @license http://www.yiiframework.com/license/
 */

CREATE TABLE `cart` (
  `id` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);