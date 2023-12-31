<?php declare(strict_types=1);

namespace SnapAdmin\Core\Framework\DataAbstractionLayer\Version\Aggregate\VersionCommit;

use SnapAdmin\Core\Framework\DataAbstractionLayer\Entity;
use SnapAdmin\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use SnapAdmin\Core\Framework\DataAbstractionLayer\Version\Aggregate\VersionCommitData\VersionCommitDataCollection;
use SnapAdmin\Core\Framework\DataAbstractionLayer\Version\VersionEntity;
use SnapAdmin\Core\Framework\Log\Package;

#[Package('core')]
class VersionCommitEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var int
     */
    protected $autoIncrement;

    /**
     * @var string|null
     */
    protected $message;

    /**
     * @var string|null
     */
    protected $userId;

    /**
     * @var VersionCommitDataCollection
     */
    protected $data;

    /**
     * @var bool
     */
    protected $isMerge;

    /**
     * @var VersionEntity|null
     */
    protected $version;

    public function getAutoIncrement(): int
    {
        return $this->autoIncrement;
    }

    public function setAutoIncrement(int $autoIncrement): void
    {
        $this->autoIncrement = $autoIncrement;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    public function getVersionId(): ?string
    {
        return $this->versionId;
    }

    public function getData(): VersionCommitDataCollection
    {
        return $this->data;
    }

    public function setData(VersionCommitDataCollection $data): void
    {
        $this->data = $data;
    }

    public function getIsMerge(): bool
    {
        return $this->isMerge;
    }

    public function setIsMerge(bool $isMerge): void
    {
        $this->isMerge = $isMerge;
    }

    public function getVersion(): ?VersionEntity
    {
        return $this->version;
    }

    public function setVersion(VersionEntity $version): void
    {
        $this->version = $version;
    }
}
