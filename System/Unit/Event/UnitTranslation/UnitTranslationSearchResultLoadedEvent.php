<?php declare(strict_types=1);

namespace Shopware\System\Unit\Event\UnitTranslation;

use Shopware\System\Unit\Struct\UnitTranslationSearchResult;
use Shopware\Application\Context\Struct\ApplicationContext;
use Shopware\Framework\Event\NestedEvent;

class UnitTranslationSearchResultLoadedEvent extends NestedEvent
{
    public const NAME = 'unit_translation.search.result.loaded';

    /**
     * @var UnitTranslationSearchResult
     */
    protected $result;

    public function __construct(UnitTranslationSearchResult $result)
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
