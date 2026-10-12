<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Model;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Test\Unit\DatasetFactory;
use Peerapatfc\ThaiAddress\Model\Dataset;

class DatasetTest extends TestCase
{
    private Dataset $dataset;

    protected function setUp(): void
    {
        $this->dataset = DatasetFactory::create();
    }

    public function testItKnowsAll77Provinces(): void
    {
        $provinces = $this->dataset->getProvinces();

        $this->assertCount(77, $provinces);
        $this->assertSame(['th' => 'กรุงเทพมหานคร', 'en' => 'Bangkok'], $provinces[10]);
        $this->assertSame('Chiang Mai', $provinces[50]['en']);
    }

    public function testAPostcodeCanHaveSeveralSubdistricts(): void
    {
        $rows = $this->dataset->findByPostcode('10110');

        $this->assertNotEmpty($rows);
        $this->assertSame([10], array_values(array_unique(array_column($rows, 'province_code'))));
        $this->assertArrayHasKey('district', $rows[0]);
        $this->assertArrayHasKey('subdistrict', $rows[0]);
    }

    public function testAnUnknownPostcodeHasNoRows(): void
    {
        $this->assertSame([], $this->dataset->findByPostcode('00000'));
        $this->assertSame([], $this->dataset->findByPostcode('abcde'));
    }

    public function testEveryPostcodeRowPointsAtARealProvince(): void
    {
        $provinces = $this->dataset->getProvinces();

        foreach (['10110', '50200', '83000', '94000', '40000'] as $postcode) {
            foreach ($this->dataset->findByPostcode($postcode) as $row) {
                $this->assertArrayHasKey($row['province_code'], $provinces, $postcode);
            }
        }
    }
}
