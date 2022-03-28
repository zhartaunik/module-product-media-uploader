<?php

declare(strict_types=1);

namespace PerfectCode\ProductMediaUploader\Model\Product;

use Laminas\Validator\Uri as UriValidator;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterfaceFactory;
use Magento\Framework\Api\Data\ImageContentInterfaceFactory;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Filesystem\Glob;
use Magento\Framework\Filesystem\Io\File as IoFile;
use PerfectCode\ProductMediaUploader\Api\MediaEntryGeneratorInterface;
use PerfectCode\ProductMediaUploader\Helper\Config as ConfigHelper;
use PerfectCode\ProductMediaUploader\Model\MediaImportException;

class MediaEntryGenerator implements MediaEntryGeneratorInterface
{
    /**
     * @var ImageContentInterfaceFactory
     */
    private ImageContentInterfaceFactory $contentFactory;

    /**
     * @var ProductAttributeMediaGalleryEntryInterfaceFactory
     */
    private ProductAttributeMediaGalleryEntryInterfaceFactory $mediaEntryFactory;

    /**
     * @var Glob
     */
    private Glob $glob;

    /**
     * @var IoFile
     */
    private IoFile $ioFile;

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var DriverFile
     */
    private DriverFile $driver;

    /**
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;

    /**
     * @var UriValidator
     */
    private UriValidator $uriValidator;

    /**
     * @param ImageContentInterfaceFactory $contentFactory
     * @param ProductAttributeMediaGalleryEntryInterfaceFactory $mediaEntryFactory
     * @param Glob $glob
     * @param IoFile $ioFile
     * @param Filesystem $filesystem
     * @param DriverFile $driver
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        ImageContentInterfaceFactory $contentFactory,
        ProductAttributeMediaGalleryEntryInterfaceFactory $mediaEntryFactory,
        Glob $glob,
        IoFile $ioFile,
        Filesystem $filesystem,
        DriverFile $driver,
        ConfigHelper $configHelper,
        UriValidator $uriValidator
    ) {
        $this->contentFactory = $contentFactory;
        $this->mediaEntryFactory = $mediaEntryFactory;
        $this->glob = $glob;
        $this->ioFile = $ioFile;
        $this->filesystem = $filesystem;
        $this->driver = $driver;
        $this->configHelper = $configHelper;
        $this->uriValidator = $uriValidator;
    }

    /**
     * Prepares an object which can be pushed to native Magento ProductInterface as a product image.
     *
     * @param string $imageUrl External product image
     * @param string[] $data Additional data which can be used for image title generation.
     * @return ProductAttributeMediaGalleryEntryInterface
     * @throws FileSystemException
     * @throws MediaImportException
     */
    public function generate(string $imageUrl, array $data = []): ProductAttributeMediaGalleryEntryInterface
    {
        if (!$this->uriValidator->isValid($imageUrl)) {
            throw new MediaImportException(__('Url `%1` has invalid format, data: %2', $imageUrl, json_encode($data)));
        }
        $imageUrl = $this->removeQueryString($imageUrl);
        $imageAbsolutePath = $this->getMediaFile($imageUrl, $data);

        if ($imageAbsolutePath === null) {
            $mediaFile = $this->driver->fileGetContents($imageUrl);
        } else {
            $mediaFile = $this->driver->fileGetContents($imageAbsolutePath);
        }

        $contentDataObject = $this->contentFactory->create()
            ->setName($this->getImageName($imageUrl, $data))
            ->setBase64EncodedData(base64_encode($mediaFile))
            ->setType($this->getMimeType($mediaFile));
        $mediaEntry = $this->mediaEntryFactory->create();
        $mediaEntry->setLabel($data['label'] ?? $this->configHelper->getDefaultLabel())
            ->setMediaType('image')
            ->setPosition($this->configHelper->getDefaultPosition())
            ->setDisabled($this->configHelper->isDisabledByDefault())
            ->setTypes($this->configHelper->getAssignedRoles())
            ->setContent($contentDataObject);

        return $mediaEntry;
    }

    /**
     * Get downloaded image name to be saved in pub/media folder.
     *
     * @param string $imageUrl
     * @param string[] $data Additional data which can be used for image title generation.
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getImageName(string $imageUrl, array $data = []): string
    {
        /** @var string[] $pathInfo */
        $pathInfo = $this->ioFile->getPathInfo($imageUrl);
        return $pathInfo['basename'];
    }

    /**
     * Removes querystring from the URL if it exists.
     * E.g. convert from-to
     *      http://localhost/my-image.jpg?param=value
     *      http://localhost/my-image.jpg
     *
     * @param string $imageUrl
     * @return string URL without querystring
     */
    private function removeQueryString(string $imageUrl): string
    {
        if (strpos($imageUrl, '?') !== false) {
            $imageUrl = substr($imageUrl, 0, strpos($imageUrl, '?'));
        }

        return $imageUrl;
    }

    /**
     * Retrieves all media files from disc, already downloaded with the similar name inside media folder.
     * E.g. for the image http://localhost/my-image.jpg it searches:
     * /var/www/magento/pub/media/catalog/product/m/y/my-image*
     * This includes such files as my-image.jpg, my-image_1.jpg or my-image_1_1.jpg
     * (also files generated by default magento when file with the same name already exists).
     *
     * @param string $imageUrl
     * @param string[] $data Additional data which can be used for image title generation.
     * @return string|null
     */
    private function getMediaFile(string $imageUrl, array $data = []): ?string
    {
        $imageName = $this->getImageName($imageUrl, $data);
        /** @var string[] $pathInfo */
        $pathInfo = $this->ioFile->getPathInfo($imageName);
        $mediaDirPath = $this->filesystem->getDirectoryRead(AppDirectoryList::MEDIA)->getAbsolutePath();
        $productImages = $this->glob->glob(
            $mediaDirPath
            . 'catalog' . DS
            . 'product' . DS
            . $imageName[0] . DS
            . $imageName[1] . DS
            . $pathInfo['filename'] . '*'
        );

        return array_shift($productImages);
    }

    /**
     * Returns image mime type. E.g. image/jpeg
     *
     * @param string $mediaFile
     * @return string
     */
    private function getMimeType(string $mediaFile): string
    {
        /** @var string[] $photoProperties */
        $photoProperties = getimagesizefromstring($mediaFile);
        return $photoProperties['mime'];
    }
}
