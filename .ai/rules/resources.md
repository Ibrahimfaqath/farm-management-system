---
paths:
  - 'resources/**/*.{php,css,js}'
---

# Resources

## English is the source language for UI text
UI text uses English as the translation key via `__()`, with Indonesian only in `lang/id.json`. There is no `lang/en.json`: an untranslated English label renders as itself. Default locale stays `id`. Apply `__()` to enum labels via a single match arm so status badges, dropdowns and tables all translate from one place. The active language lives in the session (PUT /locale, `App\Support\Locale`) and is applied by `App\Http\Middleware\ApplyLocale`, which must stay inside the web middleware group so the session is readable.
