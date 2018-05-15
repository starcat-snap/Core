<?php declare(strict_types=1);

namespace Shopware\Application\Context\Event\ContextRule;

use Shopware\Application\Context\Struct\ContextRuleSearchResult;
use Shopware\Application\Context\Struct\ApplicationContext;
use Shopware\Framework\Event\NestedEvent;

class ContextRuleSearchResultLoadedEvent extends NestedEvent
{
    public const NAME = 'context_rule.search.result.loaded';

    /**
     * @var ContextRuleSearchResult
     */
    protected $result;

    public function __construct(ContextRuleSearchResult $result)
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
