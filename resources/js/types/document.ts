import type { User } from './auth';

export type DocumentVisibility = 'private' | 'public' | 'unlisted';

export type DocumentType = 'original' | 'translation';

export type Document = {
    id: number;
    title: string;
    content: string;
    visibility: DocumentVisibility;
    document_type: DocumentType;
    source_title: string | null;
    source_url: string | null;
    source_author: string | null;
    source_content: string | null;
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
