<?php declare(strict_types=1);

namespace Shopware\Checkout\Payment\Event\PaymentMethodTranslation;

use Shopware\Framework\ORM\Search\AggregatorResult;
use Shopware\Application\Context\Struct\ApplicationContext;
use Shopware\Framework\Event\NestedEvent;

class PaymentMethodTranslationAggregationResultLoadedEvent extends NestedEvent
{
    public const NAME = 'payment_method_translation.aggregation.result.loaded';

    /**
     * @var AggregatorResult
     */
    protected $result;

    public function __construct(AggregatorResult $result)
    {
        $this->result = $result;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): ApplicationContext
    {
        return $this->result->getContext();
    }

    public function getResult(): AggregatorResult
    {
        return $this->result;
    }
}
