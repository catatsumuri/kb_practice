import { Head, setLayoutProps } from '@inertiajs/react';
import {
    edit,
    index,
    translate,
    update,
} from '@/actions/App/Http/Controllers/DocumentController';
import { show as showNamespace } from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import { DocumentForm } from '@/components/document-form';
import { DocumentRevisions } from '@/components/document-revisions';
import { SourceFreshness } from '@/components/source-freshness';
import { documentHref } from '@/lib/document';

import type {
    Document,
    DocumentRevision,
    DocumentSourceSnapshot,
} from '@/types';
type EditDocumentProps = {
    document: Pick<
        Document,
        | 'id'
        | 'path'
        | 'title'
        | 'content'
        | 'visibility'
        | 'source_title'
        | 'source_url'
        | 'source_author'
        | 'source_content'
    > & {
        adopted_source_snapshot: DocumentSourceSnapshot | null;
        namespace: { slug: string; name: string } | null;
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
            ...(document.namespace
                ? [
                      {
                          title: document.namespace.name,
                          href: showNamespace(document.namespace.slug),
                      },
                  ]
                : []),
            {
                title: document.title,
                href: documentHref(document),
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
                    cancelHref={documentHref(document)}
                    submitLabel="更新"
                    defaultValues={document}
                    translateUrl={translate.url(document.id)}
                />
            </main>
        </>
    );
}
