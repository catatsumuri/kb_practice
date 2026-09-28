import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { index } from '@/actions/App/Http/Controllers/DocumentController';
import {
    create as createNamespace,
    show as showNamespace,
} from '@/actions/App/Http/Controllers/DocumentNamespaceController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import type { DocumentNamespaceListItem } from '@/types';
type DocumentsProps = {
    namespaces: Pick<
        DocumentNamespaceListItem,
        'id' | 'slug' | 'name' | 'documents_count'
    >[];
};

export default function Documents({
    namespaces: namespaceList,
}: DocumentsProps) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'ドキュメント',
                href: index(),
            },
        ],
    });

    return (
        <>
            <Head title="ドキュメント" />
            <main className="p-4">
                <section className="mb-8">
                    <div className="mb-4 flex items-center justify-between gap-4">
                        <h2 className="text-lg font-semibold">
                            ネームスペース
                        </h2>
                        <Button asChild variant="outline">
                            <Link href={createNamespace()}>
                                新規ネームスペース
                            </Link>
                        </Button>
                    </div>
                    {namespaceList.length === 0 ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    ネームスペースはまだありません
                                </CardTitle>
                                <CardDescription>
                                    新規ネームスペースから最初のネームスペースを作成できます。
                                </CardDescription>
                            </CardHeader>
                        </Card>
                    ) : (
                        <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {namespaceList.map((namespace) => (
                                <li key={namespace.id}>
                                    <Link href={showNamespace(namespace.slug)}>
                                        <Card className="h-full transition-colors hover:bg-muted/50">
                                            <CardHeader>
                                                <CardTitle>
                                                    {namespace.name}
                                                </CardTitle>
                                                <CardDescription>
                                                    /{namespace.slug}
                                                </CardDescription>
                                                <CardDescription className="flex items-center gap-1">
                                                    <FileText className="size-4" />
                                                    {namespace.documents_count}
                                                </CardDescription>
                                            </CardHeader>
                                        </Card>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </main>
        </>
    );
}
