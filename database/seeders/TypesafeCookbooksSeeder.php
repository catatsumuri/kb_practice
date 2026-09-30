<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TypesafeCookbooksSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Add TypeSafe cookbook and demo source pages without replacing existing translations.
     */
    public function run(): void
    {
        $namespace = DocumentNamespace::query()->where('slug', 'typesafe')->firstOrFail();

        $cookbookGroups = [
            [
                'title' => '自己一致性',
                'pages' => [
                    ['page' => 'cookbooks/consistency_noul_cookbook', 'label' => '初級'],
                    ['page' => 'cookbooks/consistency_choice_cookbook', 'label' => '初級'],
                ],
            ],
            [
                'title' => 'バッチ処理',
                'pages' => [
                    ['page' => 'cookbooks/parallel_questions', 'label' => '初級'],
                ],
            ],
            [
                'title' => 'How-to',
                'pages' => [
                    ['page' => 'cookbooks/rerank_typesafe', 'label' => '初級'],
                    ['page' => 'cookbooks/semantic_find', 'label' => '初級'],
                    ['page' => 'cookbooks/autoformat', 'label' => '初級'],
                    ['page' => 'cookbooks/function_calling', 'label' => '中級'],
                    ['page' => 'cookbooks/skill_suggestion', 'label' => '中級'],
                    ['page' => 'cookbooks/entity_alignment', 'label' => '初級'],
                    ['page' => 'cookbooks/classifying_rag_passages', 'label' => '中級'],
                    ['page' => 'cookbooks/citation_check', 'label' => '初級'],
                    ['page' => 'cookbooks/llm_guardrails', 'label' => '中級'],
                ],
            ],
            [
                'title' => '抽出',
                'pages' => [
                    ['page' => 'cookbooks/sde_cascade', 'label' => '中級'],
                    ['page' => 'cookbooks/date_extraction_cookbook', 'label' => '初級'],
                    ['page' => 'cookbooks/pre_parsed_value_extraction_cookbook', 'label' => '初級'],
                ],
            ],
            [
                'title' => '分類',
                'pages' => [
                    ['page' => 'cookbooks/hierarchical_classification', 'label' => '中級'],
                    ['page' => 'cookbooks/autoresearch_feature_discovery', 'label' => '上級'],
                    ['page' => 'cookbooks/classification_using_confidence', 'label' => '初級'],
                ],
            ],
        ];

        $navigation = $namespace->navigation ?? [];
        $hasCookbooks = false;
        $hasDemos = false;

        foreach ($navigation as &$entry) {
            if (($entry['page'] ?? null) === 'cookbooks') {
                $entry['pages'] = $cookbookGroups;
                $hasCookbooks = true;
            }

            if (($entry['page'] ?? null) === 'demos') {
                $entry = [
                    'title' => 'デモ',
                    'page' => 'demos',
                    'pages' => ['demos/smart-home'],
                ];
                $hasDemos = true;
            }
        }
        unset($entry);

        if (! $hasCookbooks) {
            $navigation[] = [
                'title' => 'クックブック',
                'page' => 'cookbooks',
                'pages' => $cookbookGroups,
            ];
        }

        if (! $hasDemos) {
            $navigation[] = [
                'title' => 'デモ',
                'page' => 'demos',
                'pages' => ['demos/smart-home'],
            ];
        }

        $namespace->update(['navigation' => $navigation]);

        $sourcePages = [
            ['path' => 'cookbooks/parallel_questions', 'source_title' => 'Parallel questions', 'source_file' => 'typesafe-parallel_questions-source.md'],
            ['path' => 'cookbooks/rerank_typesafe', 'source_title' => 'Re-ranking', 'source_file' => 'typesafe-rerank_typesafe-source.md'],
            ['path' => 'cookbooks/semantic_find', 'source_title' => 'Line-by-line search', 'source_file' => 'typesafe-semantic_find-source.md'],
            ['path' => 'cookbooks/autoformat', 'source_title' => 'Structure recovery', 'source_file' => 'typesafe-autoformat-source.md'],
            ['path' => 'cookbooks/function_calling', 'source_title' => 'Function calling', 'source_file' => 'typesafe-function_calling-source.md'],
            ['path' => 'cookbooks/skill_suggestion', 'source_title' => 'Skill suggestion', 'source_file' => 'typesafe-skill_suggestion-source.md'],
            ['path' => 'cookbooks/entity_alignment', 'source_title' => 'Knowledge graph entity alignment', 'source_file' => 'typesafe-entity_alignment-source.md'],
            ['path' => 'cookbooks/classifying_rag_passages', 'source_title' => 'Classifying RAG passages', 'source_file' => 'typesafe-classifying_rag_passages-source.md'],
            ['path' => 'cookbooks/citation_check', 'source_title' => 'Double-checking citations', 'source_file' => 'typesafe-citation_check-source.md'],
            ['path' => 'cookbooks/llm_guardrails', 'source_title' => 'Guardrails for LLMs', 'source_file' => 'typesafe-llm_guardrails-source.md'],
            ['path' => 'cookbooks/sde_cascade', 'source_title' => 'SDE cascade', 'source_file' => 'typesafe-sde_cascade-source.md'],
            ['path' => 'cookbooks/date_extraction_cookbook', 'source_title' => 'Date extraction', 'source_file' => 'typesafe-date_extraction_cookbook-source.md'],
            ['path' => 'cookbooks/pre_parsed_value_extraction_cookbook', 'source_title' => 'Pre-parsed value extraction', 'source_file' => 'typesafe-pre_parsed_value_extraction_cookbook-source.md'],
            ['path' => 'cookbooks/hierarchical_classification', 'source_title' => 'Hierarchical classification', 'source_file' => 'typesafe-hierarchical_classification-source.md'],
            ['path' => 'cookbooks/autoresearch_feature_discovery', 'source_title' => 'Autoresearch feature discovery', 'source_file' => 'typesafe-autoresearch_feature_discovery-source.md'],
            ['path' => 'cookbooks/classification_using_confidence', 'source_title' => 'Classification using confidence', 'source_file' => 'typesafe-classification_using_confidence-source.md'],
            ['path' => 'demos', 'source_title' => 'Demos', 'source_file' => 'typesafe-demos-source.md'],
            ['path' => 'demos/smart-home', 'source_title' => 'Smart home assistant demo', 'source_file' => 'typesafe-smart-home-source.md'],
        ];

        foreach ($sourcePages as $sourcePage) {
            $sourceContent = File::get(database_path('seeders/'.$sourcePage['source_file']));
            $sourceUrl = "{$namespace->source_url}/{$sourcePage['path']}.md";

            $document = Document::query()->firstOrCreate(
                [
                    'document_namespace_id' => $namespace->id,
                    'path' => $sourcePage['path'],
                ],
                [
                    'user_id' => $namespace->owner_user_id,
                    'title' => $sourcePage['source_title'],
                    'content' => $sourceContent,
                    'visibility' => DocumentVisibility::Public,
                    'document_type' => DocumentType::Translation,
                    'source_title' => $sourcePage['source_title'],
                    'source_url' => $sourceUrl,
                    'canonical_url' => "{$namespace->source_url}/{$sourcePage['path']}",
                    'source_author' => 'TypeSafe',
                    'source_content' => $sourceContent,
                ],
            );

            if ($document->wasRecentlyCreated) {
                $snapshot = $document->sourceSnapshots()->create([
                    'content' => $sourceContent,
                    'content_hash' => hash('sha256', $sourceContent),
                    'title' => $sourcePage['source_title'],
                    'fetched_at' => now(),
                ]);

                $document->update(['document_source_snapshot_id' => $snapshot->id]);
            }
        }
    }
}
