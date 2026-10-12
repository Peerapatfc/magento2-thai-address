<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Model\Config;
use Peerapatfc\ThaiAddress\Plugin\Checkout\ThaiAddressFields;

class ThaiAddressFieldsTest extends TestCase
{
    private function process(array $layout, bool $enabled = true, int $streetLines = 2): array
    {
        $config = $this->createMock(Config::class);
        $config->method('isAutocompleteEnabled')->willReturn($enabled);
        $config->method('getStreetLines')->willReturn($streetLines);

        return (new ThaiAddressFields($config))->afterProcess($this->createMock(LayoutProcessor::class), $layout);
    }

    /**
     * A shipping form and a billing form, as the checkout layout has them.
     *
     * @return array
     */
    private function layout(): array
    {
        $fields = static fn (string $scope): array => [
            'street' => ['sortOrder' => '70'],
            'country_id' => ['sortOrder' => '81'],
            'region_id' => ['sortOrder' => '80'],
            'city' => [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'config' => ['customScope' => $scope, 'elementTmpl' => 'ui/form/element/input'],
                'provider' => 'checkoutProvider',
                'dataScope' => $scope . '.city',
                'sortOrder' => '90',
            ],
            'postcode' => ['sortOrder' => '110'],
        ];

        return ['components' => ['checkout' => ['children' => [
            'shipping' => ['children' => ['shipping-address-fieldset' => ['children' => $fields('shippingAddress')]]],
            'billing' => ['children' => ['payment' => ['children' => [
                'form-fields' => ['children' => $fields('billingAddresscheckmo')],
            ]]]],
        ]]]];
    }

    /**
     * @param array $layout
     * @return array[] The two address forms
     */
    private function forms(array $layout): array
    {
        $children = $layout['components']['checkout']['children'];

        return [
            $children['shipping']['children']['shipping-address-fieldset']['children'],
            $children['billing']['children']['payment']['children']['form-fields']['children'],
        ];
    }

    public function testEveryAddressFormGetsTheDropdownsInThaiOrder(): void
    {
        foreach ($this->forms($this->process($this->layout())) as $fields) {
            $this->assertSame('Peerapatfc_ThaiAddress/js/form/element/thai-district', $fields['city']['component']);
            $this->assertArrayHasKey('thai_subdistrict', $fields);
            $this->assertSame('Peerapatfc_ThaiAddress/form/element/area', $fields['city']['config']['elementTmpl']);
            $this->assertLessThan($fields['country_id']['sortOrder'], $fields['street']['sortOrder']);
            $this->assertLessThan($fields['region_id']['sortOrder'], $fields['country_id']['sortOrder']);
            $this->assertLessThan($fields['city']['sortOrder'], $fields['region_id']['sortOrder']);
            $this->assertLessThan($fields['thai_subdistrict']['sortOrder'], $fields['city']['sortOrder']);
            $this->assertLessThan($fields['postcode']['sortOrder'], $fields['thai_subdistrict']['sortOrder']);
        }
    }

    public function testTheSubdistrictFieldUsesTheScopeOfItsForm(): void
    {
        [$shipping, $billing] = $this->forms($this->process($this->layout()));

        $this->assertSame('shippingAddress.thai_subdistrict', $shipping['thai_subdistrict']['dataScope']);
        $this->assertSame('billingAddresscheckmo.thai_subdistrict', $billing['thai_subdistrict']['dataScope']);
    }

    public function testWithOneStreetLineThereIsNoSubdistrictField(): void
    {
        foreach ($this->forms($this->process($this->layout(), true, 1)) as $fields) {
            $this->assertSame('Peerapatfc_ThaiAddress/js/form/element/thai-district', $fields['city']['component']);
            $this->assertArrayNotHasKey('thai_subdistrict', $fields);
        }
    }

    public function testNothingChangesWhenTheSettingIsOff(): void
    {
        $this->assertSame($this->layout(), $this->process($this->layout(), false));
    }

    public function testALayoutWithoutAddressFormsIsLeftAlone(): void
    {
        $layout = ['components' => ['checkout' => ['children' => ['summary' => ['children' => ['totals' => []]]]]]];

        $this->assertSame($layout, $this->process($layout));
    }
}
