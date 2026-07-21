import { Head, setLayoutProps } from '@inertiajs/react';
import {
    edit,
    index,
    show,
    update,
} from '@/actions/App/Http/Controllers/DocumentController';
import { DocumentForm } from '@/components/document-form';

import type { Document } from '@/types';
type EditDocumentProps = {
    document: Pick<Document, 'id' | 'title' | 'content'>;
};

export default function EditDocument({ document }: EditDocumentProps) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'ドキュメント',
                href: index(),
            },
            {
                title: document.title,
                href: show(document.id),
            },
            {
                title: '編集',
                href: edit(document.id),
            },
        ],
    });

    return (
        <>
            <Head title={`${document.title}を編集`} />

            <main className="p-4">
                <DocumentForm
                    title="記事の編集"
                    description="タイトルとMarkdown形式の本文を編集できます。"
                    form={update.form(document.id)}
                    cancelHref={show(document.id)}
                    submitLabel="更新"
                    defaultValues={document}
                />
            </main>
        </>
    );
}
