import type { DocumentVisibility } from '@/types';

export const visibilityLabels: Record<DocumentVisibility, string> = {
    private: '非公開',
    unlisted: '限定公開',
    public: '公開',
};
