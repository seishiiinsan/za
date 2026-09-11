<?php

namespace App\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;

/**
 * Mot de passe à usage unique basé sur le temps (TOTP, RFC 6238).
 *
 * Implémenté ici plutôt que tiré d'une dépendance : l'algorithme tient en
 * quelques lignes et le secret ne quitte jamais l'application.
 */
class TwoFactor
{
    public const DIGITS = 6;

    public const PERIOD = 30;

    /** Tolérance : une période avant et une après, pour les horloges décalées. */
    public const WINDOW = 1;

    protected const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** Secret partagé, en base32. */
    public function generateSecret(int $length = 32): string
    {
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::ALPHABET[random_int(0, 31)];
        }

        return $secret;
    }

    /** URI otpauth:// à coller dans une application d'authentification. */
    public function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer).':'.rawurlencode($account).'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);
    }

    /**
     * QR code de l'URI, en SVG inline.
     *
     * Rendu côté serveur : le secret ne part pas vers un service tiers pour
     * être mis en image.
     */
    public function qrCodeSvg(string $uri, int $size = 200): string
    {
        $writer = new Writer(new ImageRenderer(
            new RendererStyle($size, 1, null, null, Fill::uniformColor(
                new Rgb(10, 10, 10),      // fond, accordé au thème sombre
                new Rgb(237, 233, 254),   // modules
            )),
            new SvgImageBackEnd,
        ));

        // Le prologue XML empêcherait l'insertion directe dans la page.
        return trim(preg_replace('/<\?xml.*?\?>/', '', $writer->writeString($uri)) ?? '');
    }

    public function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{'.self::DIGITS.'}$/', $code)) {
            return false;
        }

        $counter = intdiv($timestamp ?? time(), self::PERIOD);

        for ($offset = -self::WINDOW; $offset <= self::WINDOW; $offset++) {
            // hash_equals : la comparaison ne doit pas dépendre du code fourni.
            if (hash_equals($this->codeAt($secret, $counter + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    public function codeAt(string $secret, int $counter): string
    {
        $hash = hash_hmac('sha1', pack('J', $counter), $this->decodeBase32($secret), true);
        $offset = ord($hash[19]) & 0x0F;

        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** @return array<int, string> */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    protected function decodeBase32(string $secret): string
    {
        $bits = '';

        foreach (str_split(strtoupper($secret)) as $character) {
            $index = strpos(self::ALPHABET, $character);

            if ($index !== false) {
                $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
            }
        }

        $binary = '';

        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $binary .= chr((int) bindec($byte));
            }
        }

        return $binary;
    }
}
