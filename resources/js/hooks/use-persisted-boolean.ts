import { useEffect, useState } from 'react';

// The initial render must match the server-rendered markup exactly (SSR
// runs without access to the browser's localStorage), so state always
// starts at `defaultValue` and only picks up the stored value once mounted
// in the browser, after hydration has already settled.
export function usePersistedBoolean(key: string, defaultValue: boolean) {
    const [value, setValue] = useState(defaultValue);

    useEffect(() => {
        try {
            const stored = localStorage.getItem(key);

            if (stored !== null) {
                setValue(stored === 'true');
            }
        } catch {
            // Storage may be unavailable (private browsing, disabled, etc.);
            // fall back to the default for this session.
        }
    }, [key]);

    const setPersistedValue = (
        next: boolean | ((previous: boolean) => boolean),
    ) => {
        setValue((previous) => {
            const resolved = typeof next === 'function' ? next(previous) : next;

            try {
                localStorage.setItem(key, String(resolved));
            } catch {
                // Ignore write failures; the toggle still works this session.
            }

            return resolved;
        });
    };

    return [value, setPersistedValue] as const;
}
