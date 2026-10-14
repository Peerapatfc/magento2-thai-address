<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Carries the 0.1.0 setting "Province must match the postal code" over to "Strictness".
 *
 * Yes becomes "Postal code and province must match", No becomes "Postal code must exist", at every scope where the
 * old setting was saved. A scope that already has a Strictness value keeps it. The old rows are then removed.
 */
class MigrateProvinceMatchSetting implements DataPatchInterface
{
    private const LEGACY_PATH = 'thai_address/validation/enforce_province_match';
    private const LEVEL_PATH = 'thai_address/validation/level';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(private readonly ModuleDataSetupInterface $moduleDataSetup)
    {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('core_config_data');

        $legacy = $connection->fetchAll(
            "SELECT scope, scope_id, value FROM {$table} WHERE path = ?",
            [self::LEGACY_PATH]
        );
        if ($legacy === []) {
            return $this;
        }

        $taken = [];
        $levels = $connection->fetchAll("SELECT scope, scope_id FROM {$table} WHERE path = ?", [self::LEVEL_PATH]);
        foreach ($levels as $row) {
            $taken[$row['scope'] . ':' . $row['scope_id']] = true;
        }

        foreach ($legacy as $row) {
            if (isset($taken[$row['scope'] . ':' . $row['scope_id']])) {
                continue;
            }

            $connection->insert($table, [
                'scope' => $row['scope'],
                'scope_id' => $row['scope_id'],
                'path' => self::LEVEL_PATH,
                'value' => (string)$row['value'] === '0' ? 'postcode' : 'province',
            ]);
        }

        $connection->delete($table, ['path = ?' => self::LEGACY_PATH]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
