import type { User } from './auth';

export type DocumentVisibility = 'private' | 'public' | 'unlisted';

export type Document = {
    id: number;
    title: string;
    content: string;
    visibility: DocumentVisibility;
    created_at: string;
};

export type DocumentWithUser = Document & {
    user: Pick<User, 'id' | 'name'>;
};

export type DocumentListItem = DocumentWithUser & {
    likes_count: number;
};

export type DocumentPermissions = {
    update: boolean;
    delete: boolean;
};
