# Choice {#choice}

> Choiceは、定義されたセットから1つのオプションを選択するためのSystem Oneの質問タイプです。回答には、選択されたオプション、各オプションの確率、および確信度が含まれます。

回答が固定されたオプションのセットのいずれかである場合にChoiceを使用します。例えば、チケットを処理するチーム、製品が属するカテゴリー、またはコードスニペットが書かれている言語などです。回答がスペクトル上の位置である場合は[Score](/primitives/score)を、はいまたはいいえの場合は[Noul](/primitives/noul)を使用します。[質問タイプの選び方](/primitives#choose-a-question-type)では3つを比較しています。

Choice回答は`choice`内の選択されたオプションです。モデルはまた`probabilities`内のすべてのオプションの確率と、選択されたオプションの`confidence`値も返します。

質問の例：

```
"このコードはどのプログラミング言語で書かれていますか"
  → options: python, javascript, typescript, go, rust, other

"タイトルと説明に基づいて、これはどのタイプの会議ですか"
  → options: standup, planning, retrospective, one on one, brainstorm, none of the above

"この商品はどの商品カテゴリーに属しますか"
  → options: electronics, clothing, home garden, food and beverage
```

## リクエストの構造 {#request-structure}

[TypeSafe API](/api)へのPOSTリクエストボディには特定の構造があります。トップレベルには3つのフィールドがあります：評価するコンテンツである`state`、`model`、そしてあなたが選択した質問IDから質問オブジェクトへのマップである`questions`です。各Choice質問には以下のフィールドがあります：

* `type`：常に`"choice"`。
* `instructions`：モデルが回答する質問。
* `criteria`：マップとしての回答オプション。各キーはオプション名で、各値はそのオプションの説明です。

以下は、状態がオンラインシューズストアのサポートチケットで、質問がどのチームが処理すべきかというリクエストです：

```json title="request" theme={null}
{
  "state": "My running shoes arrived in the wrong size. Can I swap them for a size 10?",
  "questions": {
    "department": {
      "type": "choice",
      "instructions": "Which team should handle this?",
      "criteria": {
        "returns": "Exchanges, wrong or damaged items",
        "shipping": "Delivery status, delays, lost packages",
        "billing": "Charges, invoices, payment problems"
      }
    }
  }
}
```

この場合の質問IDは`department`で、あなたが選択します。回答は同じIDの下に返されます。モデルは質問IDを見ることはありません。オプション名とその説明はどちらもモデルに送信されるため、オプションを互いに区別できる説明を書いてください。

[クライアントSDK](/sdk)では型付きの質問が提供されています。Pythonでは、同じ質問は`Choice`です：

```python theme={null}
from typesafe_sdk import Choice, TypeSafeClient

with TypeSafeClient() as client:
    response = client.system_one(
        state="My running shoes arrived in the wrong size. Can I swap them for a size 10?",
        questions={
            "department": Choice(
                instructions="Which team should handle this?",
                criteria={
                    "returns": "Exchanges, wrong or damaged items",
                    "shipping": "Delivery status, delays, lost packages",
                    "billing": "Charges, invoices, payment problems",
                },
            ),
        },
    )

    print(response.answers["department"].choice)
```

System Oneモデルを呼び出すには`system_one`メソッドまたは`https://api.typesafe.ai/v1/systemone`エンドポイントを使用します。`model`フィールドはリクエストを処理するモデルを選択します。[TypeSafeを使って構築する方法](/concepts/how-to-build-with-system-one)では、コードのどこで呼び出すかについて説明しています。

[クライアントSDK](/sdk)のいずれかを使用するか、[HTTP API](/api)を直接呼び出してください。コーディングエージェントがインテグレーションを作成する場合は、リクエストとレスポンスの形状を認識させるために、先に[TypeSafeエージェントスキル](/agent-skill#installation)をインストールしてください。

<Note>
  `instructions`と`criteria`の各エントリは、文字列、オブジェクト、または配列にできます。まず文字列から始めてください。説明にオプションがカバーする内容、カバーしない内容、いくつかの例など複数の種類のガイダンスが必要な場合はオブジェクトを使用してください。下記の[構造化されたinstructionsとcriteria](#structured-instructions-and-criteria)および[APIリファレンス](/api#param-instructions-1)を参照してください。
</Note>

## レスポンスの構造 {#response-structure}

レスポンスには、リクエストのIDの下に質問ごとに1つのエントリが`answers`にあります。これは上記のリクエスト例へのレスポンスです：

```json theme={null}
{
  "model": "jev-1.13.0",
  "answers": {
    "department": {
      "type": "choice",
      "choice": "returns",
      "confidence": 1.0,
      "probabilities": {
        "shipping": 0.0,
        "returns": 1.0,
        "billing": 0.0
      }
    }
  },
  "usage": {
    "input_tokens": 328,
    "output_tokens": 34
  }
}
```

`type`の他に、各Choice回答には3つの値があります：

* `choice`：最も高い確率のオプション。
* `probabilities`：すべてのオプションにわたる完全な確率分布。すべての値の合計は1です。
* [`confidence`](/confidence)：`probabilities`の広がり方から計算される0から1の数値。確率が複数のオプションに広がるフラットな形状は低い確信度を意味します。1つのオプションへの単一のピークは高い確信度を意味します。

このチケットは簡単なものなので、確率はすべて`returns`にあり、確信度は1.0です。サイズ違いと払い戻しがないことの両方に言及するチケットは、確率が`returns`と`billing`に分割され、確信度が下がるでしょう。

## グッドプラクティス：1回の呼び出しで複数の質問をする {#good-practice-ask-more-than-one-question-per-call}

1リクエストにつき1質問ではなく、コードが必要とする可能性のあるすべてのChoice質問を1つのリクエストでまとめて質問してください。質問は並列に評価されます。質問を追加してもレスポンス時間はほとんど変わらず、コードは必要のない回答を無視できます。余分な質問はトークンのコストがかかります。[複数の質問をまとめて質問する](/primitives#ask-multiple-questions-together)でこれを詳しく説明しています。次のセクションでは、1回の呼び出しで5つのChoice質問を示します。

同じ論理は1つのChoice質問内のオプションにも適用されます。Choice質問は最大255のオプションを受け入れ、オプションを追加するごとに数トークンのコストがかかるため、省略リストではなくチーム、カテゴリー、または製品の完全なリストをモデルに与えてください。リストがすべての入力をカバーしない可能性がある場合は`other`または`none of the above`オプションを追加して、モデルが他のどれも当てはまらないと言えるようにしてください。

深い階層や大きなタクソノミーでドキュメントを分類するには、Choice質問をレベルごとにチェーンさせます。[階層的分類クックブック](/cookbooks/hierarchical_classification)では、単一の貪欲なパスにコミットする代わりに、各レベルで最良の`K`候補パスを保持しながらChoice確率に対してビームサーチを実行する方法を示しています。

## より複雑な例 {#a-more-complex-example}

上記の基本的な例はチケットをチームにルーティングします。より大きなサポートシステムでは、返品理由、配送問題、顧客の要望、および顧客のトーンも必要になる場合があります。

以下のリクエストは、最初のチケットよりも曖昧なチケットについて5つのChoice質問を質問します：3つのチームが関係し、顧客が何を求めているかを明示していません。

```json title="request" theme={null}
{
  "state": "Shoes arrived two weeks late and in the wrong size. Also I see two charges of $120 on my card. What are you going to do about this?",
  "questions": {
    "department": {
      "type": "choice",
      "instructions": "Which team should handle this?",
      "criteria": {
        "returns": "Exchanges, wrong or damaged items",
        "shipping": "Delivery status, delays, lost packages",
        "billing": "Charges, invoices, payment problems"
      }
    },
    "return_reason": {
      "type": "choice",
      "instructions": "If the customer wants to return something, why?",
      "criteria": {
        "wrong_size": "The item doesn't fit",
        "wrong_item": "A different product was delivered",
        "damaged": "The item arrived broken or faulty",
        "changed_mind": "The item is fine, the customer no longer wants it",
        "other": "A return reason that fits none of the above"
      }
    },
    "shipping_issue": {
      "type": "choice",
      "instructions": "If this is a shipping problem, which kind is it?",
      "criteria": {
        "not_delivered": "The package never arrived",
        "delayed": "The package is late but still on its way",
        "wrong_address": "The package went to the wrong place",
        "damaged_in_transit": "The package arrived damaged",
        "other": "A shipping problem that fits none of the above"
      }
    },
    "requested_resolution": {
      "type": "choice",
      "instructions": "What does the customer want to happen?",
      "criteria": {
        "exchange": "Swap the item for a different one",
        "refund": "Money back",
        "replacement": "The same item sent again",
        "information": "Just an answer, no action needed"
      }
    },
    "tone": {
      "type": "choice",
      "instructions": "What is the customer's tone?",
      "criteria": {
        "calm": null,
        "frustrated": null,
        "angry": null
      }
    }
  }
}
```

これらのChoice質問のうち2つは推測的です：`return_reason`は`department`が`returns`の場合にのみ意味があり、`shipping_issue`は`shipping`の場合にのみ意味があります。`tone`質問はオプション名それ自体が明確なため`null`の説明を使用しています。

TypeSafeのレスポンス：

```json theme={null}
{
  "model": "jev-1.13.0",
  "answers": {
    "department": {
      "type": "choice",
      "choice": "returns",
      "confidence": 0.42,
      "probabilities": {
        "shipping": 0.04,
        "billing": 0.35,
        "returns": 0.61
      }
    },
    "return_reason": {
      "type": "choice",
      "choice": "wrong_size",
      "confidence": 1.0,
      "probabilities": {
        "other": 0.0,
        "wrong_size": 1.0,
        "changed_mind": 0.0,
        "damaged": 0.0,
        "wrong_item": 0.0
      }
    },
    "shipping_issue": {
      "type": "choice",
      "choice": "delayed",
      "confidence": 0.67,
      "probabilities": {
        "wrong_address": 0.0,
        "other": 0.26,
        "not_delivered": 0.0,
        "damaged_in_transit": 0.0,
        "delayed": 0.74
      }
    },
    "requested_resolution": {
      "type": "choice",
      "choice": "refund",
      "confidence": 0.2,
      "probabilities": {
        "replacement": 0.34,
        "refund": 0.4,
        "information": 0.02,
        "exchange": 0.24
      }
    },
    "tone": {
      "type": "choice",
      "choice": "frustrated",
      "confidence": 0.76,
      "probabilities": {
        "frustrated": 0.84,
        "angry": 0.16,
        "calm": 0.0
      }
    }
  },
  "usage": {
    "input_tokens": 589,
    "output_tokens": 212
  }
}
```

各質問はチケットに対して独立して回答されています：

* `department`の回答は確率0.61で`returns`ですが、二重請求のために`billing`が0.35を持っています。チケットは2つのチームに属しており、確信度0.42はそれを反映しています。
* `return_reason`は確信度1.0で`wrong_size`であり、チケットにそれが明確に書かれているため予想通りです。
* `shipping_issue`の回答は`delayed`と`other`に分割されています。これは推測的な質問であり、`department`は`shipping`として返ってこなかったため、コードでは以下のサンプルコードスニペットに示すように無視できます。
* `requested_resolution`の回答は0.40で`refund`に傾いており、`replacement`と`exchange`が残りのほとんどを共有し、確信度は0.20です。二重請求は払い戻しを示唆し、サイズ違いは交換を示唆しており、顧客はどちらを望むかを言っていません。
* `tone`の回答は確率0.84、確信度0.76で`frustrated`です。

以下のサンプルコードは必要な回答を読み取り、残りを無視し、確信度の低い回答を行動の理由ではなく質問する理由として扱います：

```python theme={null}
from typesafe_sdk import Choice, TypeSafeClient

TRIAGE_QUESTIONS = {
    "department": Choice(
        instructions="Which team should handle this?",
        criteria={
            "returns": "Exchanges, wrong or damaged items",
            "shipping": "Delivery status, delays, lost packages",
            "billing": "Charges, invoices, payment problems",
        },
    ),
    "return_reason": Choice(
        instructions="If the customer wants to return something, why?",
        criteria={
            "wrong_size": "The item doesn't fit",
            "wrong_item": "A different product was delivered",
            "damaged": "The item arrived broken or faulty",
            "changed_mind": "The item is fine, the customer no longer wants it",
            "other": "A return reason that fits none of the above",
        },
    ),
    "shipping_issue": Choice(
        instructions="If this is a shipping problem, which kind is it?",
        criteria={
            "not_delivered": "The package never arrived",
            "delayed": "The package is late but still on its way",
            "wrong_address": "The package went to the wrong place",
            "damaged_in_transit": "The package arrived damaged",
            "other": "A shipping problem that fits none of the above",
        },
    ),
    "requested_resolution": Choice(
        instructions="What does the customer want to happen?",
        criteria={
            "exchange": "Swap the item for a different one",
            "refund": "Money back",
            "replacement": "The same item sent again",
            "information": "Just an answer, no action needed",
        },
    ),
    "tone": Choice(
        instructions="What is the customer's tone?",
        criteria={"calm": None, "frustrated": None, "angry": None},
    ),
}


def triage(ticket: str) -> None:
    with TypeSafeClient() as client:
        response = client.system_one(
            state=ticket,
            questions=TRIAGE_QUESTIONS,
        )
    answers = response.answers

    department = answers["department"]
    if department.confidence < 0.3:
        # Not clear which team to send to. Let a person decide.
        send_to_manual_triage(ticket)
        return

    if department.choice == "returns":
        # return_reason answer is only used here
        assign(ticket, team="returns", issue=answers["return_reason"].choice)
    elif department.choice == "shipping":
        # shipping_issue answer is only used here
        assign(ticket, team="shipping", issue=answers["shipping_issue"].choice)
    else:
        assign(ticket, team="billing")

    # A second team with a real share of the probability gets a copy
    for team, probability in department.probabilities.items():
        if team != department.choice and probability > 0.25:
            notify(ticket, team=team)

    resolution = answers["requested_resolution"]
    if resolution.confidence < 0.5:
        # The customer hasn't said what they want. Ask, don't guess.
        ask_customer_what_they_want(ticket)
    elif resolution.choice == "refund":
        flag_for_refund_approval(ticket)

    if answers["tone"].choice == "angry":
        flag_for_senior_agent(ticket)
```

上記のチケットに対して、このコードはチケットをissue `wrong_size`で返品チームに割り当て、billingチームの0.35のシェアが0.25の閾値を超えているためコピーを送り、解決の確信度0.20が0.5を下回るため顧客に何を望むかを質問します。コードは`shipping_issue`の回答を使用しません。

1つのリクエスト、5つの回答、そしてルーティングロジックは普通の`if`文です。後で顧客の言語やチケットがどの製品に関するものかを知る必要が生じた場合は、`TRIAGE_QUESTIONS`に別のChoice質問を追加するだけで、リクエスト数は1のままです。

[スマートホームアシスタントデモ](/demos/smart-home)では、すべてのユーザーリクエストを1回の呼び出しでChoice質問の長いリスト（リクエストカテゴリー、部屋、デバイス、アクション）に対して評価します。それらの質問のほとんどはどの1つのリクエストにも関係なく、コードはそれらを無視します。

## 構造化されたinstructionsとcriteria {#structured-instructions-and-criteria}

まずオプションごとに1行の説明から始めてください。2つのオプションが似ていてモデルが混同し続ける場合は、文字列の代わりにオブジェクトで各オプションを説明してください。そのオプションがカバーする内容、隣接するオプションに属する内容、いくつかの入力例のフィールドを与えてください。

以下の2つの回答オプション、return\_policyとreturn\_statusは混同しやすいです。どちらかについてのチケットは返品や払い戻しについて言及する可能性があるため、各オプションはそれが何のためではないかを述べています。

```json title="request" theme={null}
{
  "state": "I sent the shoes back a week ago. When do I get my money?",
  "questions": {
    "return_topic": {
      "type": "choice",
      "instructions": {
        "question": "Which returns topic is the customer asking about?",
        "focus": "Classify the information the customer wants."
      },
      "criteria": {
        "return_policy": {
          "what": "Whether and how an item can be returned",
          "not_for": "Progress of a return already sent",
          "examples": [
            "Can I return shoes I've worn once?",
            "How long do I have to return an order?"
          ]
        },
        "return_status": {
          "what": "Progress of a return already sent",
          "not_for": "Whether and how an item can be returned",
          "examples": [
            "Has my return arrived yet?",
            "When will my refund be paid?"
          ]
        }
      }
    }
  }
}
```

レスポンスは確信度1.0で`return_status`です：

```json theme={null}
{
  "model": "jev-1.13.0",
  "answers": {
    "return_topic": {
      "type": "choice",
      "choice": "return_status",
      "confidence": 1.0,
      "probabilities": {
        "return_policy": 0.0,
        "return_status": 1.0
      }
    }
  },
  "usage": {
    "input_tokens": 407,
    "output_tokens": 32
  }
}
```

フィールド名`question`、`focus`、`what`、`not_for`、`examples`はAPIの一部ではなく、予約済みのものもありません。オプション名を選ぶのと同じように、あなたが選択します。モデルは名前と値の両方を見るため、後に続く内容をラベル付けする短い名前を使用してください。