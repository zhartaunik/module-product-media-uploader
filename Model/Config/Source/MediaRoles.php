<?php

declare(strict_types=1);

namespace PerfectCode\ProductMediaUploader\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class MediaRoles implements OptionSourceInterface
{
    /**
     * Get a list of supported media roles
     *
     * @codeCoverageIgnore
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'image', 'label' => 'image',],
            ['value' => 'small_image', 'label' => 'small_image',],
            ['value' => 'thumbnail', 'label' => 'thumbnail',],
            ['value' => 'swatch_image', 'label' => 'swatch_image',],
        ];
    }
}
