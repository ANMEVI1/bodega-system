<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class InputGuard
{
    /**
     * Escape SQL LIKE wildcards (%, _, \) so user search terms cannot trigger wildcard abuse.
     */
    public static function escapeLike(string $value): string
    {
        return addcslashes(self::cleanText($value), '\\%_');
    }

    /**
     * Strip null bytes and dangerous ASCII control characters while preserving valid newlines/tabs.
     */
    public static function cleanText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return trim($cleaned);
    }

    /**
     * Validate a person's name: allows letters (including Spanish accents/ñ), spaces, dots, hyphens, and apostrophes.
     * Blocks digits and SQL/script characters.
     */
    public static function normalizePersonName(?string $raw, string $field = 'name'): string
    {
        $cleaned = preg_replace('/\s+/u', ' ', self::cleanText($raw)) ?? '';

        if ($cleaned === '') {
            throw ValidationException::withMessages([
                $field => 'Ingresa un nombre válido.',
            ]);
        }

        if (preg_match('/\d/u', $cleaned) === 1) {
            throw ValidationException::withMessages([
                $field => 'El nombre no puede contener números.',
            ]);
        }

        if (preg_match('/\A\p{L}[\p{L}\s\.\-\']*\z/u', $cleaned) !== 1 || str_contains($cleaned, '--')) {
            throw ValidationException::withMessages([
                $field => 'El nombre solo admite letras y espacios (sin números ni símbolos especiales).',
            ]);
        }

        return $cleaned;
    }

    /**
     * Validate and normalize a Peruvian mobile number into 519XXXXXXXX (11 digits).
     * Blocks letters, SQL injection tokens, and incorrect lengths.
     */
    public static function normalizePeruPhone(?string $raw, bool $required = true, string $field = 'phone'): ?string
    {
        $trimmed = self::cleanText($raw);

        if ($trimmed === '' || $trimmed === '+51' || $trimmed === '51') {
            if (! $required) {
                return null;
            }

            throw ValidationException::withMessages([
                $field => 'Ingresa un número celular válido de Perú de 9 dígitos después del +51 (ej. +51987654321).',
            ]);
        }

        if (preg_match('/\A(?:\+?\s*51)?[\s\-()]*[0-9\s\-()]+\z/', $trimmed) !== 1) {
            throw ValidationException::withMessages([
                $field => 'El campo teléfono solo permite números y el prefijo +51 (sin letras ni símbolos especiales).',
            ]);
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '51')) {
            $local = substr($digits, 2);
        } elseif (strlen($digits) === 9) {
            $local = $digits;
        } else {
            throw ValidationException::withMessages([
                $field => 'El teléfono de Perú debe tener exactamente 9 dígitos después de +51 (ej. +51987654321).',
            ]);
        }

        if (preg_match('/\A9[0-9]{8}\z/', $local) !== 1) {
            throw ValidationException::withMessages([
                $field => 'El celular peruano debe iniciar con 9 y tener 9 dígitos (+519XXXXXXXX).',
            ]);
        }

        return '51'.$local;
    }
}
