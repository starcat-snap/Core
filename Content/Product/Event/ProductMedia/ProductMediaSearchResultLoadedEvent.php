<?php declare(strict_types=1);

namespace Shopware\Content\Product\Event\ProductMedia;

use Shopware\Content\Product\Struct\ProductMediaSearchResult;
use Shopware\Application\Context\Struct\ApplicationContext;
use Shopware\Framework\Event\NestedEvent;

class ProductMediaSearchResultLoadedEvent extends NestedEvent
{
    public const NAME = 'product_media.search.result.loaded';

    /**
     * @var ProductMediaSearchResult
     */
    protected $result;

    public function __construct(ProductMediaSearchResult $result)
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
