# 状態 {#state}

> 状態とは何か、どのように構造化するか、そしてSystem Oneモデルに必要なコンテキストを与える方法。

**状態**とは、System Oneモデルに評価を依頼するコンテンツです。サポートメッセージ、テキストの一節、またはアプリケーションの現在の状態などが該当します。状態はAPIリクエストの`state`フィールドに、回答を求める質問とともに渡します。

各リクエストは、1つの状態を1つ以上の質問に対して評価します。すべての質問は同じ状態を参照し、独立して評価されます。1つのリクエストに[Choice](/primitives/choice)、[Score](/primitives/score)、[Noul](/primitives/noul)の質問を混在させることができます。

## 状態はシンプルな文字列または構造化されたJSON値にできます {#state-can-be-a-simple-string-or-a-structured-json-value}

最もシンプルな状態はプレーンな文字列です：

```python theme={null}
state = "My card was charged twice."
```

状態はJSONオブジェクトや配列にすることもでき、関連するコンテキスト、例、その他モデルが関連する質問に回答するのに役立つ情報を含めることができます。状態とは、専門家のパネルに判断を求める前に提示する資料だと考えてください。Pythonでは、対応する文字列、辞書、またはリストを`client.system_one(state=...)`に直接渡します。

| フォーマット | 用途 | 例 |
| - | - | - |
| 文字列 | メッセージ、記事、または一節 | `"My card was charged twice."` |
| オブジェクト | 名前付きフィールド、関連レコード、またはアプリケーション状態 | `{"message": "My card was charged twice.", "order_id": "A-104"}` |
| 配列 | メッセージやレコードのシーケンス | `["Hi", "My customer number is TS1337.", "My card was charged twice."]` |

状態の各部分に説明的な名前が付き、関係性が明確に保たれるよう、ほとんどのリクエストにはオブジェクトを使用してください。ユースケースがシンプルで1つのテキストだけが必要な場合は、文字列が適しています。

<Note>
  Jevはテキストのみを受け付けます。状態は文字列、JSONオブジェクト、またはテキスト値の配列でなければなりません。画像、音声、動画はサポートされていません（現時点では）。Jevの主要なトレーニング言語は英語です。日本語を含むその他の言語やCJKスクリプトも受け付けますが、現在は精度が低くなっています。詳細は[Models](/models#language-support)をご覧ください。
</Note>

```json title="A support conversation as state" theme={null}
{
  "ticket": {
    "subject": "Duplicate charge",
    "messages": [
      {"from": "customer", "text": "I was charged twice for order A-104. Please refund the duplicate."},
      {"from": "support", "text": "We are checking the charges."}
    ]
  },
  "order": {
    "id": "A-104",
    "charges": [
      {"amount_usd": 49, "status": "captured"},
      {"amount_usd": 49, "status": "captured"}
    ]
  },
  "refund_policy": "Duplicate charges are eligible for a refund."
}
```

このオブジェクトは、会話、注文、ポリシーを含んでいても、1つの状態です。判断にそれらの部分の比較が必要な場合は、関連情報をまとめて入れてください。

## コンテンツと質問を分離する {#separate-content-from-questions}

状態にはコンテンツと裏付けとなる事実を含めます。[質問](/primitives)は、そのコンテンツについてモデルが行うべき判断を定義します。たとえば、返金リクエストとポリシーは状態に入れ、顧客が返金を要求したかどうか、またポリシーがそれを支持するかどうかを質問します。

指示、基準、質問タイプ、1つの状態に対して複数の質問をする方法については、[プリミティブ（質問）](/primitives)をご覧ください。

リクエストスキーマについては[APIリファレンス](/api)を、インストール、型付き入力、レスポンス処理については[クライアントSDK](/sdk)をご覧ください。