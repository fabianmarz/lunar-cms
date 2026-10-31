export type Translations = Record<string, string>;

export interface LanguageOption {
    id: number;
    code: string;
    name: string | null;
    default: boolean;
}

// Empty values are dropped from the map rather than stored as '' — the same
// normalisation Lunar's TranslatedInput applies, so the server sees only
// languages that actually have content.
export const withTranslation = (map: Translations | undefined, code: string, value: string): Translations => {
    const next = { ...(map ?? {}) };

    if (value === '') {
        delete next[code];
    } else {
        next[code] = value;
    }

    return next;
};
