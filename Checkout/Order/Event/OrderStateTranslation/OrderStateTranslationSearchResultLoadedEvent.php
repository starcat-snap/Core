<?php declare(strict_types=1);

namespace Shopware\Checkout\Order\Event\OrderStateTranslation;

use Shopware\Checkout\Order\Struct\OrderStateTranslationSearchResult;
use Shopware\Application\Context\Struct\ApplicationContext;
use Shopware\Framework\Event\NestedEvent;

class OrderStateTranslationSearchResultLoadedEvent extends NestedEvent
{
    public const NAME = 'order_state_translation.search.result.loaded';

    /**
     * @var OrderStateTranslationSearchResult
     */
    protected $result;

    public function __construct(OrderStateTranslationSearchResult $result)
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
}
