<?php

declare(strict_types=1);

namespace App\Domain\Ai;

/**
 * Substitusi sederhana `{{ key }}` dan `{{ json:key }}` pada template prompt.
 */
class PromptRenderer
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function render(string $template, array $context): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*(json:)?([a-z0-9_.]+)\s*\}\}/i',
            function (array $m) use ($context): string {
                $value = data_get($context, $m[2]);

                if ($m[1] !== '') {
                    return (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }

                if (is_array($value)) {
                    return implode(', ', array_map('strval', $value));
                }

                return $value === null ? '' : (string) $value;
            },
            $template,
        );
    }
}
