# 上級編：構造 {#advanced-structure}

> Instructions、Choiceオプション、Scoreレベル、およびNoul criteriaはすべてJSON構造を受け付けます。

System Oneモデルは構造を理解するようにトレーニングされています。

## 構造が許可される場所 {#where-structure-is-allowed}

以下のフィールドはすべて[`EntryType`](/sdk/javascript/api/type-aliases/EntryType)です。

| フィールド | 適用対象 | 受け付ける形式 |
| - | - | - |
| `instructions` | Choice、Score、Noul | `string`、`object`、`array`、または`null` |
| `criteria`の値（オプションの説明） | Choice | `string`、`object`、`array`、または`null` |
| `criteria`のエントリ（レベルの説明） | Score | `string`、`object`、`array`、または`null` |
| `criteria.true`と`criteria.false` | Noul | `string`、`object`、`array`、または`null` |

## 質問を構造化するタイミング {#when-to-structure-a-question}

* **明確さに役立つとき。** 質問に複数の部分がある場合、JSONの形式にするとキーにラベルが付くため明確さが向上します。
* **質問に補足データが必要なとき。** スキーマ、タクソノミー、またはデータベースの行はすでにJSONです。JSONをそのまま使用するか、文字列テンプレートにシリアライズするのではなく、関連するサブフィールドを渡します。

## 構造化されたinstructions {#structured-instructions}

1つの`field`オブジェクトがチェック対象のフィールドを記述し、各質問はキーによってそれを参照します。同じ形式が、値を検証するNoul、候補から1つを選ぶChoice、および値をスケールに配置する2つのScoreを駆動します。

```json title="request" theme={null}
{
  "state": {
    "source_text": "2026年3月3日にBeaver Dam Logistics宛てに発行された請求書#4471、金額$12,840.00、支払期限30日。"
  },
  "questions": {
    "invoice_number_is_correct": {
      "type": "noul",
      "instructions": {
        "field": {
          "name": "invoice_number",
          "type": "string",
          "description": "請求書に印刷されている識別子。"
        },
        "extracted_value": "4471",
        "question": "`extracted_value`は`source_text`に表示されている`field`と一致しますか？"
      }
    },
    "customer_name": {
      "type": "choice",
      "instructions": {
        "field": {
          "name": "customer_name",
          "type": "string",
          "description": "請求書の発行先となった組織。"
        },
        "question": "`source_text`における`field`の値はどのオプションですか？"
      },
      "criteria": {
        "Beaver Logistics": null,
        "Dam Logistics": null,
        "Beaver Dam Logistics": null,
        "Beaver": null,
        "Dam": null
      }
    },
    "amount_due": {
      "type": "score",
      "instructions": {
        "field": {
          "name": "amount_due",
          "type": "number",
          "unit": "USD",
          "description": "請求書が支払いを求める合計金額。"
        },
        "question": "`source_text`における`field`の値はどの程度の大きさですか？"
      },
      "criteria": [
        "$1,000未満",
        "$1,000〜$10,000",
        "$10,000〜$100,000",
        "$100,000〜$1,000,000",
        "$1,000,000超"
      ]
    },
    "payment_terms": {
      "type": "score",
      "instructions": {
        "field": {
          "name": "payment_terms",
          "type": "integer",
          "unit": "days",
          "description": "「net 30」などの条件から、支払いに許可された日数。"
        },
        "question": "`source_text`の`field`は支払いに何日を許可していますか？"
      },
      "criteria": [
        "受領時に支払い",
        "Net 10",
        "Net 30",
        "Net 60",
        "Net 90"
      ]
    }
  }
}
```

コードでは、候補レコードをループして各フィールドにこれらの質問を1つずつ作成し、すべてを1回の呼び出しで送信することができます。[SDEカスケードのクックブック](/cookbooks/sde_cascade)もこれに類似したことを行っています。

配列も使用できます。instructionがチェックまたは比較するもののリストである場合に使用します：

```json theme={null}
"instructions": {
  "question": "主張された送信者IDは送信ドメインと矛盾していますか？",
  "compare": ["ticket.sender.display_name", "ticket.sender.email"],
  "focus": "名前付き組織とメールドメインを比較してください。"
}
```

## 構造化されたChoiceオプション {#structured-choice-options}

Choiceのオプション説明も構造化されたオブジェクトにすることができます。

### 境界を明確にするためのJSONルーブリック {#json-rubric-for-boundary-clarification}

```json title="request" theme={null}
{
  "state": "2週間前にスタンディングデスクを注文したのですが、トラッキングには「ラベル作成済み」と表示されたままです。請求はされているのでしょうか？",
  "questions": {
    "department": {
      "type": "choice",
      "instructions": {
        "question": "このメッセージはどのチームが対応すべきですか？",
        "focus": "言及されているすべてのトピックではなく、顧客の主なリクエストを分類してください。"
      },
      "criteria": {
        "billing": {
          "what": "請求、インボイス、返金、またはサブスクリプション",
          "not_for": "注文の追跡またはアカウントアクセス",
          "examples": [
            "2回請求されました",
            "返金はどこにありますか？"
          ]
        },
        "orders": {
          "what": "注文状況、配送、キャンセル、または返品",
          "not_for": "請求またはアカウントアクセス",
          "examples": [
            "荷物はどこにありますか？",
            "注文をキャンセルしてください"
          ]
        },
        "account": {
          "what": "ログイン、パスワード、プロフィール、またはセキュリティ",
          "not_for": "請求または配送",
          "examples": [
            "ログインできません",
            "メールアドレスを変更してください"
          ]
        }
      }
    }
  }
}
```

