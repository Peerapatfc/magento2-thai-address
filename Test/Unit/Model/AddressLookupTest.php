<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Api\Data\AddressMatchInterfaceFactory;
use Peerapatfc\ThaiAddress\Model\AddressLookup;
use Peerapatfc\ThaiAddress\Model\AddressMatch;
use Peerapatfc\ThaiAddress\Test\Unit\DatasetFactory;
use Peerapatfc\ThaiAddress\Model\Dataset;
use Peerapatfc\ThaiAddress\Model\RegionResolver;

class AddressLookupTest extends TestCase
{
    private AddressLookup $lookup;

    protected function setUp(): void
    {
        $regions = $this->createMock(RegionResolver::class);
        $regions->method('getRegionId')->willReturnCallback(fn (int $code): ?int => $code === 10 ? 700 : null);

        $factory = $this->createMock(AddressMatchInterfaceFactory::class);
        $factory->method('create')->willReturnCallback(fn (): AddressMatch => new AddressMatch());

        $this->lookup = new AddressLookup(DatasetFactory::create(), $regions, $factory);
    }

    public function testItReturnsEverySubdistrictWithItsProvinceAndRegionId(): void
    {
        $matches = $this->lookup->getByPostcode('10110');

        $this->assertNotEmpty($matches);
        foreach ($matches as $match) {
            $this->assertSame('10110', $match->getPostcode());
            $this->assertSame(10, $match->getProvinceCode());
            $this->assertSame('กรุงเทพมหานคร', $match->getProvinceNameTh());
            $this->assertSame('Bangkok', $match->getProvinceNameEn());
            $this->assertSame(700, $match->getRegionId());
            $this->assertNotSame('', $match->getDistrict());
            $this->assertNotSame('', $match->getSubdistrict());
        }
    }

    public function testRegionIdIsNullBeforeTheRegionsAreInstalled(): void
    {
        foreach ($this->lookup->getByPostcode('50200') as $match) {
            $this->assertNull($match->getRegionId());
        }
    }

    public function testAnUnknownPostcodeGivesAnEmptyList(): void
    {
        $this->assertSame([], $this->lookup->getByPostcode('00000'));
    }

    public function testSurroundingSpacesAreIgnored(): void
    {
        $this->assertNotEmpty($this->lookup->getByPostcode(' 10110 '));
    }

    /**
     * @dataProvider notAPostcode
     */
    public function testAMalformedPostcodeIsAnInputError(string $postcode): void
    {
        $this->expectException(InputException::class);

        $this->lookup->getByPostcode($postcode);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notAPostcode(): array
    {
        return ['empty' => [''], 'short' => ['1011'], 'letters' => ['abcde'], 'long' => ['101100']];
    }
}
