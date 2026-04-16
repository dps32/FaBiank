<?php

namespace App\Services;

use InvalidArgumentException;

class TwoFactorService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    // generar secret para 2fa
    public function generateSecret(int $length = 32): string
    {
        $alphabet = self::BASE32_ALPHABET;
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $secret;
    }

    // uri para el otpauth, que será el valor del qr
    public function buildOtpAuthUri(string $secret, string $accountName, string $issuer): string
    {
        $encodedIssuer = rawurlencode($issuer);
        $encodedAccount = rawurlencode($accountName);

        return "otpauth://totp/{$encodedIssuer}:{$encodedAccount}?secret={$secret}&issuer={$encodedIssuer}&algorithm=SHA1&digits=6&period=30";
    }

    // generar el url del qr
    public function buildQrCodeUrl(string $otpauthUri): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data='.rawurlencode($otpauthUri);
    }

    // verificar un código 2fa
    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        if (!$this->isValidSecret($secret)) {
            return false;
        }

        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timeSlice = (int) floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->generateTotp($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    public function hasConfiguredSecret(?string $secret): bool
    {
        return is_string($secret) && trim($secret) !== '';
    }

    public function isValidSecret(?string $secret): bool
    {
        return is_string($secret) && preg_match('/^[A-Z2-7]{16,}$/', $secret) === 1;
    }

    // generar el código totp para un secret
    private function generateTotp(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0).pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $binary =
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);

        $otp = $binary % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    // base32 a bytes
    private function base32Decode(string $input): string
    {
        $cleanInput = strtoupper(preg_replace('/[^A-Z2-7]/', '', $input) ?? '');

        if ($cleanInput === '') {
            throw new InvalidArgumentException('Secret Base32 invalido.');
        }

        $alphabet = array_flip(str_split(self::BASE32_ALPHABET));
        $bits = '';

        foreach (str_split($cleanInput) as $char) {
            if (!array_key_exists($char, $alphabet)) {
                throw new InvalidArgumentException('Secret Base32 invalido.');
            }

            $bits .= str_pad(decbin($alphabet[$char]), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        $chunks = str_split($bits, 8);

        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 8) {
                continue;
            }

            $bytes .= chr(bindec($chunk));
        }

        return $bytes;
    }
}
