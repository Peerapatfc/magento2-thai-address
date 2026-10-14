<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Setup\Patch\Data;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Setup\Patch\Data\MigrateProvinceMatchSetting;

class MigrateProvinceMatchSettingTest extends TestCase
{
    private AdapterInterface&MockObject $connection;

    private function patch(array $legacyRows, array $levelRows): MigrateProvinceMatchSetting
    {
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->connection->method('fetchAll')->willReturnCallback(
            fn (string $sql) => str_contains($sql, 'value FROM') ? $legacyRows : $levelRows
        );
        $setup = $this->createMock(ModuleDataSetupInterface::class);
        $setup->method('getConnection')->willReturn($this->connection);
        $setup->method('getTable')->willReturnArgument(0);

        return new MigrateProvinceMatchSetting($setup);
    }

    public function testNoBecomesPostcodeAndYesBecomesProvinceAtEachScope(): void
    {
        $patch = $this->patch(
            [
                ['scope' => 'default', 'scope_id' => '0', 'value' => '0'],
                ['scope' => 'websites', 'scope_id' => '2', 'value' => '1'],
            ],
            []
        );
        $inserted = [];
        $this->connection->method('insert')->willReturnCallback(function (string $table, array $row) use (&$inserted) {
            $inserted[] = $row;

            return 1;
        });
        $this->connection->expects($this->once())->method('delete')
            ->with('core_config_data', ['path = ?' => 'thai_address/validation/enforce_province_match']);

        $patch->apply();

        $this->assertSame(
            [
                ['scope' => 'default', 'scope_id' => '0', 'path' => 'thai_address/validation/level', 'value' => 'postcode'],
                ['scope' => 'websites', 'scope_id' => '2', 'path' => 'thai_address/validation/level', 'value' => 'province'],
            ],
            $inserted
        );
    }

    public function testAScopeThatAlreadyHasAStrictnessKeepsIt(): void
    {
        $patch = $this->patch(
            [['scope' => 'default', 'scope_id' => '0', 'value' => '0']],
            [['scope' => 'default', 'scope_id' => '0']]
        );
        $this->connection->expects($this->never())->method('insert');
        $this->connection->expects($this->once())->method('delete');

        $patch->apply();
    }

    public function testNothingHappensWhenTheOldSettingWasNeverSaved(): void
    {
        $patch = $this->patch([], []);
        $this->connection->expects($this->never())->method('insert');
        $this->connection->expects($this->never())->method('delete');

        $patch->apply();
    }
}
