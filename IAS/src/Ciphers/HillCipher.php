<?php

namespace CipherLab\Ciphers;

use CipherLab\Contracts\KeyedCipherInterface;
use InvalidArgumentException;

/**
 * HillCipher
 *
 * Polygraphic substitution using a 2×2 integer key matrix.
 *   Encrypt: C = K · P (mod 26)
 *   Decrypt: P = K⁻¹ · C (mod 26)
 *
 * SOLID:
 *  (S) Hill math only; matrix inversion is a private concern
 *  (O) A 3×3 variant could subclass this without changing it
 *  (L) Substitutable as KeyedCipherInterface / CipherInterface
 */
class HillCipher extends AbstractCipher implements KeyedCipherInterface
{
    /** @var int[][] Default 2×2 key matrix */
    private array $matrix = [[3, 3], [2, 5]];

    // ── KeyedCipherInterface ───────────────────────────────────────────

    public function setParams(array $params): void
    {
        $raw  = $params['hill_key'] ?? '3 3 2 5';
        $nums = array_values(array_filter(
            preg_split('/[\s,]+/', trim($raw)),
            'is_numeric'
        ));

        if (count($nums) < 4) {
            throw new InvalidArgumentException('Hill key needs exactly 4 integers (e.g. "3 3 2 5").');
        }

        $this->matrix = [
            [(int)$nums[0], (int)$nums[1]],
            [(int)$nums[2], (int)$nums[3]],
        ];
    }

    public function getFormFields(): array
    {
        return [
            [
                'name'        => 'hill_key',
                'label'       => 'Key Matrix — 4 integers (a b c d) forming [[a,b],[c,d]]',
                'type'        => 'text',
                'placeholder' => 'e.g. 3 3 2 5',
                'default'     => '3 3 2 5',
                'hint'        => 'Det must be coprime with 26. Non-alpha chars are replaced with X.',
            ],
        ];
    }

    // ── CipherInterface ────────────────────────────────────────────────

    public function getId(): string          { return 'hill'; }
    public function getName(): string        { return 'Hill Cipher'; }
    public function getIcon(): string        { return '🔢'; }
    public function getDescription(): string { return 'Matrix-based polygraphic substitution cipher using a 2×2 key matrix.'; }

    public function encrypt(string $text): string
    {
        return $this->applyMatrix($this->prepareText($text), $this->matrix);
    }

    public function decrypt(string $text): string
    {
        return $this->applyMatrix($this->prepareText($text), $this->invertMatrix($this->matrix));
    }

    // ── Private helpers ────────────────────────────────────────────────

    private function prepareText(string $text): string
    {
        $text = strtoupper(preg_replace('/[^a-zA-Z]/', 'X', $text));
        if (strlen($text) % 2 !== 0) {
            $text .= 'X';
        }
        return $text;
    }

    private function applyMatrix(string $text, array $m): string
    {
        $result = '';
        for ($i = 0; $i < strlen($text); $i += 2) {
            $a      = ord($text[$i])     - 65;
            $b      = ord($text[$i + 1]) - 65;
            $result .= chr($this->mod26($m[0][0] * $a + $m[0][1] * $b) + 65);
            $result .= chr($this->mod26($m[1][0] * $a + $m[1][1] * $b) + 65);
        }
        return $result;
    }

    /** Compute K⁻¹ mod 26 for a 2×2 matrix. */
    private function invertMatrix(array $m): array
    {
        $det    = $m[0][0] * $m[1][1] - $m[0][1] * $m[1][0];
        $detInv = $this->modInverse($this->mod26($det));

        if ($detInv === -1) {
            throw new InvalidArgumentException(
                'Key matrix determinant has no inverse mod 26. Choose a different key.'
            );
        }

        return [
            [$this->mod26( $detInv * $m[1][1]),  $this->mod26(-$detInv * $m[0][1])],
            [$this->mod26(-$detInv * $m[1][0]),  $this->mod26( $detInv * $m[0][0])],
        ];
    }
}
