<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Resolves the active interface language.
 *
 * English is the source language of the application, so it needs no catalogue:
 * every English label is used verbatim. Indonesian is the shipped translation
 * and the configured default locale.
 */
class Locale
{
    /** @var list<string> */
    public const SUPPORTED = ['id', 'en'];

    public static function normalize(?string $locale): ?string
    {
        if ($locale === null) {
            return null;
        }

        $locale = strtolower(trim($locale));
        // Accept regional variants such as "id_ID" or "en-US".
        $base = str_replace('_', '-', $locale);
        $base = explode('-', $base)[0];

        return in_array($base, self::SUPPORTED, true) ? $base : null;
    }

    public static function current(Request $request): string
    {
        $session = $request->hasSession() ? $request->session() : null;

        return self::normalize($session?->get('locale'))
            ?? self::normalize(config('app.locale'))
            ?? 'id';
    }

    public static function apply(Request $request): void
    {
        App::setLocale(self::current($request));
    }

    public static function alternatives(): array
    {
        return array_values(array_diff(self::SUPPORTED, [App::getLocale()]));
    }
}
