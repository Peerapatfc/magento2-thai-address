<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Peerapatfc\ThaiAddress\Model\Dataset;

/**
 * Adds Thailand's 77 provinces as directory regions, with English and Thai names.
 *
 * The region "code" is the official province code (10 = Bangkok), which is how the rest of the module links a
 * postal code to a region. Safe to re-run: provinces that already exist (same code) are left alone.
 */
class AddThaiRegions implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Dataset $dataset
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly Dataset $dataset
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $regionTable = $this->moduleDataSetup->getTable('directory_country_region');
        $nameTable = $this->moduleDataSetup->getTable('directory_country_region_name');

        $this->moduleDataSetup->startSetup();

        $existing = $connection->fetchCol(
            $connection->select()->from($regionTable, 'code')->where('country_id = ?', Dataset::COUNTRY_ID)
        );

        foreach ($this->dataset->getProvinces() as $code => $names) {
            if (in_array((string)$code, $existing, true)) {
                continue;
            }

            $connection->insert($regionTable, [
                'country_id' => Dataset::COUNTRY_ID,
                'code' => (string)$code,
                'default_name' => $names['en'],
            ]);
            $regionId = (int)$connection->lastInsertId($regionTable);

            $connection->insertMultiple($nameTable, [
                ['locale' => 'en_US', 'region_id' => $regionId, 'name' => $names['en']],
                ['locale' => 'th_TH', 'region_id' => $regionId, 'name' => $names['th']],
            ]);
        }

        $this->moduleDataSetup->endSetup();

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
