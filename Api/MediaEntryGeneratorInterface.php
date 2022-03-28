<?php

declare(strict_types=1);

namespace PerfectCode\ProductMediaUploader\Api;

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Framework\Exception\FileSystemException;
use PerfectCode\ProductMediaUploader\Model\MediaImportException;

/**
 * Generate a media_entry to push it into the method ProductInterface::setMediaEntries(array $mediaEntries)
 */
interface MediaEntryGeneratorInterface
{
    /**
     * Prepares an object which can be pushed to native Magento ProductInterface as a product image.
     *
     * @param string $imageUrl External product image.
     * @param string[] $data Additional data which can be used for image title generation.
     * @return ProductAttributeMediaGalleryEntryInterface
     * @throws FileSystemException
     * @throws MediaImportException
     */
    public function generate(string $imageUrl, array $data = []): ProductAttributeMediaGalleryEntryInterface;

    /**
     * Get downloaded image name to be saved in pub/media folder.
     * You may override this class if you need to change an image generation name.
     *
     * @param string $imageUrl
     * @param string[] $data Additional data which can be used for image title generation.
     * @return string
     */
    public function getImageName(string $imageUrl, array $data = []): string;
}
