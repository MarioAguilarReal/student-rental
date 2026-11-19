import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

export type Translations = Record<string, string>;
export type Replacements = Record<string, string | number>;

/**
 * Mirrors Laravel's __() for JSON translations: the key is the English source text,
 * lang/{locale}.json holds the translation and `:name` placeholders are replaced.
 * Missing keys fall back to the English text, so the UI never shows a raw key.
 */
export function translate(
    translations: Translations,
    key: string,
    replacements: Replacements = {},
): string {
    let text = translations[key] ?? key;

    // Laravel-style plurals: "1 persona|:count personas", chosen by the :count replacement.
    if (text.includes('|') && typeof replacements.count === 'number') {
        const [singular, plural] = text.split('|');
        text =
            Math.abs(replacements.count) === 1
                ? singular
                : (plural ?? singular);
    }

    // Longest placeholders first, so ":count" never clobbers ":counter".
    const names = Object.keys(replacements).sort((a, b) => b.length - a.length);

    for (const name of names) {
        text = text.replaceAll(`:${name}`, String(replacements[name]));
    }

    return text;
}

export function useTranslation() {
    const { translations, locale } = usePage().props;

    const t = useCallback(
        (key: string, replacements?: Replacements) =>
            translate(translations, key, replacements),
        [translations],
    );

    return { t, locale };
}
