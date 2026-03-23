<?php

namespace CipherLab\Contracts;

/**
 * CipherInterface
 *
 * Core abstraction that ALL ciphers must implement.
 * High-level code (index.php, renderer) depends ONLY on this — never on concrete classes.
 *
 * SOLID:
 *  (D) Dependency Inversion  — depend on abstractions, not concretions
 *  (L) Liskov Substitution   — any cipher can replace any other cipher here
 *  (I) Interface Segregation — minimal; keyed ciphers extend via KeyedCipherInterface
 */
interface CipherInterface
{
    public function getId(): string;
    public function getName(): string;
    public function getIcon(): string;
    public function getDescription(): string;

    public function encrypt(string $text): string;
    public function decrypt(string $text): string;
}
