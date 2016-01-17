<?php
/**
 * @link https://www.github.com/hscstudio/yii2-cart
 * @copyright Copyright (c) 2016 HafidMukhlasin.com
 * @license http://www.yiiframework.com/license/
 */
 
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