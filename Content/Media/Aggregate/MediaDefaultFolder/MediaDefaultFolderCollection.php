<?php declare(strict_types=1);

namespace SnapAdmin\Core\Content\Media\Aggregate\MediaDefaultFolder;

use SnapAdmin\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<MediaDefaultFolderEntity>
 */
class MediaDefaultFolderCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'media_default_folder_collection';
    }

    protected function getExpectedClass(): string
    {
        return MediaDefaultFolderEntity::class;
    }
}
