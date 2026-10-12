<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;

/**
 * Maps between official province codes and Magento region ids for Thailand.
 *
 * The regions are created by Setup\Patch\Data\AddThaiRegions, which stores the province code in the
 * region's "code" column.
 */
class RegionResolver
{
    /** @var array<int, int>|null province code => region id */
    private ?array $regionIds = null;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(private readonly CollectionFactory $collectionFactory)
    {
    }

    /**
     * Magento region id for a province code.
     *
     * @param int $provinceCode
     * @return int|null
     */
    public function getRegionId(int $provinceCode): ?int
    {
        return $this->map()[$provinceCode] ?? null;
    }

    /**
     * Province code for a Magento region id of Thailand.
     *
     * @param int $regionId
     * @return int|null
     */
    public function getProvinceCode(int $regionId): ?int
    {
        $code = array_search($regionId, $this->map(), true);

        return $code === false ? null : (int)$code;
    }

    /**
     * Province code => region id, loaded once.
     *
     * @return array<int, int>
     */
    private function map(): array
    {
        if ($this->regionIds === null) {
            $this->regionIds = [];
            $collection = $this->collectionFactory->create()->addCountryFilter(Dataset::COUNTRY_ID);
            foreach ($collection as $region) {
                if (ctype_digit((string)$region->getCode())) {
                    $this->regionIds[(int)$region->getCode()] = (int)$region->getRegionId();
                }
            }
        }

        return $this->regionIds;
    }
}
