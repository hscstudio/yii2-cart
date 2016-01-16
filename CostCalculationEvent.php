<?php

namespace hscstudio\cart;

use yii\base\Event;


/**
 * Class CostCalculationEvent
 * @package \hscsstudio\cart
 */
class CostCalculationEvent extends Event
{
    /**
     * Base cost of the cart or item, that was calculated without discount
     * @var int
     */
    public $baseCost;
    /**
     * Discount value that could be filled by the cart's behaviors that should provide discounts.
     * This value will be subtracted from the cart's cost
     * @var int
     */
    public $discountValue = 0;
} 