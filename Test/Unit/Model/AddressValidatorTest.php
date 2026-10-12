<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Model;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Model\AddressValidator;
use Peerapatfc\ThaiAddress\Model\Config;
use Peerapatfc\ThaiAddress\Test\Unit\DatasetFactory;
use Peerapatfc\ThaiAddress\Model\Dataset;
use Peerapatfc\ThaiAddress\Model\ProvinceMatcher;
use Peerapatfc\ThaiAddress\Model\RegionResolver;

class AddressValidatorTest extends TestCase
{
    private const BANGKOK_REGION_ID = 700;
    private const PHUKET_REGION_ID = 783;

    private Config&MockObject $config;
    private RegionResolver&MockObject $regions;
    private AddressValidator $validator;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->config->method('enforceProvinceMatch')->willReturn(true);

        $this->regions = $this->createMock(RegionResolver::class);
        $this->regions->method('getProvinceCode')->willReturnMap([
            [self::BANGKOK_REGION_ID, 10],
            [self::PHUKET_REGION_ID, 83],
        ]);

        $dataset = DatasetFactory::create();
        $this->validator = new AddressValidator($dataset, new ProvinceMatcher($dataset), $this->regions, $this->config);
    }

    public function testAMatchingRegionIdPasses(): void
    {
        $this->assertNull($this->validator->validate('10110', self::BANGKOK_REGION_ID, null));
    }

    public function testAMatchingProvinceNameWorksWithoutARegionId(): void
    {
        $this->assertNull($this->validator->validate('10110', null, 'กรุงเทพ'));
        $this->assertNull($this->validator->validate('83000', null, 'Phuket'));
    }

    public function testAProvinceThatDoesNotMatchIsRejectedWithTheExpectedProvince(): void
    {
        $error = $this->validator->validate('10110', self::PHUKET_REGION_ID, null);

        $this->assertNotNull($error);
        $this->assertStringContainsString('10110', (string)$error);
        $this->assertStringContainsString('กรุงเทพมหานคร', (string)$error);
    }

    public function testAnUnrecognisedProvinceTextIsRejected(): void
    {
        $this->assertNotNull($this->validator->validate('10110', null, 'Atlantis'));
    }

    public function testAnUnknownPostcodeIsRejected(): void
    {
        $error = $this->validator->validate('00000', self::BANGKOK_REGION_ID, null);

        $this->assertNotNull($error);
        $this->assertStringContainsString('00000', (string)$error);
    }

    /**
     * @dataProvider malformedPostcodes
     */
    public function testAMalformedPostcodeIsRejected(?string $postcode): void
    {
        $this->assertNotNull($this->validator->validate($postcode, self::BANGKOK_REGION_ID, null));
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function malformedPostcodes(): array
    {
        return ['empty' => [''], 'null' => [null], 'too short' => ['1011'], 'letters' => ['abcde'], 'too long' => ['101100']];
    }

    public function testWithNoProvinceGivenOnlyThePostcodeIsChecked(): void
    {
        $this->assertNull($this->validator->validate('10110', null, null));
        $this->assertNull($this->validator->validate('10110', 0, ''));
    }

    public function testTheProvinceCheckCanBeSwitchedOff(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('enforceProvinceMatch')->willReturn(false);
        $dataset = DatasetFactory::create();
        $validator = new AddressValidator($dataset, new ProvinceMatcher($dataset), $this->regions, $config);

        $this->assertNull($validator->validate('10110', self::PHUKET_REGION_ID, 'Phuket'));
        $this->assertNotNull($validator->validate('00000', self::PHUKET_REGION_ID, 'Phuket'), 'postcode still checked');
    }
}
