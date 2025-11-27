<?php

namespace App\Shared\Infrastructure\Security;

use App\Shared\Domain\Service\UuidGeneratorInterface;
use Exception;

class UuidGenerator implements UuidGeneratorInterface
{

    /**
     * @return string
     */
    public function generate(): string
    {
        return $this->generateUuidV7();
    }

    /**
     * @param string $uuid
     * @return bool
     */
    public function isValid(string $uuid): bool
    {
        return $this->validateV7($uuid);
    }

    /**
     * @return string
     */
    private function generateUuidV7(): string
    {
        $ts = (int) floor(microtime(true) * 1000.0);
        $ts48 = $ts & 0xFFFFFFFFFFFF;

        $time_low = ($ts48 >> 16) & 0xFFFFFFFF;
        $time_mid = $ts48 & 0xFFFF;

        $rand = $this->randomHex(10);

        $rand12 = hexdec(substr($rand, 0, 3)) & 0x0FFF;
        $rand16 = hexdec(substr($rand, 3, 4)) & 0xFFFF;
        $nodeHex = substr($rand, 7, 12);

        $version = 7;
        $time_hi_and_version = ($version << 12) | ($rand12 & 0x0FFF);

        $rand14 = $rand16 & 0x3FFF;

        $clock_seq_hi = (($rand14 >> 8) & 0x3F) | 0x80;
        $clock_seq_low = $rand14 & 0xFF;

        return sprintf(
            '%08x-%04x-%04x-%02x%02x-%012s',
            $time_low,
            $time_mid,
            $time_hi_and_version,
            $clock_seq_hi,
            $clock_seq_low,
            strtolower($nodeHex)
        );
    }

    /**
     * @param string $uuid
     * @return bool
     */
    private function validateV7(string $uuid): bool
    {
        if (strlen($uuid) !== 36) {
            return false;
        }

        if ($uuid[8] !== '-' ||
            $uuid[13] !== '-' ||
            $uuid[18] !== '-' ||
            $uuid[23] !== '-') {
            return false;
        }

        $u = strtolower($uuid);

        if ($u[14] !== '7') {
            return false;
        }

        if (!in_array($u[19], ['8', '9', 'a', 'b'], true)) {
            return false;
        }

        for ($i = 0; $i < 36; $i++) {
            if ($i === 8 || $i === 13 || $i === 18 || $i === 23) {
                continue;
            }
            $c = $u[$i];
            if (!(($c >= '0' && $c <= '9') || ($c >= 'a' && $c <= 'f'))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $bytes
     * @return string
     */
    private function randomHex(int $bytes): string
    {
        try {
            return bin2hex(random_bytes($bytes));
        } catch (Exception $_) {
        }

        $hex = '';
        for ($i = 0; $i < $bytes; $i++) {
            $hex .= sprintf('%02x', mt_rand(0, 255));
        }

        return $hex;
    }
}