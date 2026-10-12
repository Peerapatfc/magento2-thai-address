<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\ViewModel;

use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Model\Config;
use Peerapatfc\ThaiAddress\ViewModel\AddressForm;

class AddressFormTest extends TestCase
{
    private function viewModel(bool $enabled, string $language): AddressForm
    {
        $config = $this->createMock(Config::class);
        $config->method('isAutocompleteEnabled')->willReturn($enabled);
        $config->method('getLanguage')->willReturn($language);

        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.example/');
        $store->method('getCode')->willReturn('th');
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new AddressForm($config, $storeManager);
    }

    public function testItGivesTheWidgetTheRestUrlOfTheStoreViewAndTheLanguage(): void
    {
        $this->assertSame(
            ['restUrl' => 'https://shop.example/rest/th/V1/thai-address/', 'language' => 'th'],
            $this->viewModel(true, 'th')->getWidgetConfig()
        );
    }

    public function testItFollowsTheAutocompleteSetting(): void
    {
        $this->assertTrue($this->viewModel(true, 'en')->isEnabled());
        $this->assertFalse($this->viewModel(false, 'en')->isEnabled());
    }
}
