# コーディングエージェントとJev {#jev-with-coding-agents}

> コーディングエージェントを使用している場合のJevとは何か（そして何でないか）。

コーディングエージェントに組み込むモデルを探してTypeSafeを見つけた場合は、まずここをお読みください。Jevは、Claude Code、Cursor、opencode、Copilot、Muse Spark、Grok Botや類似ツールの背後にあるLLMの代替品では**ありません**。代わりに、コーディングエージェントをいつも通り使用して、Jevで意思決定を行うコードを書くことができます。

## Jevはチャットやコード補完のLLMではない {#jev-is-not-a-chat-or-code-completion-llm}

Jevは[System Oneモデル](/concepts/system-one)です。テキストの生成、コードの作成、会話の維持は行いません。[状態](/concepts/state)と型付きの[質問](/primitives)のセットを受け取り、コードで直接使用できる構造化された回答を返します：

* オプションごとの確率を持つ、選択肢リストからの`choice`。
* 独自に定義したルーブリックに基づく`score`。
* 真偽値ステートメントに対する`noul`（0〜1）。

コーディングエージェントは、テキストをストリーミングし、ツールを呼び出し、自然言語の指示に基づいてファイルを編集するLLMに依存しています。Jevはそのいずれも行いません。`model: "jev-latest"`という設定でコーディングエージェントをJev搭載エージェントに変えることはできません。なぜなら、両者は異なる問題を解決するシステムだからです。

## 代わりに必要なもの {#what-you-probably-want-instead}

やりたいことに合った行を選んでください：

| やりたいこと | 対応方法 |
| - | - |
| *TypeSafeを使ったコードを書く*コーディングエージェントを改善したい | [TypeSafeエージェントスキル](/agent-skill)をインストールしてください。Claude Code、Codexなどのエージェントに、Jev API、[プリミティブ](/primitives)、[パターン](/patterns)に関する完全なコンテキストを与え、正確なTypeSafeインテグレーションを生成できるようにします。 |
| 構築中のアプリやエージェント内でJevを使用したい（ルーティング、分類、スコアリング、ガードレール、または構造化された意思決定） | [クイックスタート](/introduction/quickstart)から始め、[TypeSafeで構築する方法](/concepts/how-to-build-with-system-one)と、[確信度ルーティング](/patterns/confidence-routing)や[インテントルーティング](/patterns/intent-routing)などの一般的なアーキテクチャの[パターン](/patterns)をお読みください。 |
| コーディングエージェントを動かすモデルを置き換えたい、または交換したい | Jevはこのためのツールではありません。LLMベースのコーディングエージェントを引き続き使用し、製品で高速・キャリブレーション済み・構造化された意思決定が必要な箇所でJevを別途使用してください。 |
| コードを書く前にJevを試したい | [Playground](https://console.typesafe.ai/playground)を開き、状態としてテキストを貼り付け、いくつかの質問を追加してください。ウォークスルーは[クイックスタート](/introduction/quickstart)をご覧ください。 |

## Jevが役立つ場面 {#when-jev-is-worth-reaching-for}

JevはコーディングエージェントのLLMではありませんが、コーディングエージェントで構築しているエージェントやアプリの*内部*では、まさに適切なツールであることがよくあります。コードが以下のことを行う必要がある場合にJevを活用してください：

* リクエストを固定された宛先セットのいずれかにルーティングし、そのルーティングの確信度を把握する。
* ルーブリック（緊急度、品質、リスク）に基づいて何かをスコアリングし、その数値で分岐する。
* アクションを実行する前に、ドキュメント、メッセージ、またはレコードに対してステートメントが真かどうかを確認する。
* 「JSONを返す」ようLLMに求める脆弱なプロンプトを、構造上型付きの値を返す呼び出しに置き換える。

構築しているものに該当する場合、最も早い入り口は[クイックスタート](/introduction/quickstart)、次に質問タイプの[プリミティブ](/primitives)リファレンスです。

## 次のステップ {#next-steps}

* [System One](/concepts/system-one) — System Oneモデルとは何か、LLMとの違い。
* [クイックスタート](/introduction/quickstart) — Playground、HTTP、またはPython SDKでJevを試す。
* [エージェントスキル](/agent-skill) — TypeSafe APIに関するコンテキストをコーディングエージェントに提供する。
* [パターン](/patterns) — TypeSafeで構築するための一般的なアーキテクチャ。