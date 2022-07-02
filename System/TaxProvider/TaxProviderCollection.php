<?php declare(strict_types=1);

namespace Shopware\Core\System\TaxProvider;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @package checkout
 *
 * @extends EntityCollection<TaxProviderEntity>
 */
class TaxProviderCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'tax_provider_collection';
    }

    public function sortByPriority(): void
    {
        $this->sort(fn (TaxProviderEntity $a, TaxProviderEntity $b) => $a->getPriority() <=> $b->getPriority());
    }

    protected function getExpectedClass(): string
    {
        return TaxProviderEntity::class;
    }
}
