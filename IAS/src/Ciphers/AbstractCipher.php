<?php

namespace CipherLab\Ciphers;

use CipherLab\Contracts\CipherInterface;

/**
 * AbstractCipher
 *
 * Provides shared mathematical utilities used across multiple ciphers.
 * Contains ZERO cipher-specific logic — only reusable primitives.
 *
 * SOLID:
 *  (S) Single Responsibility — math helpers only, no cipher logic
 *  (O) Open/Closed           — subclasses extend, never modify this class
 *  (L) Liskov Substitution   — all subclasses satisfy CipherInterface
 */
abstract class AbstractCipher implements CipherInterface
{
    /**
     * Modulo that always returns non-negative result.
     * PHP's native % returns negative for negative operands.
     */
    protected function mod26(int $n): int
    {
        return (($n % 26) + 26) % 26;
    }

    /**
     * Greatest Common Divisor via Euclidean algorithm.
     */
    protected function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }
        return abs($a);
    }

    /**
     * Modular multiplicative inverse of $a mod 26.
     * Returns -1 when no inverse exists (i.e. gcd(a, 26) != 1).
     */
    protected function modInverse(int $a, int $m = 26): int
    {
        $a = $this->mod26($a);
        for ($i = 1; $i < $m; $i++) {
            if (($a * $i) % $m === 1) {
                return $i;
            }
        }
        return -1;
    }

    /**
     * Strip non-alpha characters and uppercase the result.
     */
    protected function alphaOnly(string $text): string
    {
        return strtoupper(preg_replace('/[^a-zA-Z]/', '', $text));
    }
}
