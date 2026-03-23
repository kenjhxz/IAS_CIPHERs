<?php

namespace CipherLab\Ciphers;

use CipherLab\Contracts\KeyedCipherInterface;
use InvalidArgumentException;

/**
 * PlayfairCipher
 *
 * Digraph substitution cipher using a 5×5 keyword-derived table.
 * Rules:
 *   Same row    → shift columns right (+1) / left (-1)
 *   Same column → shift rows down (+1) / up (-1)
 *   Rectangle   → swap columns (same for both directions)
 *
 * SOLID:
 *  (S) Only Playfair logic; table construction is a private responsibility
 *  (L) Substitutable as KeyedCipherInterface
 */
class PlayfairCipher extends AbstractCipher implements KeyedCipherInterface
{
    private string $keyword = 'KEYWORD';

    // ── KeyedCipherInterface ───────────────────────────────────────────

    public function setParams(array $params): void
    {
        $kw = trim($params['key'] ?? '');
        if ($kw === '') {
            throw new InvalidArgumentException('Playfair requires a keyword.');
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
                'placeholder' => 'e.g. MONARCHY',
                'default'     => '',
                'hint'        => 'J is treated as I. Builds a 5×5 substitution table.',
            ],
        ];
    }

    // ── CipherInterface ────────────────────────────────────────────────

    public function getId(): string          { return 'playfair'; }
    public function getName(): string        { return 'Playfair Cipher'; }
    public function getIcon(): string        { return '🔲'; }
    public function getDescription(): string { return 'Digraph substitution cipher using a 5×5 keyword matrix.'; }

    public function encrypt(string $text): string
    {
        return $this->process($text, shift: +1);
    }

    public function decrypt(string $text): string
    {
        return $this->process($text, shift: -1);
    }

    // ── Private helpers ────────────────────────────────────────────────

    /** Shared engine: +1 = encrypt, -1 = decrypt. */
    private function process(string $text, int $shift): string
    {
        $table  = $this->buildTable($this->keyword);
        $text   = $this->prepareDigraphs($text);
        $result = '';

        for ($i = 0; $i < strlen($text); $i += 2) {
            [$ac, $ar] = $this->findPos($table, $text[$i]);
            [$bc, $br] = $this->findPos($table, $text[$i + 1]);

            if ($ar === $br) {
                $result .= $table[$ar * 5 + ($ac + $shift + 5) % 5];
                $result .= $table[$br * 5 + ($bc + $shift + 5) % 5];
            } elseif ($ac === $bc) {
                $result .= $table[(($ar + $shift + 5) % 5) * 5 + $ac];
                $result .= $table[(($br + $shift + 5) % 5) * 5 + $bc];
            } else {
                $result .= $table[$ar * 5 + $bc];
                $result .= $table[$br * 5 + $ac];
            }
        }
        return $result;
    }

    private function buildTable(string $keyword): array
    {
        $src  = str_replace('J', 'I', $this->alphaOnly($keyword));
        $seen = [];
        $tbl  = [];

        foreach (str_split($src) as $c) {
            if (!isset($seen[$c])) { $seen[$c] = true; $tbl[] = $c; }
        }
        for ($i = 65; $i <= 90; $i++) {
            $c = chr($i);
            if ($c === 'J') continue;
            if (!isset($seen[$c])) { $seen[$c] = true; $tbl[] = $c; }
        }
        return $tbl;
    }

    /** Prepare plaintext: alpha-only, J→I, split to digraphs, pad. */
    private function prepareDigraphs(string $text): string
    {
        $text   = str_replace('J', 'I', $this->alphaOnly($text));
        $result = '';
        $i      = 0;
        while ($i < strlen($text)) {
            $a = $text[$i];
            $b = $text[$i + 1] ?? 'X';
            if ($a === $b) { $result .= $a . 'X'; $i++; }
            else           { $result .= $a . $b;  $i += 2; }
        }
        if (strlen($result) % 2 !== 0) $result .= 'X';
        return $result;
    }

    /** Return [col, row] of a character in the 5×5 table. */
    private function findPos(array $table, string $char): array
    {
        $idx = array_search($char, $table, true);
        return [$idx % 5, intdiv($idx, 5)];
    }
}
