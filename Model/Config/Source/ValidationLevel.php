<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Peerapatfc\ThaiAddress\Model\Config;

class ValidationLevel implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::LEVEL_POSTCODE, 'label' => __('Postal code must exist')],
            ['value' => Config::LEVEL_PROVINCE, 'label' => __('Postal code and province must match')],
            [
                'value' => Config::LEVEL_ADDRESS,
                'label' => __('Postal code, province, district and sub-district must match'),
            ],
        ];
    }
}
