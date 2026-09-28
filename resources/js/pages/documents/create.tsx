import { Head, setLayoutProps } from '@inertiajs/react';
import {
    create,
    index,
    store,
} from '@/actions/App/Http/Controllers/DocumentController';
import { show } from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import { DocumentForm } from '@/components/document-form';

import type { FetchedSource } from '@/components/document-form';
import type { DocumentNamespace } from '@/types';

type CreateDocumentProps = {
    namespace: Pick<DocumentNamespace, 'id' | 'slug' | 'name' | 'is_public'>;
    fetchedSource?: FetchedSource | null;
};

export default function CreateDocument({
    namespace,
    fetchedSource,
}: CreateDocumentProps) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'ドキュメント', href: index() },
            { title: namespace.name, href: show(namespace.slug) },
            { title: '新規記事', href: create(namespace.slug) },
        ],
    });

    return (
        <>
            <Head title={`「${namespace.name}」に記事を新規作成`} />

            <main className="p-4">
                <DocumentForm
                    title={`「${namespace.name}」に新規記事を作成`}
                    description="タイトルとMarkdown形式の本文を入力してください。"
                    form={store.form(namespace.slug)}
                    cancelHref={show(namespace.slug)}
                    submitLabel="保存"
                    allowSourceFetch
                    fetchedSource={fetchedSource}
                    namespaceSlug={namespace.slug}
                    defaultVisibility={
                        namespace.is_public ? 'public' : 'private'
                    }
                />
            </main>
        </>
    );
}
