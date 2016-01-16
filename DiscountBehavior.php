<?php

namespace hscstudio\cart;

use yii\base\Behavior;


/**
 * Class DiscountBehavior
 * @package \hscsstudio\cart
 */
class DiscountBehavior extends Behavior
{
    public function events()
    {
        return [
            Cart::EVENT_COST_CALCULATION => 'onCostCalculation',
            ItemInterface::EVENT_COST_CALCULATION => 'onCostCalculation',
        ];
    }

    /**
     * @param CostCalculationEvent $event
     */
    public function onCostCalculation($event)
    {

    }
}