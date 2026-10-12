<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

/**
 * Decides whether free text such as "จ.เชียงใหม่", "กทม." or "Bangkok" names a given province.
 */
class ProvinceMatcher
{
    private const BANGKOK_CODE = 10;

    /** Common spellings of Bangkok that are not its official name. */
    private const BANGKOK_ALIASES = ['กรุงเทพ', 'กรุงเทพฯ', 'กทม', 'bangkok', 'bkk', 'krungthep'];

    /**
     * @param Dataset $dataset
     */
    public function __construct(private readonly Dataset $dataset)
    {
    }

    /**
     * Whether the text names the province with this code.
     *
     * @param string $input
     * @param int $provinceCode
     * @return bool
     */
    public function matches(string $input, int $provinceCode): bool
    {
        $needle = $this->normalize($input);
        if ($needle === '') {
            return false;
        }

        if ($provinceCode === self::BANGKOK_CODE) {
            foreach (self::BANGKOK_ALIASES as $alias) {
                if ($this->normalize($alias) === $needle) {
                    return true;
                }
            }
        }

        $names = $this->dataset->getProvinces()[$provinceCode] ?? null;
        if ($names === null) {
            return false;
        }

        return $this->normalize($names['th']) === $needle || $this->normalize($names['en']) === $needle;
    }

    /**
     * Finds the province code for free text, or null.
     *
     * @param string $input
     * @return int|null
     */
    public function findCode(string $input): ?int
    {
        foreach (array_keys($this->dataset->getProvinces()) as $code) {
            if ($this->matches($input, $code)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Lower-cases, drops "จังหวัด"/"จ."/"Province" and removes spaces and punctuation.
     *
     * @param string $input
     * @return string
     */
    public function normalize(string $input): string
    {
        $text = mb_strtolower(trim($input));
        $text = (string)preg_replace('/^(จังหวัด|จ\.)\s*/u', '', $text);
        $text = (string)preg_replace('/\s*(province|metropolis|city)\s*$/u', '', $text);

        return (string)preg_replace('/[\s.\-ฯ]/u', '', $text);
    }
}
