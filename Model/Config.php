<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'thai_address/validation/enabled';
    private const XML_PATH_ENFORCE_PROVINCE = 'thai_address/validation/enforce_province_match';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    /**
     * Whether Thai shipping and billing addresses are checked at checkout.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isValidationEnabled(int|string|null $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Whether the chosen province must agree with the postal code (otherwise only the postal code is checked).
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function enforceProvinceMatch(int|string|null $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENFORCE_PROVINCE, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
