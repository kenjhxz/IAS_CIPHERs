<?php

namespace CipherLab\Registry;

use CipherLab\Contracts\CipherInterface;
use InvalidArgumentException;

/**
 * CipherRegistry
 *
 * Central store for all available ciphers.
 * To add a new cipher: just call register() — nothing else changes.
 *
 * SOLID:
 *  (S) Single Responsibility — only manages cipher registration & lookup
 *  (O) Open/Closed           — extend the app by registering, not by modifying
 *  (D) Dependency Inversion  — stores CipherInterface, not concrete classes
 */
class CipherRegistry
{
    /** @var array<string, CipherInterface> */
    private array $ciphers = [];

    public function register(CipherInterface $cipher): void
    {
        $this->ciphers[$cipher->getId()] = $cipher;
    }

    public function get(string $id): CipherInterface
    {
        if (!isset($this->ciphers[$id])) {
            throw new InvalidArgumentException("Unknown cipher: '{$id}'.");
        }
        return $this->ciphers[$id];
    }

    /** @return array<string, CipherInterface> */
    public function all(): array
    {
        return $this->ciphers;
    }

    public function has(string $id): bool
    {
        return isset($this->ciphers[$id]);
    }
}
