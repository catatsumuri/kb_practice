import type { User } from './auth';
import type { DocumentNamespace } from './document-namespace';

export type DocumentVisibility = 'private' | 'public';

export type DocumentType = 'original' | 'translation';

export type DocumentSourceSnapshot = {
    id: number;
    document_id: number;
    content: string;
    content_hash: string;
    title: string | null;
    fetched_at: string;
};

export type DocumentRevision = {
    id: number;
    document_id: number;
    title: string;
    content: string;
    created_at: string;
    user: Pick<User, 'id' | 'name'> | null;
};

export type Document = {
    id: number;
    title: string;
    content: string;
    visibility: DocumentVisibility;
    document_type: DocumentType;
    source_title: string | null;
    source_url: string | null;
    canonical_url: string | null;
    source_author: string | null;
    source_content: string | null;
    document_namespace_id: number | null;
    document_source_snapshot_id: number | null;
    path: string | null;
    created_at: string;
};

export type DocumentWithUser = Document & {
    user: Pick<User, 'id' | 'name'>;
    namespace: Pick<DocumentNamespace, 'id' | 'slug' | 'name'> | null;
};

export type DocumentPermissions = {
    update: boolean;
    delete: boolean;
};

export type DocumentNavItem = Pick<Document, 'id' | 'title' | 'path'>;

export type DocumentNavNode = {
    title: string | null;
    document: DocumentNavItem | null;
    label: string | null;
    children: DocumentNavNode[];
};
