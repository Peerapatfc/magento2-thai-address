<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Api\Data\AreaInterfaceFactory;
use Peerapatfc\ThaiAddress\Model\Area;
use Peerapatfc\ThaiAddress\Model\AreaList;
use Peerapatfc\ThaiAddress\Model\RegionResolver;
use Peerapatfc\ThaiAddress\Test\Unit\DatasetFactory;

class AreaListTest extends TestCase
{
    private AreaList $list;

    protected function setUp(): void
    {
        $regions = $this->createMock(RegionResolver::class);
        $regions->method('getRegionId')->willReturnCallback(fn (int $code): ?int => $code === 10 ? 700 : null);

        $factory = $this->createMock(AreaInterfaceFactory::class);
        $factory->method('create')->willReturnCallback(fn (): Area => new Area());

        $this->list = new AreaList(DatasetFactory::create(), $regions, $factory);
    }

    public function testItListsAll77ProvincesWithTheirRegionId(): void
    {
        $provinces = $this->list->getProvinces();

        $this->assertCount(77, $provinces);
        $bangkok = array_values(array_filter($provinces, fn ($a): bool => $a->getCode() === 10))[0];
        $this->assertSame('Bangkok', $bangkok->getNameEn());
        $this->assertSame(700, $bangkok->getRegionId());
    }

    public function testDistrictsBelongToTheProvince(): void
    {
        $districts = $this->list->getDistricts(10);

        $this->assertCount(50, $districts);
        foreach ($districts as $district) {
            $this->assertSame(10, intdiv($district->getCode(), 100));
        }
    }

    public function testSubdistrictsBelongToTheDistrictAndCarryAPostcode(): void
    {
        $subdistricts = $this->list->getSubdistricts(1004);

        $this->assertCount(5, $subdistricts);
        foreach ($subdistricts as $subdistrict) {
            $this->assertSame(1004, intdiv($subdistrict->getCode(), 100));
            $this->assertMatchesRegularExpression('/^\d{5}$/', (string)$subdistrict->getPostcode());
        }
    }

    public function testNamesAreInThaiDictionaryOrder(): void
    {
        $names = array_map(fn ($a): string => $a->getNameTh(), $this->list->getSubdistricts(1004));

        $this->assertSame(['บางรัก', 'มหาพฤฒาราม', 'สี่พระยา', 'สีลม', 'สุริยวงศ์'], $names);
    }

    public function testAnUnknownCodeGivesAnEmptyList(): void
    {
        $this->assertSame([], $this->list->getDistricts(99));
        $this->assertSame([], $this->list->getSubdistricts(9999));
    }
}
