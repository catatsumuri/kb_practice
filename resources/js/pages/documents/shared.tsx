import { Head } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import Markdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { DocumentMeta } from '@/components/document-meta';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import type { DocumentWithUser } from '@/types';

type SharedDocumentProps = {
    document: DocumentWithUser;
    likesCount: number;
};

export default function SharedDocument({
    document,
    likesCount,
}: SharedDocumentProps) {
    return (
        <>
            <Head title={document.title}>
                <meta name="referrer" content="no-referrer" />
                <meta name="robots" content="noindex, nofollow" />
            </Head>

            <Card>
                <CardHeader>
                    <CardTitle>{document.title}</CardTitle>
                    <DocumentMeta
                        author={document.user.name}
                        createdAt={document.created_at}
                        className="items-center"
                    >
                        <span className="inline-flex items-center gap-1">
                            <Heart className="size-4" />
                            {likesCount}
                        </span>
                    </DocumentMeta>
                </CardHeader>
                <CardContent>
                    <div className="markdown-content">
                        <Markdown remarkPlugins={[remarkGfm]}>
                            {document.content}
                        </Markdown>
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
