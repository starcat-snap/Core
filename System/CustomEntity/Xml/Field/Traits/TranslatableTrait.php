<?php declare(strict_types=1);

namespace SnapAdmin\Core\System\CustomEntity\Xml\Field\Traits;

use SnapAdmin\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
trait TranslatableTrait
{
    protected bool $translatable;

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }
}
