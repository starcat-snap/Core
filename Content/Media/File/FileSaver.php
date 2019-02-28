<?php declare(strict_types=1);

namespace Shopware\Core\Content\Media\File;

use function Flag\next1309;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailEntity;
use Shopware\Core\Content\Media\Exception\CouldNotRenameFileException;
use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Content\Media\Exception\EmptyMediaFilenameException;
use Shopware\Core\Content\Media\Exception\IllegalFileNameException;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\Exception\MissingFileException;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaType\MediaType;
use Shopware\Core\Content\Media\Message\GenerateThumbnailsMessage;
use Shopware\Core\Content\Media\Metadata\Metadata;
use Shopware\Core\Content\Media\Metadata\MetadataLoader;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Content\Media\Thumbnail\ThumbnailService;
use Shopware\Core\Content\Media\TypeDetector\TypeDetector;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\SourceContext;
use Symfony\Component\Messenger\MessageBusInterface;

class FileSaver
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $mediaRepository;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var ThumbnailService
     */
    private $thumbnailService;

    /**
     * @var FileNameValidator
     */
    private $fileNameValidator;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var MetadataLoader
     */
    private $metadataLoader;

    /**
     * @var TypeDetector
     */
    private $typeDetector;

    public function __construct(
        EntityRepositoryInterface $mediaRepository,
        FilesystemInterface $filesystem,
        UrlGeneratorInterface $urlGenerator,
        ThumbnailService $thumbnailService,
        MetadataLoader $metadataLoader,
        TypeDetector $typeDetector,
        MessageBusInterface $messageBus
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->filesystem = $filesystem;
        $this->urlGenerator = $urlGenerator;
        $this->thumbnailService = $thumbnailService;
        $this->fileNameValidator = new FileNameValidator();
        $this->metadataLoader = $metadataLoader;
        $this->typeDetector = $typeDetector;
        $this->messageBus = $messageBus;
    }

    /**
     * @throws DuplicatedMediaFileNameException
     * @throws EmptyMediaFilenameException
     * @throws IllegalFileNameException
     * @throws MediaNotFoundException
     */
    public function persistFileToMedia(
        MediaFile $mediaFile,
        string $destination,
        string $mediaId,
        Context $context
    ): void {
        $currentMedia = null;
        try {
            $currentMedia = $this->findMediaById($mediaId, $context);
            $destination = $this->validateFileName($destination);
            $this->ensureFileNameIsUnique(
                $currentMedia,
                $destination,
                $mediaFile->getFileExtension(),
                $context
            );
        } catch (DuplicatedMediaFileNameException |
            EmptyMediaFilenameException |
            IllegalFileNameException $e
        ) {
            if ($currentMedia !== null && !$currentMedia->hasFile()) {
                $this->mediaRepository->delete([['id' => $currentMedia->getId()]], $context);
            }

            throw $e;
        }

        $this->removeOldMediaData($currentMedia, $context);

        $mediaType = $this->typeDetector->detect($mediaFile);
        $rawMetadata = $this->metadataLoader->loadFromFile($mediaFile, $mediaType);

        $media = $this->updateMediaEntity(
            $mediaFile,
            $destination,
            $currentMedia,
            $rawMetadata,
            $mediaType,
            $context
        );

        $this->saveFileToMediaDir($mediaFile, $media);

        if (next1309()) {
            $message = new GenerateThumbnailsMessage();
            $message->setMediaIds([$mediaId]);
            $message->withContext($context);

            $this->messageBus->dispatch($message);
        } else {
            $this->thumbnailService->updateThumbnails($media, $context);
        }
    }

    /**
     * @throws CouldNotRenameFileException
     * @throws DuplicatedMediaFileNameException
     * @throws FileExistsException
     * @throws MediaNotFoundException
     * @throws MissingFileException
     * @throws EmptyMediaFilenameException
     * @throws IllegalFileNameException
     */
    public function renameMedia(string $mediaId, string $destination, Context $context): void
    {
        $destination = $this->validateFileName($destination);
        $currentMedia = $this->findMediaById($mediaId, $context);

        if (!$currentMedia->hasFile()) {
            throw new MissingFileException($mediaId);
        }

        if ($destination === $currentMedia->getFileName()) {
            return;
        }

        $this->ensureFileNameIsUnique(
            $currentMedia,
            $destination,
            $currentMedia->getFileExtension(),
            $context
        );

        $this->doRenameMedia($currentMedia, $destination, $context);
    }

    /**
     * @throws CouldNotRenameFileException
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    private function doRenameMedia(MediaEntity $currentMedia, string $destination, Context $context): void
    {
        $updatedMedia = clone $currentMedia;
        $updatedMedia->setFileName($destination);
        $updatedMedia->setUploadedAt(new \DateTime());

        try {
            $renamedFiles = $this->renameFile(
                $this->urlGenerator->getRelativeMediaUrl($currentMedia),
                $this->urlGenerator->getRelativeMediaUrl($updatedMedia)
            );
        } catch (\Exception $e) {
            throw new CouldNotRenameFileException($currentMedia->getId(), $currentMedia->getFileName());
        }

        foreach ($currentMedia->getThumbnails() as $thumbnail) {
            try {
                $renamedFiles = array_merge(
                    $renamedFiles,
                    $this->renameThumbnail($thumbnail, $currentMedia, $updatedMedia)
                );
            } catch (\Exception $e) {
                $this->rollbackRenameAction($currentMedia, $renamedFiles);
            }
        }

        $updateData = [
            'id' => $updatedMedia->getId(),
            'fileName' => $updatedMedia->getFileName(),
            'uploadedAt' => $updatedMedia->getUploadedAt(),
        ];

        try {
            $context->scope(SourceContext::ORIGIN_SYSTEM, function (Context $context) use ($updateData) {
                $this->mediaRepository->update([$updateData], $context);
            });
        } catch (\Exception $e) {
            $this->rollbackRenameAction($currentMedia, $renamedFiles);
        }
    }

    /**
     * @throws CouldNotRenameFileException
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    private function renameThumbnail(
        MediaThumbnailEntity $thumbnail,
        MediaEntity $currentMedia,
        MediaEntity $updatedMedia
    ): array {
        return $this->renameFile(
            $this->urlGenerator->getRelativeThumbnailUrl(
                $currentMedia,
                $thumbnail->getWidth(),
                $thumbnail->getHeight()
            ),
            $this->urlGenerator->getRelativeThumbnailUrl(
                $updatedMedia,
                $thumbnail->getWidth(),
                $thumbnail->getHeight()
            )
        );
    }

    private function removeOldMediaData(MediaEntity $media, Context $context): void
    {
        if (!$media->hasFile()) {
            return;
        }

        $oldMediaFilePath = $this->urlGenerator->getRelativeMediaUrl($media);
        try {
            $this->filesystem->delete($oldMediaFilePath);
        } catch (FileNotFoundException $e) {
            //nth
        }

        $this->thumbnailService->deleteThumbnails($media, $context);
    }

    private function saveFileToMediaDir(MediaFile $mediaFile, MediaEntity $media): void
    {
        $stream = fopen($mediaFile->getFileName(), 'rb');
        $path = $this->urlGenerator->getRelativeMediaUrl($media);
        try {
            $this->filesystem->putStream($path, $stream);
        } finally {
            // The Google Cloud Storage filesystem closes the stream even though it should not. To prevent a fatal
            // error, we therefore need to check whether the stream has been closed yet.
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function updateMediaEntity(
        MediaFile $mediaFile,
        string $destination,
        MediaEntity $media,
        Metadata $metadata,
        MediaType $mediaType,
        Context $context
    ): MediaEntity {
        $data = [
            'id' => $media->getId(),
            'userId' => $context->getSourceContext()->getUserId(),
            'mimeType' => $mediaFile->getMimeType(),
            'fileExtension' => $mediaFile->getFileExtension(),
            'fileSize' => $mediaFile->getFileSize(),
            'fileName' => $destination,
            'metaDataRaw' => serialize($metadata),
            'mediaTypeRaw' => serialize($mediaType),
            'uploadedAt' => new \DateTime(),
        ];

        $context->scope(SourceContext::ORIGIN_SYSTEM, function (Context $context) use ($data) {
            $this->mediaRepository->update([$data], $context);
        });

        return $this->mediaRepository->search(new Criteria([$media->getId()]), $context)->get($media->getId());
    }

    /**
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    private function renameFile($source, $destination): array
    {
        $this->filesystem->rename($source, $destination);

        return [$source => $destination];
    }

    /**
     * @throws CouldNotRenameFileException
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    private function rollbackRenameAction(MediaEntity $oldMedia, array $renamedFiles): void
    {
        foreach ($renamedFiles as $oldFileName => $newFileName) {
            $this->filesystem->rename($newFileName, $oldFileName);
        }

        throw new CouldNotRenameFileException($oldMedia->getId(), $oldMedia->getFileName());
    }

    /**
     * @throws MediaNotFoundException
     */
    private function findMediaById(string $mediaId, Context $context): MediaEntity
    {
        $currentMedia = $this->mediaRepository
            ->search(new Criteria([$mediaId]), $context)
            ->get($mediaId);

        if ($currentMedia === null) {
            throw new MediaNotFoundException($mediaId);
        }

        return $currentMedia;
    }

    /**
     * @throws EmptyMediaFilenameException
     * @throws IllegalFileNameException
     */
    private function validateFileName(string $destination): string
    {
        $destination = rtrim($destination);
        $this->fileNameValidator->validateFileName($destination);

        return $destination;
    }

    /**
     * @throws DuplicatedMediaFileNameException
     */
    private function ensureFileNameIsUnique(
        MediaEntity $currentMedia,
        string $destination,
        string $fileExtension,
        Context $context
    ): void {
        $mediaWithRelatedFileName = $this->searchRelatedMediaByFileName(
            $currentMedia,
            $destination,
            $fileExtension,
            $context
        );

        foreach ($mediaWithRelatedFileName as $media) {
            if ($media->hasFile() && $destination === $media->getFileName()) {
                throw new DuplicatedMediaFileNameException(
                    $destination,
                    $fileExtension
                );
            }
        }
    }

    private function searchRelatedMediaByFileName(
        MediaEntity $media,
        string $destination,
        string $fileExtension,
        Context $context
    ): MediaCollection {
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(
            MultiFilter::CONNECTION_AND,
            [
                new EqualsFilter('fileName', $destination),
                new EqualsFilter('fileExtension', $fileExtension),
                new NotFilter(
                    NotFilter::CONNECTION_AND,
                    [new EqualsFilter('id', $media->getId())]
                ),
            ]
        ));

        /** @var MediaCollection $mediaCollection */
        $mediaCollection = $this->mediaRepository->search($criteria, $context)->getEntities();

        return $mediaCollection;
    }
}
