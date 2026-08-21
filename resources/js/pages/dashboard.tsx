import { lang } from '@erag/lang-sync-inertia/react';
import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import { show } from '@/actions/App/Http/Controllers/DocumentController';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { DocumentWithUser } from '@/types';

type DashboardProps = {
    documents: (Pick<
        DocumentWithUser,
        'id' | 'title' | 'created_at' | 'user'
    > & { likes_count: number })[];
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
                                    href={show(document.id)}
                                    prefetch
                                >
                                    <Card className="h-full transition-colors hover:bg-muted/50">
                                        <CardHeader className="flex-row items-start justify-between gap-4">
                                            <div className="grid gap-1.5">
                                                <CardTitle className="line-clamp-2 leading-snug">
                                                    {document.title}
                                                </CardTitle>
                                                <CardDescription className="flex flex-wrap gap-x-3 gap-y-1">
                                                    <span>
                                                        作成者：
                                                        {document.user.name}
                                                    </span>
                                                    <time
                                                        dateTime={
                                                            document.created_at
                                                        }
                                                    >
                                                        {formatDate(
                                                            document.created_at,
                                                        )}
                                                    </time>
                                                </CardDescription>
                                            </div>
                                            <div className="flex shrink-0 items-center gap-1 text-sm text-muted-foreground">
                                                <Heart className="size-4" />
                                                {document.likes_count}
                                            </div>
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
