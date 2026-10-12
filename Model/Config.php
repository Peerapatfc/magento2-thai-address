<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'thai_address/validation/enabled';
    private const XML_PATH_STREET_LINES = 'customer/address/street_lines';
    private const XML_PATH_AUTOCOMPLETE = 'thai_address/autocomplete/enabled';
    private const XML_PATH_LEVEL = 'thai_address/validation/level';

    public const LEVEL_POSTCODE = 'postcode';
    public const LEVEL_PROVINCE = 'province';
    public const LEVEL_ADDRESS = 'address';

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
     * How strictly a Thai address is checked: one of the LEVEL_* constants.
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getValidationLevel(int|string|null $storeId = null): string
    {
        $level = (string)$this->scopeConfig->getValue(self::XML_PATH_LEVEL, ScopeInterface::SCOPE_STORE, $storeId);

        return in_array($level, [self::LEVEL_POSTCODE, self::LEVEL_PROVINCE, self::LEVEL_ADDRESS], true)
            ? $level
            : self::LEVEL_PROVINCE;
    }

    /**
     * Whether the checkout suggests province, district and sub-district from the postal code.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isAutocompleteEnabled(int|string|null $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AUTOCOMPLETE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Language of the drop-down names: "th" for a Thai store view, "en" otherwise.
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getLanguage(int|string|null $storeId = null): string
    {
        $locale = (string)$this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId);

        return str_starts_with($locale, 'th') ? 'th' : 'en';
    }

    /**
     * How many street lines the address form has (Stores > Configuration > Customers > Customer Configuration).
     *
     * @param int|string|null $storeId
     * @return int
     */
    public function getStreetLines(int|string|null $storeId = null): int
    {
        $lines = (int)$this->scopeConfig->getValue(self::XML_PATH_STREET_LINES, ScopeInterface::SCOPE_STORE, $storeId);

        return $lines > 0 ? $lines : 2;
    }
}
