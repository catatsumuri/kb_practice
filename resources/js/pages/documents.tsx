import { Head, setLayoutProps } from '@inertiajs/react';
import { documents } from '@/routes';

type Document = {
    id: number;
    title: string;
    content: string;
    created_at: string;
};

type DocumentsProps = {
    documents: Document[];
};

function formatDate(date: string) {
    return new Intl.DateTimeFormat('ja-JP', {
        dateStyle: 'long',
        timeZone: 'Asia/Tokyo',
    }).format(new Date(date));
}

export default function Documents({ documents: documentList }: DocumentsProps) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'ドキュメント',
                href: documents(),
            },
        ],
    });

    return (
        <>
            <Head title="ドキュメント" />
            <main>
                <div>
                    {documentList.map((document) => (
                        <article key={document.id}>
                            <h2>{document.title}</h2>
                            <time dateTime={document.created_at}>
                                {formatDate(document.created_at)}
                            </time>
                            <p>{document.content}</p>
                        </article>
                    ))}
                </div>
            </main>
        </>
    );
}
