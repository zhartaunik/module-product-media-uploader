<?php

declare(strict_types=1);

namespace PerfectCode\ProductMediaUploader\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    /**
     * @return string
     */
    public function getDefaultLabel(): string
    {
        return (string) $this->scopeConfig->getValue('catalog/media_import/label');
    }

    /**
     * @return int
     */
    public function getDefaultPosition(): int
    {
        return (int) $this->scopeConfig->getValue('catalog/media_import/position');
    }

    /**
     * @return bool
     */
    public function isDisabledByDefault(): bool
    {
        return (bool) $this->scopeConfig->getValue('catalog/media_import/disabled');
    }

    /**
     * @return string[]
     */
    public function getAssignedRoles(): array
    {
        return explode(',', $this->scopeConfig->getValue('catalog/media_import/types'));
    }
}
