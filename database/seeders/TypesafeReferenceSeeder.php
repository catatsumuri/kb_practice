<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TypesafeReferenceSeeder extends Seeder
{
    use WithoutModelEvents;

    private const NAVIGATION = [
        [
            'title' => 'Reference',
            'pages' => [
                'models',
                'api',
                'agent-skill',
                'legal',
                [
                    'title' => 'Model jaggedness',
                    'pages' => [
                        'model-jaggedness/jev-1.13',
                    ],
                ],
            ],
        ],
        [
            'title' => 'Client SDKs',
            'pages' => [
                'sdk',
                [
                    'title' => 'Python SDK',
                    'pages' => [
                        'sdk/python',
                        'sdk/python/usage',
                        'sdk/python/changelog',
                        [
                            'title' => 'API reference',
                            'pages' => [
                                'sdk/python/api',
                                [
                                    'title' => 'Clients',
                                    'pages' => [
                                        'sdk/python/api/clients/async',
                                        'sdk/python/api/clients/sync',
                                    ],
                                ],
                                [
                                    'title' => 'Types',
                                    'pages' => [
                                        'sdk/python/api/types/questions',
                                        'sdk/python/api/types/responses',
                                        'sdk/python/api/retries',
                                        'sdk/python/api/types/common',
                                    ],
                                ],
                                'sdk/python/api/exceptions',
                                'sdk/python/api/constants',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'JavaScript SDK',
                    'pages' => [
                        'sdk/javascript',
                        'sdk/javascript/changelog',
                        [
                            'title' => 'API reference',
                            'pages' => [
                                'sdk/javascript/api',
                                [
                                    'title' => 'Classes',
                                    'pages' => [
                                        'sdk/javascript/api/classes/APIConnectionError',
                                        'sdk/javascript/api/classes/APIError',
                                        'sdk/javascript/api/classes/APIPromise',
                                        'sdk/javascript/api/classes/APITimeoutError',
                                        'sdk/javascript/api/classes/APIUserAbortError',
                                        'sdk/javascript/api/classes/AuthenticationError',
                                        'sdk/javascript/api/classes/BadRequestError',
                                        'sdk/javascript/api/classes/InternalServerError',
                                        'sdk/javascript/api/classes/NotFoundError',
                                        'sdk/javascript/api/classes/PermissionDeniedError',
                                        'sdk/javascript/api/classes/RateLimitError',
                                        'sdk/javascript/api/classes/TypeSafeClient',
                                        'sdk/javascript/api/classes/TypeSafeError',
                                        'sdk/javascript/api/classes/UnprocessableEntityError',
                                    ],
                                ],
                                [
                                    'title' => 'Interfaces',
                                    'pages' => [
                                        'sdk/javascript/api/interfaces/ChoiceQuestion',
                                        'sdk/javascript/api/interfaces/ChoiceResponse',
                                        'sdk/javascript/api/interfaces/Logger',
                                        'sdk/javascript/api/interfaces/ModelCard',
                                        'sdk/javascript/api/interfaces/Models',
                                        'sdk/javascript/api/interfaces/NoulQuestion',
                                        'sdk/javascript/api/interfaces/NoulResponse',
                                        'sdk/javascript/api/interfaces/Questions',
                                        'sdk/javascript/api/interfaces/RequestOptions',
                                        'sdk/javascript/api/interfaces/RetryPolicy',
                                        'sdk/javascript/api/interfaces/ScoreQuestion',
                                        'sdk/javascript/api/interfaces/ScoreResponse',
                                        'sdk/javascript/api/interfaces/SystemOneRequest',
                                        'sdk/javascript/api/interfaces/SystemOneRequestPayload',
                                        'sdk/javascript/api/interfaces/SystemOneResult',
                                        'sdk/javascript/api/interfaces/TypeSafeClientConfig',
                                        'sdk/javascript/api/interfaces/Usage',
                                        'sdk/javascript/api/interfaces/WithResponse',
                                    ],
                                ],
                                [
                                    'title' => 'Type Aliases',
                                    'pages' => [
                                        'sdk/javascript/api/type-aliases/ChoiceCriteria',
                                        'sdk/javascript/api/type-aliases/Description',
                                        'sdk/javascript/api/type-aliases/EntryType',
                                        'sdk/javascript/api/type-aliases/EnvVar',
                                        'sdk/javascript/api/type-aliases/Fetch',
                                        'sdk/javascript/api/type-aliases/JsonValue',
                                        'sdk/javascript/api/type-aliases/LogLevel',
                                        'sdk/javascript/api/type-aliases/Question',
                                        'sdk/javascript/api/type-aliases/ResultFor',
                                        'sdk/javascript/api/type-aliases/ScoreCriteria',
                                        'sdk/javascript/api/type-aliases/ScoreLegend',
                                        'sdk/javascript/api/type-aliases/ScoreOf',
                                    ],
                                ],
                                [
                                    'title' => 'Variables',
                                    'pages' => [
                                        'sdk/javascript/api/variables/ENV',
                                        'sdk/javascript/api/variables/LOG_LEVELS',
                                        'sdk/javascript/api/variables/VERSION',
                                    ],
                                ],
                                [
                                    'title' => 'Functions',
                                    'pages' => [
                                        'sdk/javascript/api/functions/choice',
                                        'sdk/javascript/api/functions/noul',
                                        'sdk/javascript/api/functions/score',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    private const SOURCE_PAGES = [
        'models' => 'Models',
        'api' => 'API reference',
        'agent-skill' => 'Agent skill',
        'legal' => 'Legal',
        'model-jaggedness/jev-1.13' => 'Jev 1.13 jaggedness',
        'sdk' => 'Client SDKs',
        'sdk/python' => 'TypeSafe Python SDK',
        'sdk/python/usage' => 'Usage',
        'sdk/python/changelog' => 'Changelog',
        'sdk/python/api' => 'API reference',
        'sdk/python/api/clients/async' => 'Asynchronous client',
        'sdk/python/api/clients/sync' => 'Synchronous client',
        'sdk/python/api/types/questions' => 'Questions',
        'sdk/python/api/types/responses' => 'Answers and responses',
        'sdk/python/api/retries' => 'Retries',
        'sdk/python/api/types/common' => 'Common types',
        'sdk/python/api/exceptions' => 'Exceptions',
        'sdk/python/api/constants' => 'Constants',
        'sdk/javascript' => 'JavaScript SDK',
        'sdk/javascript/changelog' => 'Changelog',
        'sdk/javascript/api' => 'API reference',
        'sdk/javascript/api/classes/APIConnectionError' => 'Class: APIConnectionError',
        'sdk/javascript/api/classes/APIError' => 'Class: APIError',
        'sdk/javascript/api/classes/APIPromise' => 'Class: APIPromise<T>',
        'sdk/javascript/api/classes/APITimeoutError' => 'Class: APITimeoutError',
        'sdk/javascript/api/classes/APIUserAbortError' => 'Class: APIUserAbortError',
        'sdk/javascript/api/classes/AuthenticationError' => 'Class: AuthenticationError',
        'sdk/javascript/api/classes/BadRequestError' => 'Class: BadRequestError',
        'sdk/javascript/api/classes/InternalServerError' => 'Class: InternalServerError',
        'sdk/javascript/api/classes/NotFoundError' => 'Class: NotFoundError',
        'sdk/javascript/api/classes/PermissionDeniedError' => 'Class: PermissionDeniedError',
        'sdk/javascript/api/classes/RateLimitError' => 'Class: RateLimitError',
        'sdk/javascript/api/classes/TypeSafeClient' => 'Class: TypeSafeClient',
        'sdk/javascript/api/classes/TypeSafeError' => 'Class: TypeSafeError',
        'sdk/javascript/api/classes/UnprocessableEntityError' => 'Class: UnprocessableEntityError',
        'sdk/javascript/api/interfaces/ChoiceQuestion' => 'Interface: ChoiceQuestion<T>',
        'sdk/javascript/api/interfaces/ChoiceResponse' => 'Interface: ChoiceResponse<T>',
        'sdk/javascript/api/interfaces/Logger' => 'Interface: Logger',
        'sdk/javascript/api/interfaces/ModelCard' => 'Interface: ModelCard',
        'sdk/javascript/api/interfaces/Models' => 'Interface: Models',
        'sdk/javascript/api/interfaces/NoulQuestion' => 'Interface: NoulQuestion',
        'sdk/javascript/api/interfaces/NoulResponse' => 'Interface: NoulResponse',
        'sdk/javascript/api/interfaces/Questions' => 'Interface: Questions',
        'sdk/javascript/api/interfaces/RequestOptions' => 'Interface: RequestOptions',
        'sdk/javascript/api/interfaces/RetryPolicy' => 'Interface: RetryPolicy',
        'sdk/javascript/api/interfaces/ScoreQuestion' => 'Interface: ScoreQuestion<T>',
        'sdk/javascript/api/interfaces/ScoreResponse' => 'Interface: ScoreResponse<T>',
        'sdk/javascript/api/interfaces/SystemOneRequest' => 'Interface: SystemOneRequest<Q>',
        'sdk/javascript/api/interfaces/SystemOneRequestPayload' => 'Interface: SystemOneRequestPayload',
        'sdk/javascript/api/interfaces/SystemOneResult' => 'Interface: SystemOneResult<Q>',
        'sdk/javascript/api/interfaces/TypeSafeClientConfig' => 'Interface: TypeSafeClientConfig',
        'sdk/javascript/api/interfaces/Usage' => 'Interface: Usage',
        'sdk/javascript/api/interfaces/WithResponse' => 'Interface: WithResponse<T>',
        'sdk/javascript/api/type-aliases/ChoiceCriteria' => 'Type Alias: ChoiceCriteria',
        'sdk/javascript/api/type-aliases/Description' => 'Type Alias: Description',
        'sdk/javascript/api/type-aliases/EntryType' => 'Type Alias: EntryType',
        'sdk/javascript/api/type-aliases/EnvVar' => 'Type Alias: EnvVar',
        'sdk/javascript/api/type-aliases/Fetch' => 'Type Alias: Fetch',
        'sdk/javascript/api/type-aliases/JsonValue' => 'Type Alias: JsonValue',
        'sdk/javascript/api/type-aliases/LogLevel' => 'Type Alias: LogLevel',
        'sdk/javascript/api/type-aliases/Question' => 'Type Alias: Question',
        'sdk/javascript/api/type-aliases/ResultFor' => 'Type Alias: ResultFor<T>',
        'sdk/javascript/api/type-aliases/ScoreCriteria' => 'Type Alias: ScoreCriteria',
        'sdk/javascript/api/type-aliases/ScoreLegend' => 'Type Alias: ScoreLegend<T>',
        'sdk/javascript/api/type-aliases/ScoreOf' => 'Type Alias: ScoreOf<T>',
        'sdk/javascript/api/variables/ENV' => 'Variable: ENV',
        'sdk/javascript/api/variables/LOG_LEVELS' => 'Variable: LOG_LEVELS',
        'sdk/javascript/api/variables/VERSION' => 'Variable: VERSION',
        'sdk/javascript/api/functions/choice' => 'Function: choice()',
        'sdk/javascript/api/functions/noul' => 'Function: noul()',
        'sdk/javascript/api/functions/score' => 'Function: score()',
    ];

    /**
     * Add English reference and SDK pages without replacing existing translations.
     */
    public function run(): void
    {
        $namespace = DocumentNamespace::query()->where('slug', 'typesafe')->firstOrFail();
        $navigation = $namespace->navigation ?? [];

        foreach (self::NAVIGATION as $group) {
            $index = array_search($group['title'], array_column($navigation, 'title'), true);

            if ($index === false) {
                $navigation[] = $group;
            } else {
                $navigation[$index] = $group;
            }
        }

        $namespace->update(['navigation' => $navigation]);

        foreach (self::SOURCE_PAGES as $path => $sourceTitle) {
            $sourceFile = 'typesafe-'.str_replace('/', '-', $path).'-source.md';
            $sourceContent = File::get(database_path('seeders/'.$sourceFile));

            $document = Document::query()->firstOrCreate(
                [
                    'document_namespace_id' => $namespace->id,
                    'path' => $path,
                ],
                [
                    'user_id' => $namespace->owner_user_id,
                    'title' => $sourceTitle,
                    'content' => $sourceContent,
                    'visibility' => DocumentVisibility::Public,
                    'document_type' => DocumentType::Translation,
                    'source_title' => $sourceTitle,
                    'source_url' => "{$namespace->source_url}/{$path}.md",
                    'canonical_url' => "{$namespace->source_url}/{$path}",
                    'source_author' => 'TypeSafe',
                    'source_content' => $sourceContent,
                ],
            );

            if ($document->wasRecentlyCreated) {
                $snapshot = $document->sourceSnapshots()->create([
                    'content' => $sourceContent,
                    'content_hash' => hash('sha256', $sourceContent),
                    'title' => $sourceTitle,
                    'fetched_at' => now(),
                ]);

                $document->update(['document_source_snapshot_id' => $snapshot->id]);
            }
        }
    }
}
