<?php

namespace CipherLab\Contracts;

/**
 * KeyedCipherInterface
 *
 * Segregated interface for ciphers that require runtime parameters
 * (keywords, matrices, numeric keys, etc.).
 *
 * Pigpen does NOT implement this — it stays clean without unused methods.
 *
 * SOLID:
 *  (I) Interface Segregation — no cipher is forced to implement what it doesn't need
 *  (O) Open/Closed           — add new keyed ciphers without modifying existing ones
 */
interface KeyedCipherInterface extends CipherInterface
{
    /**
     * Inject POST params into the cipher before encrypt/decrypt is called.
     * @param array<string, mixed> $params  e.g. ['key' => 'SECRET']
     */
    public function setParams(array $params): void;

    /**
     * Describe the HTML form fields this cipher needs.
     * The Renderer reads this — ciphers never render themselves.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFormFields(): array;
}
