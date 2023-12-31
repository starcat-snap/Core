<?php declare(strict_types=1);

namespace SnapAdmin\Core\Framework\DataAbstractionLayer;

use SnapAdmin\Core\Framework\Log\Package;

/**
 * @internal
 * Contains all registered entity extensions in the system
 */
#[Package('core')]
class ExtensionRegistry
{
    /**
     * @param iterable<EntityExtension> $extensions
     *
     * @internal
     */
    public function __construct(private readonly iterable $extensions)
    {
    }

    /**
     * @return iterable<EntityExtension>
     */
    public function getExtensions(): iterable
    {
        return $this->extensions;
    }
}
