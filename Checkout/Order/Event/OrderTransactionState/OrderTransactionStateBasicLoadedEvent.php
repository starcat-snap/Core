<?php declare(strict_types=1);

namespace Shopware\Checkout\Order\Event\OrderTransactionState;

use Shopware\Checkout\Order\Collection\OrderTransactionStateBasicCollection;
use Shopware\Application\Context\Struct\ApplicationContext;
use Shopware\Framework\Event\NestedEvent;

class OrderTransactionStateBasicLoadedEvent extends NestedEvent
{
    public const NAME = 'order_transaction_state.basic.loaded';

    /**
     * @var ApplicationContext
     */
    protected $context;

    /**
     * @var OrderTransactionStateBasicCollection
     */
    protected $orderTransactionStates;

    public function __construct(OrderTransactionStateBasicCollection $orderTransactionStates, ApplicationContext $context)
    {
        $this->context = $context;
        $this->orderTransactionStates = $orderTransactionStates;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): ApplicationContext
    {
        return $this->context;
    }

    public function getOrderTransactionStates(): OrderTransactionStateBasicCollection
    {
        return $this->orderTransactionStates;
    }
}
