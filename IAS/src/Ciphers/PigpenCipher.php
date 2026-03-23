<?php

namespace CipherLab\Ciphers;

/**
 * PigpenCipher
 *
 * Substitution cipher mapping each letter to a geometric symbol
 * (represented as ASCII-art text codes for terminal/web display).
 *
 * No key required — implements CipherInterface only.
 *
 * SOLID:
 *  (S) One job: Pigpen encode/decode logic only
 *  (L) Fully substitutable wherever CipherInterface is expected
 */
class PigpenCipher extends AbstractCipher
{
    private const ENCODE_MAP = [
        'A' => '[._]', 'B' => '[_.]', 'C' => '[..]',
        'D' => '<._>', 'E' => '<_.>', 'F' => '<..>',
        'G' => '(._)', 'H' => '(_.)', 'I' => '(..)',
        'J' => '{._}', 'K' => '{_.}', 'L' => '{..}',
        'M' => '/._\\','N' => '/_.\\','O' => '/..\\ ',
        'P' => '|._|', 'Q' => '|_.|', 'R' => '|..|',
        'S' => '+._+', 'T' => '+_.+', 'U' => '+..+',
        'V' => '*._*', 'W' => '*_.*', 'X' => '*..*',
        'Y' => '~._~', 'Z' => '~_.~',
    ];

    private const WORD_SEP = '  /  ';

    // Decode map is the reverse of ENCODE_MAP, built once on first use
    private ?array $decodeMap = null;

    // ── CipherInterface ────────────────────────────────────────────────

    public function getId(): string          { return 'pigpen'; }
    public function getName(): string        { return 'Pigpen Cipher'; }
    public function getIcon(): string        { return '✏️'; }
    public function getDescription(): string { return 'Encodes letters as geometric symbols using a grid system. No key needed.'; }

    public function encrypt(string $text): string
    {
        $result = '';
        foreach (str_split(strtoupper($text)) as $char) {
            if (isset(self::ENCODE_MAP[$char])) {
                $result .= self::ENCODE_MAP[$char] . ' ';
            } elseif ($char === ' ') {
                $result .= self::WORD_SEP;
            } else {
                $result .= $char;
            }
        }
        return trim($result);
    }

    public function decrypt(string $text): string
    {
        $map    = $this->getDecodeMap();
        $text   = str_replace(self::WORD_SEP, ' ', $text);
        $result = '';

        foreach (explode(' ', trim($text)) as $token) {
            $token  = trim($token);
            $result .= ($token !== '') ? ($map[$token] ?? $token) : '';
        }
        return $result;
    }

    // ── Private helpers ────────────────────────────────────────────────

    private function getDecodeMap(): array
    {
        if ($this->decodeMap === null) {
            $this->decodeMap = array_flip(self::ENCODE_MAP);
        }
        return $this->decodeMap;
    }
}
