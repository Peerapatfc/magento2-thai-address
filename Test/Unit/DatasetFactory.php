<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit;

use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;
use Peerapatfc\ThaiAddress\Model\Dataset;

/**
 * Builds a Dataset that reads the data file shipped with the module.
 */
final class DatasetFactory
{
    public static function create(): Dataset
    {
        return new Dataset(
            new Json(),
            new File(),
            new class implements ComponentRegistrarInterface {
                public function getPaths($type): array
                {
                    return [];
                }

                public function getPath($type, $componentName): ?string
                {
                    return dirname(__DIR__, 2);
                }
            }
        );
    }
}
