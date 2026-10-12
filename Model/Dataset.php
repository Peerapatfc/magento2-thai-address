<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Thai provinces, districts and sub-districts keyed by postal code, with their official codes.
 *
 * The data is read once per request from data/thai-addresses.json. See THIRD_PARTY_LICENSES.md for its source.
 */
class Dataset
{
    public const COUNTRY_ID = 'TH';

    /** @var array<string, array{th: string, en: string}>|null province code => names */
    private ?array $provinces = null;

    /** @var array<int, array{th: string, en: string}>|null district code => names */
    private ?array $districts = null;

    /** @var array<string, list<array<string, int|string>>>|null */
    private ?array $byPostcode = null;

    /**
     * @param Json $json
     * @param File $file
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param string|null $dataFile Override the data file, for tests
     */
    public function __construct(
        private readonly Json $json,
        private readonly File $file,
        private readonly ComponentRegistrarInterface $componentRegistrar,
        private readonly ?string $dataFile = null
    ) {
    }

    /**
     * All provinces keyed by their official province code.
     *
     * @return array<int, array{th: string, en: string}>
     */
    public function getProvinces(): array
    {
        $this->load();

        return $this->provinces;
    }

    /**
     * The sub-districts that use a postal code, or an empty list when the code does not exist.
     *
     * Each row has province_code, district_code, district, district_en, subdistrict_code, subdistrict and
     * subdistrict_en. The names without a suffix are Thai.
     *
     * @param string $postcode
     * @return list<array<string, int|string>>
     */
    public function findByPostcode(string $postcode): array
    {
        $this->load();

        return $this->byPostcode[$postcode] ?? [];
    }

    /**
     * Where the module is installed (app/code or vendor).
     *
     * @return string
     */
    private function moduleDirectory(): string
    {
        return (string)$this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'Peerapatfc_ThaiAddress');
    }

    /**
     * Reads the data file once.
     *
     * @return void
     */
    private function load(): void
    {
        if ($this->byPostcode !== null) {
            return;
        }

        $path = $this->dataFile ?? $this->moduleDirectory() . '/data/thai-addresses.json';
        $data = $this->json->unserialize($this->file->fileGetContents($path));

        $this->provinces = [];
        foreach ($data['provinces'] as $code => $names) {
            $this->provinces[(int)$code] = $names;
        }

        $this->districts = [];
        foreach ($data['districts'] as $code => $names) {
            $this->districts[(int)$code] = $names;
        }

        $this->byPostcode = [];
        foreach ($data['entries'] as [$postcode, $subdistrictCode, $subdistrictTh, $subdistrictEn]) {
            $districtCode = intdiv((int)$subdistrictCode, 100);
            $district = $this->districts[$districtCode];
            $this->byPostcode[(string)$postcode][] = [
                'province_code' => intdiv($districtCode, 100),
                'district_code' => $districtCode,
                'district' => $district['th'],
                'district_en' => $district['en'],
                'subdistrict_code' => (int)$subdistrictCode,
                'subdistrict' => $subdistrictTh,
                'subdistrict_en' => $subdistrictEn,
            ];
        }
    }
}
