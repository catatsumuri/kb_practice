import { Head, setLayoutProps } from '@inertiajs/react';
import {
    edit,
    index,
    show,
    translate,
    update,
} from '@/actions/App/Http/Controllers/DocumentController';
import { DocumentForm } from '@/components/document-form';
import { DocumentRevisions } from '@/components/document-revisions';
import { SourceFreshness } from '@/components/source-freshness';

import type { Document, DocumentRevision, DocumentSourceSnapshot } from '@/types';
type EditDocumentProps = {
    document: Pick<
        Document,
        | 'id'
        | 'title'
        | 'content'
        | 'visibility'
        | 'source_title'
        | 'source_url'
        | 'source_author'
        | 'source_content'
    > & {
        adopted_source_snapshot: DocumentSourceSnapshot | null;
    };
    pendingSnapshot: DocumentSourceSnapshot | null;
    revisions: DocumentRevision[];
};

export default function EditDocument({
    document,
    pendingSnapshot,
    revisions,
}: EditDocumentProps) {
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

            <main className="grid gap-4 p-4">
                {document.source_url && (
                    <SourceFreshness
                        document={document}
                        pendingSnapshot={pendingSnapshot}
                    />
                )}

                <DocumentRevisions document={document} revisions={revisions} />

                <DocumentForm
                    title="記事の編集"
                    description="タイトルとMarkdown形式の本文を編集できます。"
                    form={update.form(document.id)}
                    cancelHref={show(document.id)}
                    submitLabel="更新"
                    defaultValues={document}
                    translateUrl={translate.url(document.id)}
                />
            </main>
        </>
    );
}
