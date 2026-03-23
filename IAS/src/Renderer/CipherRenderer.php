<?php

namespace CipherLab\Renderer;

use CipherLab\Contracts\CipherInterface;
use CipherLab\Contracts\KeyedCipherInterface;
use CipherLab\Registry\CipherRegistry;

/**
 * CipherRenderer
 *
 * Responsible ONLY for turning cipher/registry data into HTML strings.
 * No cipher logic, no routing, no POST handling — only rendering.
 *
 * SOLID:
 *  (S) Single Responsibility — HTML output only
 *  (D) Dependency Inversion  — depends on CipherInterface, not concretions
 */
class CipherRenderer
{
    public function __construct(private readonly CipherRegistry $registry) {}

    // ── Public render methods ──────────────────────────────────────────

    /** Render the top menu grid of cipher cards. */
    public function renderMenu(string $activeCipherId): string
    {
        $html = '<div class="cipher-grid">';
        foreach ($this->registry->all() as $cipher) {
            $active = $cipher->getId() === $activeCipherId ? ' active' : '';
            $id     = htmlspecialchars($cipher->getId());
            $html  .= <<<HTML
                <div class="cipher-card{$active}" onclick="selectCipher('{$id}')">
                    <span class="cipher-icon">{$cipher->getIcon()}</span>
                    <div class="cipher-label">{$cipher->getName()}</div>
                    <div class="cipher-desc">{$cipher->getDescription()}</div>
                </div>
            HTML;
        }
        $html .= '</div>';
        return $html;
    }

    /** Render all form panels (only the active one is visible). */
    public function renderPanels(
        string $activeCipherId,
        string $activePlaintext,
        string $output,
        string $error,
        string $activeAction,
        array  $postParams
    ): string {
        $html = '';
        foreach ($this->registry->all() as $cipher) {
            $html .= $this->renderPanel(
                $cipher,
                $activeCipherId,
                $activePlaintext,
                $output,
                $error,
                $activeAction,
                $postParams
            );
        }
        return $html;
    }

    // ── Private helpers ────────────────────────────────────────────────

    private function renderPanel(
        CipherInterface $cipher,
        string $activeCipherId,
        string $activePlaintext,
        string $output,
        string $error,
        string $activeAction,
        array  $postParams
    ): string {
        $id      = htmlspecialchars($cipher->getId());
        $isActive = $cipher->getId() === $activeCipherId;
        $visible  = $isActive ? ' visible' : '';

        $fields       = $this->renderFormFields($cipher, $isActive, $postParams);
        $plaintextVal = $isActive ? htmlspecialchars($activePlaintext) : '';
        $resultBlock  = $isActive ? $this->renderResult($output, $error, $activeAction) : '';

        return <<<HTML
            <div class="panel{$visible}" id="panel-{$id}">
                <div class="panel-header">
                    <span class="panel-icon">{$cipher->getIcon()}</span>
                    <div>
                        <div class="panel-title">{$cipher->getName()}</div>
                        <div class="panel-subtitle">{$cipher->getDescription()}</div>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Plaintext / Ciphertext</label>
                        <textarea name="plaintext" placeholder="Enter your message here...">{$plaintextVal}</textarea>
                    </div>
                    {$fields}
                </div>
                <div class="action-row">
                    <button type="submit" class="btn btn-encrypt" onclick="setAction('encrypt')">🔒 Encrypt</button>
                    <button type="submit" class="btn btn-decrypt" onclick="setAction('decrypt')">🔓 Decrypt</button>
                    <button type="button" class="btn btn-clear"   onclick="clearForm()">✕ Clear</button>
                </div>
                {$resultBlock}
            </div>
        HTML;
    }

    /** Render the dynamic key fields for a keyed cipher, or a hint for keyless ones. */
    private function renderFormFields(CipherInterface $cipher, bool $isActive, array $postParams): string
    {
        if (!($cipher instanceof KeyedCipherInterface)) {
            return '<div class="note full"><strong>No key required.</strong> Encrypt then paste the output back to Decrypt to round-trip.</div>';
        }

        $html = '';
        foreach ($cipher->getFormFields() as $field) {
            $name   = htmlspecialchars($field['name']);
            $label  = htmlspecialchars($field['label']);
            $type   = htmlspecialchars($field['type']);
            $hint   = htmlspecialchars($field['hint'] ?? '');
            $ph     = htmlspecialchars($field['placeholder'] ?? '');
            $val    = htmlspecialchars($isActive
                ? ($postParams[$field['name']] ?? $field['default'] ?? '')
                : ($field['default'] ?? ''));

            $input  = ($type === 'number')
                ? "<input type=\"number\" name=\"{$name}\" value=\"{$val}\" min=\"0\" max=\"25\">"
                : "<input type=\"text\"   name=\"{$name}\" value=\"{$val}\" placeholder=\"{$ph}\">";

            $html .= <<<HTML
                <div class="form-group full">
                    <label>{$label}</label>
                    {$input}
                    <span class="field-hint">{$hint}</span>
                </div>
            HTML;
        }
        return $html;
    }

    /** Render the output/error block below the form. */
    private function renderResult(string $output, string $error, string $action): string
    {
        if ($error !== '') {
            return <<<HTML
                <div class="output-box visible">
                    <div class="output-label">Error</div>
                    <div class="output-text error-text">{$error}</div>
                </div>
            HTML;
        }
        if ($output !== '') {
            $label = strtoupper($action) . 'ED OUTPUT';
            $safe  = htmlspecialchars($output);
            return <<<HTML
                <div class="output-box visible">
                    <div class="output-label">{$label}</div>
                    <div class="output-text">{$safe}</div>
                </div>
            HTML;
        }
        return '';
    }
}
