<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Peerapatfc\ThaiAddress\Model\Config;

/**
 * Turns City into a district drop-down for Thailand, adds a sub-district drop-down and puts the address fields
 * in the order a Thai address is written: country, province, district, sub-district, postal code.
 *
 * Applies to the shipping address and to every billing address form. Other countries keep a text input.
 */
class ThaiAddressFields
{
    private const SORT_ORDER = [
        'country_id' => 80,
        'region_id' => 82,
        'city' => 84,
        'thai_subdistrict' => 86,
        'postcode' => 88,
    ];

    /**
     * @param Config $config
     */
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * Patches every address form in the checkout layout.
     *
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(LayoutProcessor $subject, array $jsLayout): array
    {
        if ($this->config->isAutocompleteEnabled()) {
            $this->patch($jsLayout);
        }

        return $jsLayout;
    }

    /**
     * Finds the address forms (the ones with country, city and postcode fields) and patches them.
     *
     * @param array $node
     * @return void
     */
    private function patch(array &$node): void
    {
        $children = $node['children'] ?? null;
        if (is_array($children) && isset($children['country_id'], $children['city'], $children['postcode'])) {
            $this->patchFields($node['children']);

            return;
        }

        foreach (array_keys($node) as $key) {
            if (is_array($node[$key])) {
                $this->patch($node[$key]);
            }
        }
    }

    /**
     * Replaces the City component, adds the sub-district field and sets the field order.
     *
     * @param array $fields The children of one address form
     * @return void
     */
    private function patchFields(array &$fields): void
    {
        $city = &$fields['city'];
        $city['component'] = 'Peerapatfc_ThaiAddress/js/form/element/thai-district';
        $city['config']['elementTmpl'] = 'Peerapatfc_ThaiAddress/form/element/area';

        $fields['thai_subdistrict'] = [
            'component' => 'Peerapatfc_ThaiAddress/js/form/element/thai-subdistrict',
            'config' => [
                'customScope' => $city['config']['customScope'] ?? '',
                'template' => 'ui/form/field',
                'elementTmpl' => 'Peerapatfc_ThaiAddress/form/element/area',
            ],
            'provider' => $city['provider'] ?? 'checkoutProvider',
            'dataScope' => preg_replace('/\.city$/', '.thai_subdistrict', (string)($city['dataScope'] ?? 'city')),
            'label' => __('Sub-district'),
            'visible' => false,
            'validation' => ['required-entry' => false],
        ];

        foreach (self::SORT_ORDER as $field => $sortOrder) {
            if (isset($fields[$field])) {
                $fields[$field]['sortOrder'] = $sortOrder;
            }
        }
    }
}
