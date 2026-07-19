## Thinkstream 構文

Thinkstream には、標準の Markdown、Zenn 構文、そしてサポートされている Mintlify コンポーネントに加えて、いくつかの独自の執筆用ショートカットが用意されています。このページでは Thinkstream 固有の追加機能について説明します。

---

## Quiz フェンス

Thinkstream は、単一問題の選択式クイズ用に軽量な `quiz` フェンス付きコードブロックをサポートしています。

表示例:

```quiz
question: Next.js はどのようにフォントを最適化しますか？
correct: D

A: 追加のネットワークリクエストが発生し、パフォーマンスが向上する。
B: すべてのカスタムフォントを無効化する。
C: 実行時にすべてのフォントをプリロードする。
D: フォントファイルを他の静的アセットと一緒にホストするため、追加のネットワークリクエストが発生しない。

hint: 追加のリクエストはパフォーマンスに影響することがあります。
incorrect: 残念、違います
correctMessage: 正解です
explanation: Next.js は最適化されたフォントアセットを自前でホストできるため、ブラウザは追加のサードパーティフォントリクエストを避けられます。
```

ソース:

````md
```quiz
question: Next.js はどのようにフォントを最適化しますか？
correct: D

A: 追加のネットワークリクエストが発生し、パフォーマンスが向上する。
B: すべてのカスタムフォントを無効化する。
C: 実行時にすべてのフォントをプリロードする。
D: フォントファイルを他の静的アセットと一緒にホストするため、追加のネットワークリクエストが発生しない。

hint: 追加のリクエストはパフォーマンスに影響することがあります。
incorrect: 残念、違います
correctMessage: 正解です
explanation: Next.js は最適化されたフォントアセットを自前でホストできるため、ブラウザは追加のサードパーティフォントリクエストを避けられます。
```
````

補足:

- `question:` は選択肢の上に表示される設問を定義します。
- `correct:` は `A`、`B`、`C`、`D` のいずれかで正解の選択肢を指定します。
- 選択肢は `A:` のようなラベル形式なので、プレーンな Markdown のまま入力しやすくなっています。
- `hint:` と `incorrect:` は、誤答後に表示されるリトライ状態を制御できます。
- `correctMessage:` と `explanation:` は、正解時の結果表示をカスタマイズします。

---

## Tree フェンス

ターミナルからファイルツリーをそのまま貼り付けたい場合は、`tree` フェンス付きコードブロックを使います。Thinkstream が自動的にインタラクティブなツリー表示に変換します。

表示例:

```tree
app/Ai
└── Agents
    ├── CoverImagePromptAgent.php
    ├── MarkdownStructureAgent.php
    ├── ThinkstreamStructureAgent.php
    ├── ThinkstreamTitleAgent.php
    └── TranslateSelectionAgent.php
```

ソース:

````md
```tree
app/Ai
└── Agents
    ├── CoverImagePromptAgent.php
    ├── MarkdownStructureAgent.php
    ├── ThinkstreamStructureAgent.php
    ├── ThinkstreamTitleAgent.php
    └── TranslateSelectionAgent.php
```
````

補足:

- `app/Ai` はネストしたフォルダに分割されます。
- `├──` と `└──` を使った子行は、自動的にファイルまたはフォルダとして扱われます。
- フォルダはデフォルトで展開された状態になるため、貼り付けたツリーをすぐに読むことができます。

---

## 共有インデント

インデントされたツリーブロックにも対応しており、スニペット自体がソースファイル内のリスト項目や引用の中にネストしている場合に便利です。

表示例:

```tree
  resources/js
  └── components
      ├── markdown-content.tsx
      ├── markdown-tree.tsx
      └── markdown-update.tsx
```

ソース:

````md
```tree
  resources/js
  └── components
      ├── markdown-content.tsx
      ├── markdown-tree.tsx
      └── markdown-update.tsx
```
````

---

## フォルダのヒント

子要素が列挙される前でも明示的にフォルダにしたい場合は、末尾にスラッシュを追加します。

表示例:

```tree
app/
└── Services/
    └── SyncFileParser.php
```

ソース:

````md
```tree
app/
└── Services/
    └── SyncFileParser.php
```
````

---

## サマリー行の無視

`tree` コマンドからコピーした出力には、末尾にサマリー行が含まれることがよくあります。Thinkstream はこれらを自動的に無視します。

表示例:

```tree
.
├── app
│   └── Services
│       └── SyncFileParser.php
└── tests
    └── Feature
        └── SyntaxSeederTest.php

4 directories, 2 files
```

ソース:

````md
```tree
.
├── app
│   └── Services
│       └── SyncFileParser.php
└── tests
    └── Feature
        └── SyntaxSeederTest.php

4 directories, 2 files
```
````

---

## チャート

横棒グラフには `chart:bar`、レーダーチャートには `chart:radar` を使います。どちらも同じキーバリュー形式です。

#### 横棒グラフ

表示例:

```chart:bar
_title: フレーバープロファイル
_max: 10
juniper: 9
citrus: 4
spice: 6
herbal: 5
floral: 2
sweetness: 2
smoothness: 5
```

ソース:

````md
```chart:bar
_title: フレーバープロファイル
_max: 10
juniper: 9
citrus: 4
spice: 6
herbal: 5
floral: 2
sweetness: 2
smoothness: 5
```
````

#### レーダーチャート

表示例:

```chart:radar
_title: フレーバープロファイル
_max: 10
juniper: 9
citrus: 4
spice: 6
herbal: 5
floral: 2
sweetness: 2
smoothness: 5
```

ソース:

````md
```chart:radar
_title: フレーバープロファイル
_max: 10
juniper: 9
citrus: 4
spice: 6
herbal: 5
floral: 2
sweetness: 2
smoothness: 5
```
````

`_` で始まる予約キー: `_title`、`_max`、`_min`。それ以外の `label: value` 行はすべてデータポイントです。

---

## Wikilink

Wikilink は、投稿を `full_path` で参照してリンクする Thinkstream 独自の構文です。通常の Markdown リンクとは異なり、パスに依存しないため、プログラム的に検出できます（例: リネーム後のリンク切れを見つける）。

#### 基本的な Wikilink

`[[full_path]]` で投稿にリンクできます。表示テキストはデフォルトでパスの最後のセグメントになります。

[[syntax/wikilinks]]

ソース:

```md
[[syntax/wikilinks]]
```

#### ラベル付き Wikilink

`[[full_path|label]]` でカスタム表示テキストを設定できます。

[[syntax/wikilinks|このページ]]

ソース:

```md
[[syntax/wikilinks|このページ]]
```

#### 解決済み Wikilink

`full_path` が同じKB内の既存ドキュメントのタイトルと一致すると、そのドキュメントへのリンクとして解決されます。

[[Zenn 構文]]

ソース:

```md
[[Zenn 構文]]
```

補足:

- kb_practice では `full_path` を「ドキュメントのタイトル」として解決します（Thinkstream本来のネームスペース＋スラッグとは異なります）。
- 一致するタイトルが見つからない場合、上の2つの例のようにリンクは新規ドキュメント作成フォームに解決され、タイトル欄にその `full_path` が入力済みの状態になります。
- 既存の `[label](/path)` 形式の Markdown リンクも Wikilink と併用できます。
