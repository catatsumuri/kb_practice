<?php

return [
    /*
     * Top-level route segments that a namespace slug must never collide
     * with, since namespace URLs live under /documents/{namespace}/...
     */
    'reserved_slugs' => [
        // 'create' is also reserved for /namespaces/create (the creation
        // form route), which would otherwise be unreachable for a
        // namespace literally slugged "create" now that namespace URLs
        // are keyed by slug.
        'create',
        // 'fetch-source' is GET /documents/fetch-source (the AI
        // source-fetch action) — reserved for hygiene, though the two
        // never actually conflict since that route is POST-only.
        'fetch-source',
        'dashboard',
        'documents',
        'settings',
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password',
        'email',
        'user',
        'two-factor-challenge',
        'passkeys',
        'up',
        'storage',
        '_boost',
        '.well-known',
        'namespaces',
    ],

    /*
     * Reserved values for a document's own path/slug within its
     * namespace. "create" is reserved because documents/{namespace}/create
     * is registered as a literal route (the article-creation form) ahead
     * of documents/{namespace}/{path}, so a document path of exactly
     * "create" would be permanently unreachable via its pretty URL.
     */
    'reserved_paths' => [
        'create',
    ],
];
