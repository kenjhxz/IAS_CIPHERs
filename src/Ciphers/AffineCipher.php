<?php

namespace CipherLab\Ciphers;

use CipherLab\Contracts\KeyedCipherInterface;
use InvalidArgumentException;

/**
 * AffineCipher
 *
 * Mathematical substitution cipher.
 *   Encrypt: E(x) = (a·x + b) mod 26
 *   Decrypt: D(y) = a⁻¹·(y − b) mod 26
 *
 * SOLID:
 *  (S) Only Affine math; validation is its own private method
 *  (L) Substitutable as KeyedCipherInterface
 */
class AffineCipher extends AbstractCipher implements KeyedCipherInterface
{
    private int $a = 5;
    private int $b = 8;

    // ── KeyedCipherInterface ───────────────────────────────────────────

    public function setParams(array $params): void
    {
        $a = (int)($params['a_val'] ?? 5);
        $b = (int)($params['b_val'] ?? 8);

        if ($this->gcd($a, 26) !== 1) {
            throw new InvalidArgumentException(
                "Key 'a' ({$a}) must be coprime with 26. Valid values: 1,3,5,7,9,11,15,17,19,21,23,25."
            );
        }

        $this->a = $a;
        $this->b = $b;
    }

    public function getFormFields(): array
    {
        return [
            [
                'name'        => 'a_val',
                'label'       => 'Key a — must be coprime with 26',
                'type'        => 'number',
                'placeholder' => '5',
                'default'     => '5',
                'hint'        => 'Valid values: 1, 3, 5, 7, 9, 11, 15, 17, 19, 21, 23, 25',
            ],
            [
                'name'        => 'b_val',
                'label'       => 'Key b — shift value (0–25)',
                'type'        => 'number',
                'placeholder' => '8',
                'default'     => '8',
                'hint'        => 'Any integer 0–25.',
            ],
        ];
    }

    // ── CipherInterface ────────────────────────────────────────────────

    public function getId(): string          { return 'affine'; }
    public function getName(): string        { return 'Affine Cipher'; }
    public function getIcon(): string        { return '📐'; }
    public function getDescription(): string { return 'Mathematical cipher: E(x) = (ax + b) mod 26.'; }

    public function encrypt(string $text): string
    {
        return $this->applyFormula(
            strtoupper($text),
            fn(int $x): int => $this->mod26($this->a * $x + $this->b)
        );
    }

    public function decrypt(string $text): string
    {
        $aInv = $this->modInverse($this->a);
        if ($aInv === -1) {
            throw new InvalidArgumentException("Key 'a' has no modular inverse mod 26.");
        }

        return $this->applyFormula(
            strtoupper($text),
            fn(int $y): int => $this->mod26($aInv * ($y - $this->b))
        );
    }

    // ── Private helpers ────────────────────────────────────────────────

    /** Apply a transformation closure to every alpha character in $text. */
    private function applyFormula(string $text, callable $formula): string
    {
        $result = '';
        foreach (str_split($text) as $char) {
            $result .= ctype_alpha($char)
                ? chr($formula(ord($char) - 65) + 65)
                : $char;
        }
        return $result;
    }
}
