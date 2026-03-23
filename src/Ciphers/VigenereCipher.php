<?php

namespace CipherLab\Ciphers;

use CipherLab\Contracts\KeyedCipherInterface;
use InvalidArgumentException;

/**
 * VigenereCipher
 *
 * Polyalphabetic substitution cipher using a repeating keyword.
 *   Encrypt: C_i = (P_i + K_i) mod 26
 *   Decrypt: P_i = (C_i − K_i) mod 26
 *
 * Non-alpha characters are preserved unchanged.
 *
 * SOLID:
 *  (S) Only Vigenère logic; keyword cycling is a private helper
 *  (L) Substitutable as KeyedCipherInterface
 */
class VigenereCipher extends AbstractCipher implements KeyedCipherInterface
{
    private string $keyword = '';

    // ── KeyedCipherInterface ───────────────────────────────────────────

    public function setParams(array $params): void
    {
        $kw = $this->alphaOnly($params['key'] ?? '');
        if ($kw === '') {
            throw new InvalidArgumentException('Vigenère requires a non-empty keyword (letters only).');
        }
        $this->keyword = $kw;
    }

    public function getFormFields(): array
    {
        return [
            [
                'name'        => 'key',
                'label'       => 'Keyword',
                'type'        => 'text',
                'placeholder' => 'e.g. SECRET',
                'default'     => '',
                'hint'        => 'The keyword repeats over the message. Spaces and punctuation are preserved.',
            ],
        ];
    }

    // ── CipherInterface ────────────────────────────────────────────────

    public function getId(): string          { return 'vigenere'; }
    public function getName(): string        { return 'Vigenère Cipher'; }
    public function getIcon(): string        { return '🗝️'; }
    public function getDescription(): string { return 'Polyalphabetic cipher using a repeating keyword shift.'; }

    public function encrypt(string $text): string
    {
        return $this->applyShift($text, direction: +1);
    }

    public function decrypt(string $text): string
    {
        return $this->applyShift($text, direction: -1);
    }

    // ── Private helpers ────────────────────────────────────────────────

    /**
     * Apply keyword shift to all alpha characters.
     * @param int $direction  +1 encrypt, -1 decrypt
     */
    private function applyShift(string $text, int $direction): string
    {
        $keyLen = strlen($this->keyword);
        $result = '';
        $ki     = 0;   // advances only for alpha characters

        foreach (str_split(strtoupper($text)) as $char) {
            if (ctype_alpha($char)) {
                $shift  = ord($this->keyword[$ki % $keyLen]) - 65;
                $result .= chr($this->mod26((ord($char) - 65) + $direction * $shift) + 65);
                $ki++;
            } else {
                $result .= $char;   // preserve spaces, punctuation, digits
            }
        }
        return $result;
    }
}
