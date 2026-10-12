<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

/**
 * Compares district (อำเภอ/เขต) and sub-district (ตำบล/แขวง) names typed by customers with the official ones.
 *
 * The dataset stores the bare name ("บางรัก"); customers write "เขตบางรัก", "อ.เมือง" or leave out tone marks.
 */
class AreaNameMatcher
{
    /** Leading words that say what kind of area it is, not which one. */
    private const PREFIX = '/^(กิ่งอำเภอ|อำเภอ|เขต|แขวง|ตำบล|อ\.|ต\.)\s*/u';

    /**
     * Whether two names refer to the same area.
     *
     * @param string $input What the customer typed
     * @param string $official Name from the dataset
     * @param string|null $officialEn The English name from the dataset, accepted too
     * @return bool
     */
    public function matches(string $input, string $official, ?string $officialEn = null): bool
    {
        $needle = $this->normalize($input);
        if ($needle === '') {
            return false;
        }

        return $needle === $this->normalize($official)
            || ($officialEn !== null && $needle === $this->normalize($officialEn));
    }

    /**
     * Lower-cases, drops "เขต"/"อำเภอ"/"แขวง"/"ตำบล" (and "อ."/"ต."), tone marks, spaces and punctuation.
     *
     * @param string $input
     * @return string
     */
    public function normalize(string $input): string
    {
        $text = mb_strtolower(trim($input));
        $text = (string)preg_replace(self::PREFIX, '', $text);
        $text = (string)preg_replace('/[\x{0E48}-\x{0E4B}]/u', '', $text); // tone marks

        return (string)preg_replace('/[\s.\-ฯ\'’(),]/u', '', $text);
    }
}
