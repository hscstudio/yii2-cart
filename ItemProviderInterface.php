<?php

namespace hscstudio\cart;


/**
 * Interface ItemProviderInterface
 * @property ItemInterface $cartItem
 * @package \hscsstudio\cart
 */
interface ItemProviderInterface
{
    /**
     * @param array $params Parameters for cart item
     * @return ItemInterface
     */
    public function getCartItem($params = []);
} 