<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Collator;
use Peerapatfc\ThaiAddress\Api\AreaListInterface;
use Peerapatfc\ThaiAddress\Api\Data\AreaInterface;
use Peerapatfc\ThaiAddress\Api\Data\AreaInterfaceFactory;

class AreaList implements AreaListInterface
{
    /**
     * @param Dataset $dataset
     * @param RegionResolver $regionResolver
     * @param AreaInterfaceFactory $areaFactory
     */
    public function __construct(
        private readonly Dataset $dataset,
        private readonly RegionResolver $regionResolver,
        private readonly AreaInterfaceFactory $areaFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getProvinces(): array
    {
        $areas = [];
        foreach ($this->dataset->getProvinces() as $code => $names) {
            $areas[] = $this->area($code, $names['th'], $names['en'])
                ->setRegionId($this->regionResolver->getRegionId($code));
        }

        return $this->sorted($areas);
    }

    /**
     * @inheritdoc
     */
    public function getDistricts(int $provinceCode): array
    {
        return $this->sorted(array_map(
            fn (array $row): AreaInterface => $this->area($row['code'], $row['th'], $row['en']),
            $this->dataset->getDistricts($provinceCode)
        ));
    }

    /**
     * @inheritdoc
     */
    public function getSubdistricts(int $districtCode): array
    {
        return $this->sorted(array_map(
            fn (array $row): AreaInterface => $this->area($row['code'], $row['th'], $row['en'])
                ->setPostcode($row['postcode']),
            $this->dataset->getSubdistricts($districtCode)
        ));
    }

    /**
     * Builds an area with its code and names.
     *
     * @param int $code
     * @param string $nameTh
     * @param string $nameEn
     * @return AreaInterface
     */
    private function area(int $code, string $nameTh, string $nameEn): AreaInterface
    {
        return $this->areaFactory->create()->setCode($code)->setNameTh($nameTh)->setNameEn($nameEn);
    }

    /**
     * Thai alphabetical order (the order of a Thai dictionary), not the order of the official codes.
     *
     * @param AreaInterface[] $areas
     * @return AreaInterface[]
     */
    private function sorted(array $areas): array
    {
        $collator = new Collator('th_TH');
        usort($areas, static fn (AreaInterface $a, AreaInterface $b): int => (int)$collator->compare(
            $a->getNameTh(),
            $b->getNameTh()
        ));

        return $areas;
    }
}
