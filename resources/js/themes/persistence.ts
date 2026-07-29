export interface ThemeState {
    primary: string; // color name, e.g. 'noir', 'emerald'
    surface: string; // color name, e.g. 'slate', 'zinc'
}

const STORAGE_KEY = 'app-theme-preference';

const DEFAULT_STATE: ThemeState = { primary: 'noir', surface: 'slate' };

export function loadThemeState(): ThemeState {
    if (typeof window === 'undefined') return DEFAULT_STATE;

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) return DEFAULT_STATE;
        const parsed = JSON.parse(raw);
        return {
            primary: typeof parsed.primary === 'string' ? parsed.primary : DEFAULT_STATE.primary,
            surface: typeof parsed.surface === 'string' ? parsed.surface : DEFAULT_STATE.surface,
        };
    } catch {
        return DEFAULT_STATE;
    }
}

export function saveThemeState(state: ThemeState): void {
    if (typeof window === 'undefined') return;
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch {
        // localStorage can throw in private-browsing/quota-exceeded cases — non-fatal, just skip persisting
    }
}