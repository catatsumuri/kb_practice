import { Head, setLayoutProps } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/DocumentController';
import {
    create,
    store,
} from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import { NamespaceForm } from '@/components/namespace-form';

export default function CreateNamespace() {
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
            <Head title="ネームスペースの新規作成" />

            <main className="p-4">
                <NamespaceForm
                    title="ネームスペースの新規作成"
                    description="公開URLの一部となるスラッグと表示名を入力してください。"
                    form={store.form()}
                    cancelHref={index()}
                    submitLabel="保存"
                />
            </main>
        </>
    );
}
