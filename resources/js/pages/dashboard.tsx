import { lang } from '@erag/lang-sync-inertia/react';
import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { DocumentMeta } from '@/components/document-meta';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { documentHref } from '@/lib/document';
import { dashboard } from '@/routes';
import type { DocumentWithUser } from '@/types';

type DashboardProps = {
    documents: Pick<
        DocumentWithUser,
        'id' | 'path' | 'title' | 'created_at' | 'user' | 'namespace'
    >[];
};

export default function Dashboard({ documents }: DashboardProps) {
    const { __ } = lang();

    setLayoutProps({
        breadcrumbs: [
            {
                title: __('Dashboard'),
                href: dashboard(),
            },
        ],
    });

    return (
        <>
            <Head title={__('Dashboard')} />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="grid gap-2">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        公開ドキュメント
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        すべてのユーザーが公開しているドキュメントの一覧です。
                    </p>
                </div>

                {documents.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                公開ドキュメントはまだありません
                            </CardTitle>
                            <CardDescription>
                                ドキュメントが公開されると、ここに表示されます。
                            </CardDescription>
                        </CardHeader>
                    </Card>
                ) : (
                    <ul className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {documents.map((document) => (
                            <li key={document.id}>
                                <Link
                                    className="block h-full"
                                    href={documentHref(document)}
                                    prefetch
                                >
                                    <Card className="h-full transition-colors hover:bg-muted/50">
                                        <CardHeader className="grid gap-1.5">
                                            <CardTitle className="line-clamp-2 leading-snug">
                                                {document.title}
                                            </CardTitle>
                                            <DocumentMeta
                                                author={document.user.name}
                                                createdAt={document.created_at}
                                            />
                                        </CardHeader>
                                    </Card>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </main>
        </>
    );
}
