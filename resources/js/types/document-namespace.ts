export type DocumentNamespace = {
    id: number;
    owner_user_id: number;
    slug: string;
    name: string;
    source_url: string | null;
    created_at: string;
};

export type DocumentNamespaceListItem = DocumentNamespace & {
    documents_count: number;
};
