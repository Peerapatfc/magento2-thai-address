<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Model\AreaNameMatcher;

class AreaNameMatcherTest extends TestCase
{
    private AreaNameMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new AreaNameMatcher();
    }

    /**
     * @dataProvider sameArea
     */
    public function testDifferentSpellingsOfTheSameAreaMatch(string $typed, string $official): void
    {
        $this->assertTrue($this->matcher->matches($typed, $official));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function sameArea(): array
    {
        return [
            'bare name' => ['บางรัก', 'บางรัก'],
            'เขต prefix' => ['เขตบางรัก', 'บางรัก'],
            'เขต prefix with space' => ['เขต บางรัก', 'บางรัก'],
            'แขวง prefix' => ['แขวงสีลม', 'สีลม'],
            'อำเภอ prefix' => ['อำเภอเมืองเชียงใหม่', 'เมืองเชียงใหม่'],
            'abbreviated อ.' => ['อ.เมืองเชียงใหม่', 'เมืองเชียงใหม่'],
            'abbreviated ต.' => ['ต.ศรีภูมิ', 'ศรีภูมิ'],
            'tone mark left out' => ['ท่าพระ', 'ท่าพระ'],
            'tone mark dropped' => ['ทาพระ', 'ท่าพระ'],
            'surrounding spaces' => ['  สีลม  ', 'สีลม'],
        ];
    }

    public function testDifferentAreasDoNotMatch(): void
    {
        $this->assertFalse($this->matcher->matches('สีลม', 'บางรัก'));
    }

    public function testAnEmptyValueNeverMatches(): void
    {
        $this->assertFalse($this->matcher->matches('', ''));
        $this->assertFalse($this->matcher->matches('เขต', 'เขต'));
    }

    public function testANameThatStartsWithTheLettersOfAPrefixIsKept(): void
    {
        $this->assertSame('อุทัย', $this->matcher->normalize('อุทัย'));
    }
}
