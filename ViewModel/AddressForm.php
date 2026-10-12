<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Peerapatfc\ThaiAddress\Model\Config;

/**
 * Settings for the drop-downs of the My Account address form.
 */
class AddressForm implements ArgumentInterface
{
    /**
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Whether the form gets the drop-downs.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isAutocompleteEnabled();
    }

    /**
     * The options of the address form widget.
     *
     * @return array{restUrl: string, language: string}
     */
    public function getWidgetConfig(): array
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();

        return [
            'restUrl' => $store->getBaseUrl() . 'rest/' . $store->getCode() . '/V1/thai-address/',
            'language' => $this->config->getLanguage(),
        ];
    }
}
