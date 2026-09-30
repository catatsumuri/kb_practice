<?php

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\User;
use Database\Seeders\TypesafeCookbooksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('2人のテストユーザーとTypeSafeドキュメントの翻訳サンプルを作成する', function () {
    $this->seed();

    $users = User::query()->orderBy('id')->get();

    expect($users)->toHaveCount(2)
        ->and(Document::query()->count())->toBe(111);

    $document = Document::query()->where('path', 'introduction')->sole();

    expect($document->content)->toStartWith('# はじめに')
        ->and($document->content)->not->toContain('llms.txt')
        ->and($document->source_content)->toContain('llms.txt');

    expect($document->user_id)->toBe($users->first()->id)
        ->and($document->visibility)->toBe(DocumentVisibility::Public)
        ->and($document->document_type)->toBe(DocumentType::Translation)
        ->and($document->source_title)->toBe('Introduction')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/introduction.md')
        ->and($document->source_author)->toBe('TypeSafe')
        ->and($document->source_content)->not->toBeEmpty();
});

test('quickstartの翻訳済み本文がシードされる', function () {
    $this->seed();

    $document = Document::query()->where('path', 'introduction/quickstart')->sole();

    expect($document->title)->toBe('クイックスタート')
        ->and($document->content)->toStartWith('# クイックスタート')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('coding-agentsの翻訳済み本文がシードされる', function () {
    $this->seed();

    $document = Document::query()->where('path', 'introduction/coding-agents')->sole();

    expect($document->title)->toBe('コーディングエージェントとJev')
        ->and($document->content)->toStartWith('# コーディングエージェントとJev')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/introduction/coding-agents.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/introduction/coding-agents')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('use-case-mapの翻訳済み本文がシードされ、JSX属性は原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'concepts/use-case-map')->sole();

    expect($document->title)->toBe('ユースケース例')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/concepts/use-case-map.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/concepts/use-case-map')
        ->and($document->source_content)->toContain('<AccordionGroup>')
        ->and($document->content)->toStartWith('# ユースケース例')
        ->and($document->content)->toContain('<Columns cols={2}>')
        ->and($document->content)->toContain('<Card title="AI自動化ソフトウェア" icon="blocks">')
        ->and(substr_count($document->content, '<Accordion '))->toBe(19)
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('system-oneの翻訳済み本文がシードされる', function () {
    $this->seed();

    $document = Document::query()->where('path', 'concepts/system-one')->sole();

    expect($document->source_url)->toBe('https://docs.typesafe.ai/concepts/system-one.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/concepts/system-one')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->content)->toStartWith('# System One')
        ->and($document->content)->toContain('フラッグシップモデル')
        ->and(substr_count($document->content, '<Note>'))->toBe(2)
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('stateの翻訳済み本文がシードされ、コードブロックは原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'concepts/state')->sole();

    expect($document->title)->toBe('状態')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/concepts/state.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/concepts/state')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->content)->toStartWith('# 状態')
        ->and($document->content)->toContain('"refund_policy": "二重請求は返金の対象となります。"')
        ->and($document->content)->toContain('{"from": "customer", "text": "注文A-104で2回請求されました。')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('primitivesの翻訳済み本文がシードされ、平坦化したコードブロックとJSX属性は原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'primitives')->sole();

    expect($document->title)->toBe('プリミティブ（質問）')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/primitives.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/primitives')
        ->and($document->source_content)->not->toContain('export function')
        ->and($document->source_content)->not->toContain('TypesafeExample')
        ->and($document->content)->toStartWith('# プリミティブ（質問）')
        ->and($document->content)->toContain('```json title="request" theme={null}')
        ->and($document->content)->toContain('<Columns cols={3}>')
        ->and($document->content)->toContain('<Card title="Score" href="/primitives/score" icon="gauge">')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('primitives/choiceの翻訳済み本文がシードされ、コードブロックは原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'primitives/choice')->sole();

    expect($document->source_url)->toBe('https://docs.typesafe.ai/primitives/choice.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/primitives/choice')
        ->and($document->source_content)->not->toContain('TypesafeExample')
        ->and($document->content)->toStartWith('# Choice')
        ->and($document->content)->toContain('確信度')
        ->and(substr_count($document->content, '```json title='))->toBe(3)
        ->and($document->content)->toContain('"choice": "returns"')
        ->and($document->content)->toContain('"returns": "交換、誤った商品または破損した商品"')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('typesafeのナビゲーションは原本どおりPrimitives系のページをStateとConfidenceの間に並べる', function () {
    $this->seed();

    $concepts = collect(DocumentNamespace::query()->where('slug', 'typesafe')->sole()->navigation)
        ->firstWhere('title', 'コンセプト');

    expect($concepts['pages'])->toBe([
        'concepts/system-one',
        'concepts/state',
        'primitives',
        'primitives/choice',
        'primitives/score',
        'primitives/noul',
        'primitives/advanced',
        'confidence',
        'concepts/how-to-build-with-system-one',
        'introduction/machine-learning-primer',
    ]);
});

test('primitives/scoreの翻訳済み本文がシードされ、HTMLの表はMarkdownの表に平坦化されている', function () {
    $this->seed();

    $document = Document::query()->where('path', 'primitives/score')->sole();

    expect($document->source_url)->toBe('https://docs.typesafe.ai/primitives/score.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/primitives/score')
        ->and($document->source_content)->not->toContain('ScoreExplorer')
        ->and($document->source_content)->not->toContain('<table')
        ->and($document->content)->toStartWith('# Score')
        ->and($document->content)->not->toContain('<table')
        ->and($document->content)->toContain('| 状態 | `score` | `confidence` | `probabilities`：レベル0')
        ->and(substr_count($document->content, '```json title='))->toBe(3)
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('primitives/noulの翻訳済み本文がシードされ、criteriaのキーは原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'primitives/noul')->sole();

    expect($document->source_url)->toBe('https://docs.typesafe.ai/primitives/noul.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/primitives/noul')
        ->and($document->source_content)->not->toContain('TypesafeExample')
        ->and($document->content)->toStartWith('# Noul')
        ->and(substr_count($document->content, '```json title='))->toBe(2)
        ->and($document->content)->toContain('"type": "noul"')
        ->and($document->content)->toContain('"true": "以前の試み')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('primitives/advancedの翻訳済み本文がシードされ、JSONのキーは原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'primitives/advanced')->sole();

    expect($document->title)->toBe('上級編：構造')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/primitives/advanced.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/primitives/advanced')
        ->and($document->source_content)->not->toContain('TypesafeExample')
        ->and($document->content)->toStartWith('# 上級編：構造')
        ->and(substr_count($document->content, '```json title='))->toBe(5)
        ->and($document->content)->toContain('"compare": ["ticket.sender.display_name", "ticket.sender.email"]')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('confidenceの翻訳済み本文がシードされ、コード内の識別子は原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'confidence')->sole();

    expect($document->title)->toBe('確信度')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/confidence.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/confidence')
        ->and($document->source_content)->not->toContain('ConfidenceExplorer')
        ->and($document->content)->toStartWith('# 確信度')
        ->and(substr_count($document->content, '<Note>'))->toBe(2)
        ->and($document->content)->toContain('state=user_message,')
        ->and($document->content)->toContain('route_to_human(user_message)')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('how-to-buildの翻訳済み本文がシードされ、画像URLと入れ子のタグ構造は原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'concepts/how-to-build-with-system-one')->sole();

    expect($document->title)->toBe('TypeSafeを使った開発方法')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/concepts/how-to-build-with-system-one.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/concepts/how-to-build-with-system-one')
        ->and($document->source_content)->not->toContain('<img')
        ->and($document->content)->toStartWith('# TypeSafeを使った開発方法')
        ->and($document->content)->toContain('#only-light)')
        ->and($document->content)->toContain('#only-dark)')
        ->and(substr_count($document->content, '<Step '))->toBe(8)
        ->and(substr_count($document->content, '</Step>'))->toBe(8)
        ->and(substr_count($document->content, '<Accordion '))->toBe(9)
        ->and(substr_count($document->content, '```json title='))->toBe(8)
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('machine-learning-primerの翻訳済み本文がシードされ、画像URLは原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'introduction/machine-learning-primer')->sole();

    expect($document->title)->toBe('AIプライマー')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/introduction/machine-learning-primer.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/introduction/machine-learning-primer')
        ->and($document->content)->toStartWith('# AIプライマー')
        ->and($document->content)->not->toContain('<img')
        ->and(substr_count($document->content, '#only-light)'))->toBe(3)
        ->and(substr_count($document->content, '#only-dark)'))->toBe(3)
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('patternsの翻訳済み本文がシードされ、表と内部リンクは維持される', function () {
    $this->seed();

    $document = Document::query()->where('path', 'patterns')->sole();

    expect($document->title)->toBe('パターン')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/patterns.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/patterns')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->content)->toStartWith('# パターン')
        ->and($document->content)->toContain('| パターン | 概要 | メリット |')
        ->and($document->content)->toContain('[投機的ファンアウト](/patterns/fan-out)')
        ->and(substr_count($document->content, '<Tip>'))->toBe(1)
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('patterns/fan-outの翻訳済み本文がシードされ、コード例の構造と識別子が維持される', function () {
    $this->seed();

    $document = Document::query()->where('path', 'patterns/fan-out')->sole();

    expect($document->title)->toBe('投機的ファンアウト')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/patterns/fan-out.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/patterns/fan-out')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->content)->toStartWith('# 投機的ファンアウト')
        ->and(substr_count($document->content, '```mermaid'))->toBe(1)
        ->and(substr_count($document->content, '```json title='))->toBe(1)
        ->and(substr_count($document->content, '```python title='))->toBe(1)
        ->and(substr_count($document->content, '<Note>'))->toBe(1)
        ->and($document->content)->toContain('"bug_report": "ユーザーが壊れているかエラーが発生していることを報告している"')
        ->and($document->content)->toContain('route_to_billing_with_flag(ticket_id, refund_likely=True)')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('patterns/confidence-routingの翻訳済み本文がシードされ、コード例の構造と識別子が維持される', function () {
    $this->seed();

    $document = Document::query()->where('path', 'patterns/confidence-routing')->sole();

    expect($document->title)->toBe('確信度ゲートルーティング')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/patterns/confidence-routing.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/patterns/confidence-routing')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->content)->toStartWith('# 確信度ゲートルーティング')
        ->and(substr_count($document->content, '```mermaid'))->toBe(1)
        ->and(substr_count($document->content, '```json title='))->toBe(1)
        ->and(substr_count($document->content, '```python'))->toBe(1)
        ->and($document->content)->toContain('"approve_transfer": "保留中の送金リクエストを承認する"')
        ->and($document->content)->toContain('if action.confidence > 0.85:')
        ->and($document->content)->toContain('ask_user_to_confirm("確認のためお伺いします：この送金を承認されますか？")')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('patterns/composite-scoringの翻訳済み本文がシードされ、コード例の構造と識別子が維持される', function () {
    $this->seed();

    $document = Document::query()->where('path', 'patterns/composite-scoring')->sole();

    expect($document->title)->toBe('複合スコアリング')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/patterns/composite-scoring.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/patterns/composite-scoring')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->content)->toStartWith('# 複合スコアリング')
        ->and(substr_count($document->content, '```mermaid'))->toBe(1)
        ->and(substr_count($document->content, '```json title='))->toBe(1)
        ->and(substr_count($document->content, '```python title='))->toBe(1)
        ->and(substr_count($document->content, '"type": "score"'))->toBe(4)
        ->and($document->content)->toContain('"python_depth": {')
        ->and($document->content)->toContain('"Pythonの経験についての記載なし"')
        ->and($document->content)->toContain('ic_score = (0.40 * py) + (0.10 * lead) + (0.40 * arch) + (0.10 * general)')
        ->and($document->content)->toContain('em_score = (0.15 * py) + (0.40 * lead) + (0.20 * arch) + (0.25 * general)')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('patterns/intent-routingの翻訳済み本文がシードされ、コード例の構造と識別子が維持される', function () {
    $this->seed();

    $document = Document::query()->where('path', 'patterns/intent-routing')->sole();

    expect($document->title)->toBe('インテントルーティング')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/patterns/intent-routing.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/patterns/intent-routing')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->content)->toStartWith('# インテントルーティング')
        ->and(substr_count($document->content, '```mermaid'))->toBe(1)
        ->and(substr_count($document->content, '```json title='))->toBe(1)
        ->and(substr_count($document->content, '```python title='))->toBe(1)
        ->and($document->content)->toContain('"order_status": "既存の注文について問い合わせている"')
        ->and($document->content)->toContain('def route_ticket(ticket_id, response):')
        ->and($document->content)->toContain('handle_with_llm(ticket_id, PRODUCT_SPECIALIST)')
        ->and($document->content)->toContain('handle_with_llm(ticket_id, RETURNS_SPECIALIST)')
        ->and($document->content)->toContain('handle_with_llm(ticket_id, COMPLAINT_RESOLUTION)')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('cookbooksの翻訳済み本文がシードされ、表と内部リンクが維持される', function () {
    $this->seed();

    $document = Document::query()->where('path', 'cookbooks')->sole();
    $cookbooks = collect(DocumentNamespace::query()->where('slug', 'typesafe')->sole()->navigation)
        ->firstWhere('page', 'cookbooks');

    expect($document->title)->toBe('クックブック')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/cookbooks.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/cookbooks')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->content)->toStartWith('# クックブック')
        ->and($document->content)->toContain('| クックブック | 内容 | レベル |')
        ->and($document->content)->toContain('[自己一致性: noul](/cookbooks/consistency_noul_cookbook)')
        ->and(substr_count($document->content, '<Tip>'))->toBe(1)
        ->and($document->adoptedSourceSnapshot)->not->toBeNull()
        ->and($cookbooks['title'])->toBe('クックブック')
        ->and($cookbooks['page'])->toBe('cookbooks');
});

test('self-consistency noulsの翻訳済み本文が難易度付きでシードされる', function () {
    $this->seed();

    $document = Document::query()->where('path', 'cookbooks/consistency_noul_cookbook')->sole();
    $cookbooks = collect(DocumentNamespace::query()->where('slug', 'typesafe')->sole()->navigation)
        ->firstWhere('page', 'cookbooks');

    expect($document->title)->toBe('自己一致性：nouls')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/cookbooks/consistency_noul_cookbook.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/cookbooks/consistency_noul_cookbook')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->content)->toStartWith('# 自己一致性：nouls')
        ->and($document->content)->toContain('```python expandable theme={null}')
        ->and(substr_count($document->content, '```python'))->toBe(9)
        ->and(substr_count($document->content, '<a href='))->toBe(1)
        ->and($document->adoptedSourceSnapshot)->not->toBeNull()
        ->and($cookbooks['pages'][0]['title'])->toBe('自己一致性')
        ->and($cookbooks['pages'][0]['pages'][0])->toBe([
            'page' => 'cookbooks/consistency_noul_cookbook',
            'label' => '初級',
        ]);
});

test('self-consistency choicesの原文が難易度付きで翻訳待ち文書としてシードされる', function () {
    $this->seed();

    $document = Document::query()->where('path', 'cookbooks/consistency_choice_cookbook')->sole();
    $cookbooks = collect(DocumentNamespace::query()->where('slug', 'typesafe')->sole()->navigation)
        ->firstWhere('page', 'cookbooks');

    expect($document->title)->toBe('Self-consistency: choices')
        ->and($document->source_title)->toBe('Self-consistency: choices')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/cookbooks/consistency_choice_cookbook.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/cookbooks/consistency_choice_cookbook')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->content)->toBe($document->source_content)
        ->and($document->source_content)->toContain('# Self-consistency: choices {#self-consistency-choices}')
        ->and($cookbooks['pages'][0]['pages'][1])->toBe([
            'page' => 'cookbooks/consistency_choice_cookbook',
            'label' => '初級',
        ]);
});

test('cookbookの原文とDemosが翻訳待ち文書として登録され、難易度順にナビゲーションされる', function () {
    $this->seed();

    $parallelQuestions = Document::query()->where('path', 'cookbooks/parallel_questions')->sole();
    $smartHome = Document::query()->where('path', 'demos/smart-home')->sole();
    $navigation = collect(DocumentNamespace::query()->where('slug', 'typesafe')->sole()->navigation);
    $cookbooks = $navigation->firstWhere('page', 'cookbooks');
    $demos = $navigation->firstWhere('page', 'demos');

    expect($parallelQuestions->title)->toBe('Parallel questions')
        ->and($parallelQuestions->content)->toBe($parallelQuestions->source_content)
        ->and($parallelQuestions->source_url)->toBe('https://docs.typesafe.ai/cookbooks/parallel_questions.md')
        ->and($parallelQuestions->adoptedSourceSnapshot)->not->toBeNull()
        ->and($smartHome->title)->toBe('Smart home assistant demo')
        ->and($smartHome->content)->toBe($smartHome->source_content)
        ->and($smartHome->canonical_url)->toBe('https://docs.typesafe.ai/demos/smart-home')
        ->and($smartHome->adoptedSourceSnapshot)->not->toBeNull()
        ->and(array_column($cookbooks['pages'], 'title'))->toBe([
            '自己一致性',
            'バッチ処理',
            'How-to',
            '抽出',
            '分類',
        ])
        ->and($demos['pages'])->toBe(['demos/smart-home']);
});

test('CookbookとDemosのSeederを繰り返しても文書は重複しない', function () {
    $this->seed();
    $this->seed(TypesafeCookbooksSeeder::class);

    expect(Document::query()->count())->toBe(111)
        ->and(Document::query()->where('path', 'cookbooks/parallel_questions')->count())->toBe(1)
        ->and(Document::query()->where('path', 'demos/smart-home')->count())->toBe(1);
});
