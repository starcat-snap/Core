<?php declare(strict_types=1);

namespace SnapAdmin\Core\Framework\Store\Struct;

use SnapAdmin\Core\Framework\Log\Package;
use SnapAdmin\Core\Framework\Struct\Struct;

/**
 * @codeCoverageIgnore
 */
#[Package('services-settings')]
class StoreUpdateStruct extends Struct
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $iconPath;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $changelog;

    /**
     * @var \DateTimeInterface
     */
    protected $releaseDate;

    /**
     * @var bool
     */
    protected $integrated;

    public function getApiAlias(): string
    {
        return 'store_update';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIconPath(): string
    {
        return $this->iconPath;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getChangelog(): string
    {
        return $this->changelog;
    }

    public function getReleaseDate(): \DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function isIntegrated(): bool
    {
        return $this->integrated;
    }
}