この例はモデルに対して、各オプションがカバーするものとカバー**しない**ものを伝えます。これによりオプション間の境界が明確になります。

### タクソノミーをたどる {#walking-a-taxonomy}

深いタクソノミーに分類するには、レベルごとに1つのChoiceを使用し、コードでツリーをたどります。各ステップでオプションは現在のノードの子であり、各オプションの値は子のツリーです。これにより、モデルはブランチにコミットする前にその下に何があるかを確認できます。これは、アイテムがブランチ名だけでは明確でない葉に属する場合に重要です。

ここでは状態が商品リストであり、最初の質問でトップレベルの部門を選択します。

```json title="request" theme={null}
{
  "state": "フリップストロー蓋付き32ozプラスチックボトル。ほとんどの自転車ケージに対応。",
  "questions": {
    "department": {
      "type": "choice",
      "instructions": "この商品はどのトップレベルの部門に属しますか？",
      "criteria": {
        "Sporting Goods": {
          "Cycling": [
            "Bike Bottles & Cages",
            "Bike Lights",
            "Helmets"
          ],
          "Fitness": [
            "Yoga Mats",
            "Resistance Bands"
          ],
          "Outdoor": [
            "Tents",
            "Sleeping Bags",
            "Hydration Packs"
          ]
        },
        "Home & Kitchen": {
          "Drinkware": [
            "Water Bottles",
            "Travel Mugs",
            "Tumblers"
          ],
          "Cookware": [
            "Pots & Pans",
            "Bakeware"
          ]
        },
        "Baby & Toddler": [
          "Sippy Cups",
          "Bottle Warmers",
          "Bibs"
        ]
      }
    }
  }
}
```

このボトルは2つの部門に当てはまる可能性があります。サブツリーを表示することで、モデルは`Sporting Goods > Cycling > Bike Bottles & Cages`と`Home & Kitchen > Drinkware > Water Bottles`の両方が存在することを確認し、自転車ケージへの記載と日常的なドリンクウェアを比較検討できます。この回答の`probabilities`から、両方のブランチを探索するほど差が拮抗しているかどうかがわかります。

部門が選択されたら、次のChoiceをその部門の子をオプションとして、サブツリーを値として使用して質問し、葉に到達するまで繰り返します。コードでは、ネストされた辞書をループし、各質問の`criteria`が現在のノードになります。[階層的分類のクックブック](/cookbooks/hierarchical_classification)では、確率が拮抗している場合に複数の候補パスを保持するビームサーチを含む、類似したツリーのウォークの例を示しています。

<Note>
  サブツリーは大きくなる場合があります。ブランチが大きすぎる場合は、値を直接の子とサンプルの葉にトリミングしてください。
</Note>

## 構造化されたScoreレベル {#structured-score-levels}

ScoreのCriteria配列の各エントリをオブジェクトにすることができます。

```json title="request" theme={null}
{
  "state": "ペイメントハンドラーのnullチェックを修正しました。ついでにリトライループをリファクタリングし、古いSDKにタイムアウトのバグがあったためSDKバージョンも更新しました。",
  "questions": {
    "pr_scope": {
      "type": "score",
      "instructions": {
        "question": "このプルリクエストの説明は、単一の変更にどの程度集中していますか？",
        "note": "個々の変更の規模ではなく、独立した変更の数で判断してください。"
      },
      "criteria": [
        {
          "summary": "1つの変更、明確に記述されている",
          "signals": [
            "単一の修正または機能",
            "「ついでに」や「ながら」といった記述がない"
          ]
        },
        {
          "summary": "1つの主要な変更と小さな関連する調整",
          "signals": [
            "主要な変更と1つのマイナーな隣接する編集",
            "調整が主要な変更をサポートしている"
          ]
        },
        {
          "summary": "いくつかの独立した変更がまとめられている",
          "signals": [
            "2つ以上の無関係な修正または機能",
            "それぞれが独自のPRになり得る変更"
          ]
        }
      ]
    }
  }
}
```

## 構造化されたNoul criteria {#structured-noul-criteria}

Noulの`criteria`はオプションであり、yes/noの境界が微妙な場合、構造化された`true`と`false`の説明を使用することで、各サイドの定義と例によって境界を明確にできます。

```json title="request" theme={null}
{
  "state": {
    "sender": {
      "display_name": "Beaver Dam Builders Ltd.",
      "email": "donotreply@payroll.example"
    },
    "message": "Q3ボーナスの準備ができました。本人確認と資金の受け取りのため、ログインパスワードを返信してください。"
  },
  "questions": {
    "requests_credentials": {
      "type": "noul",
      "instructions": {
        "question": "`message`は受信者に機密情報を開示するよう求めていますか？",
        "inspect": "message",
        "focus": "認証情報そのものを送るよう求めているかを確認してください。変更やリセットの要求ではありません。"
      },
      "criteria": {
        "true": {
          "what": "受信者にパスワード、PIN、ワンタイムコード、またはその他のセキュリティに関わる回答を返信、入力、または送信するよう求める",
          "examples": [
            "パスワードを返信してください",
            "受け取った6桁のコードを送ってください"
          ]
        },
        "false": {
          "what": "機密の認証情報が要求されていない",
          "examples": [
            "設定ページからパスワードをリセットしてください",
            "明細書の準備ができました"
          ]
        }
      }
    }
  }
}
```