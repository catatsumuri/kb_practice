import { Head, setLayoutProps } from '@inertiajs/react';
import {
    create,
    index,
    store,
} from '@/actions/App/Http/Controllers/DocumentController';
import { DocumentForm } from '@/components/document-form';

export default function CreateDocument() {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'ドキュメント',
                href: index(),
            },
            {
                title: '新規作成',
                href: create(),
            },
        ],
    });

    return (
        <>
            <Head title="記事の新規作成" />

            <main className="p-4">
                <DocumentForm
                    title="記事の新規作成"
                    description="タイトルとMarkdown形式の本文を入力してください。"
                    form={store.form()}
                    cancelHref={index()}
                    submitLabel="保存"
                />
            </main>
        </>
    );
}
