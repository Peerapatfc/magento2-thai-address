<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Model;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Test\Unit\DatasetFactory;
use Peerapatfc\ThaiAddress\Model\Dataset;
use Peerapatfc\ThaiAddress\Model\ProvinceMatcher;

class ProvinceMatcherTest extends TestCase
{
    private ProvinceMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new ProvinceMatcher(DatasetFactory::create());
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function acceptedSpellings(): array
    {
        return [
            'official Thai' => ['กรุงเทพมหานคร', 10],
            'short Thai' => ['กรุงเทพ', 10],
            'with a trailing symbol' => ['กรุงเทพฯ', 10],
            'abbreviation' => ['กทม.', 10],
            'English' => ['Bangkok', 10],
            'English, any case and padding' => ['  bangkok ', 10],
            'with the จังหวัด prefix' => ['จังหวัดกรุงเทพมหานคร', 10],
            'with the จ. prefix' => ['จ.เชียงใหม่', 50],
            'English with Province suffix' => ['Chiang Mai Province', 50],
            'English with spaces' => ['chiang mai', 50],
        ];
    }

    #[DataProvider('acceptedSpellings')]
    public function testItAcceptsCommonSpellings(string $input, int $provinceCode): void
    {
        $this->assertTrue($this->matcher->matches($input, $provinceCode), $input);
        $this->assertSame($provinceCode, $this->matcher->findCode($input), $input);
    }

    public function testItRejectsAnotherProvince(): void
    {
        $this->assertFalse($this->matcher->matches('ภูเก็ต', 10));
        $this->assertFalse($this->matcher->matches('Phuket', 50));
    }

    public function testItRejectsEmptyAndUnknownText(): void
    {
        $this->assertFalse($this->matcher->matches('', 10));
        $this->assertFalse($this->matcher->matches('   ', 10));
        $this->assertNull($this->matcher->findCode('Atlantis'));
        $this->assertFalse($this->matcher->matches('Bangkok', 9999));
    }
}
