export type Document = {
    id: number;
    title: string;
    content: string;
    created_at: string;
};

export type DocumentWithUser = Document & {
    user: Pick<User, 'id' | 'name'>;
};
