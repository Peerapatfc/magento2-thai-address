<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Magento\Framework\Exception\InputException;
use Peerapatfc\ThaiAddress\Api\AddressLookupInterface;
use Peerapatfc\ThaiAddress\Api\Data\AddressMatchInterfaceFactory;

class AddressLookup implements AddressLookupInterface
{
    /**
     * @param Dataset $dataset
     * @param RegionResolver $regionResolver
     * @param AddressMatchInterfaceFactory $matchFactory
     */
    public function __construct(
        private readonly Dataset $dataset,
        private readonly RegionResolver $regionResolver,
        private readonly AddressMatchInterfaceFactory $matchFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getByPostcode(string $postcode): array
    {
        $postcode = trim($postcode);
        if (!preg_match('/^\d{5}$/', $postcode)) {
            throw new InputException(__('The postal code must be exactly 5 digits.'));
        }

        $provinces = $this->dataset->getProvinces();
        $matches = [];
        foreach ($this->dataset->findByPostcode($postcode) as $row) {
            $names = $provinces[$row['province_code']];
            $matches[] = $this->matchFactory->create()
                ->setPostcode($postcode)
                ->setProvinceCode($row['province_code'])
                ->setProvinceNameTh($names['th'])
                ->setProvinceNameEn($names['en'])
                ->setDistrict($row['district'])
                ->setSubdistrict($row['subdistrict'])
                ->setRegionId($this->regionResolver->getRegionId($row['province_code']));
        }

        return $matches;
    }
}
