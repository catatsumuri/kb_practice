# Choice {#choice}

> Choiceは、定義されたセットから1つのオプションを選択するためのSystem Oneの質問タイプです。回答には、選択されたオプション、各オプションの確率、および確信度が含まれます。

回答が固定されたオプションのセットのいずれかである場合にChoiceを使用します。たとえば、チケットを処理するチーム、製品が属するカテゴリ、またはコードスニペットが記述されているプログラミング言語などです。回答がスペクトル上の位置である場合は[Score](/primitives/score)を、はいかいいえの場合は[Noul](/primitives/noul)を使用します。[質問タイプを選ぶ](/primitives#choose-a-question-type)では3つすべてを比較しています。

Choice回答は、`choice`に選択されたオプションが入ります。モデルはまた、`probabilities`にすべてのオプションの確率を返し、選択されたオプションの`confidence`値も返します。

質問の例：

```
"このコードはどのプログラミング言語で書かれていますか"
  → options: python, javascript, typescript, go, rust, other

"タイトルと説明に基づいて、これはどのタイプの会議ですか"
  → options: standup, planning, retrospective, one on one, brainstorm, none of the above

"この商品はどの製品カテゴリに属しますか"
  → options: electronics, clothing, home garden, food and beverage
```

## リクエストの構造 {#request-structure}

[TypeSafe API](/api)へのPOSTリクエストボディには特定の構造があります。トップレベルには3つのフィールドがあります：評価するコンテンツである`state`、`model`、そして自分で選んだ質問IDから質問オブジェクトへのマップである`questions`です。各Choice質問には以下のフィールドがあります：

* `type`：常に`"choice"`。
* `instructions`：モデルが回答する質問。
* `criteria`：マップとして表された回答オプション。各キーはオプション名で、各値はそのオプションの説明です。

以下は、状態がオンラインシューズショップのサポートチケットで、質問がどのチームが対応すべきかを問うリクエストの例です：

```json title="リクエスト" theme={null}
{
  "state": "注文したランニングシューズのサイズが違いました。サイズ10に交換してもらえますか？",
  "questions": {
    "department": {
      "type": "choice",
      "instructions": "どのチームがこれを担当すべきですか？",
      "criteria": {
        "returns": "交換、誤った商品または破損した商品",
        "shipping": "配送状況、遅延、紛失した荷物",
        "billing": "請求、請求書、支払いの問題"
      }
    }
  }
}
```

質問IDは自分で選びます（この場合は`department`）。回答は同じIDのもとで返されます。モデルは質問IDを見ることはありません。オプション名とその説明は両方ともモデルに送信されるので、オプション同士を区別できる説明を書いてください。

[クライアントSDK](/sdk)では型付きの質問が提供されます。Pythonでは、同じ質問は`Choice`になります：

```python theme={null}
from typesafe_sdk import Choice, TypeSafeClient

with TypeSafeClient() as client:
    response = client.system_one(
        state="注文したランニングシューズのサイズが違いました。サイズ10に交換してもらえますか？",
        questions={
            "department": Choice(
                instructions="どのチームがこれを担当すべきですか？",
                criteria={
                    "returns": "交換、誤った商品または破損した商品",
                    "shipping": "配送状況、遅延、紛失した荷物",
                    "billing": "請求、請求書、支払いの問題",
                },
            ),
        },
    )

    print(response.answers["department"].choice)
```

System Oneモデルを呼び出すには`system_one`メソッドまたは`https://api.typesafe.ai/v1/systemone`エンドポイントを使用します。`model`フィールドはリクエストを処理するモデルを選択します。[TypeSafeを使った開発方法](/concepts/how-to-build-with-system-one)では、コードのどこで呼び出すかを説明しています。

[クライアントSDK](/sdk)のいずれかを使用するか、[HTTP API](/api)を直接呼び出してください。コーディングエージェントが統合を書いている場合は、リクエストとレスポンスの形式を認識できるよう、最初に[TypeSafeエージェントスキル](/agent-skill#installation)をインストールしてください。

<Note>
  `instructions`および`criteria`の各エントリは、文字列、オブジェクト、または配列にすることができます。まず文字列から始めてください。説明にオプションがカバーする内容、カバーしない内容、いくつかの例など複数の種類のガイダンスが必要な場合はオブジェクトを使用します。下記の[構造化されたinstructionsとcriteria](#structured-instructions-and-criteria)および[APIリファレンス](/api#param-instructions-1)を参照してください。
</Note>

## レスポンスの構造 {#response-structure}

レスポンスには、リクエストのIDをキーとして、質問ごとに1つのエントリが`answers`に含まれます。上記のリクエスト例に対するレスポンスは以下のとおりです：

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

`type`以外に、各Choice回答には3つの値があります：

* `choice`：最も高い確率を持つオプション。
* `probabilities`：すべてのオプションにわたる完全な確率分布。全ての値の合計は1です。
* [`confidence`](/confidence)：`probabilities`の広がり方から計算される0から1の数値。複数のオプションに確率が分散したフラットな形状は低い確信度を意味します。1つのオプションに単一のピークがある場合は高い確信度を意味します。

このチケットは簡単なものなので、確率はすべて`returns`にあり、確信度は1.0です。サイズ違いと返金未払いの両方に言及するチケットは、`returns`と`billing`の間で確率が分かれ、確信度は下がります。

## グッドプラクティス：1回の呼び出しで複数の質問をする {#good-practice-ask-more-than-one-question-per-call}

質問ごとに1つのリクエストを送るのではなく、コードが必要とする可能性のあるすべてのChoice質問を1つのリクエストにまとめて送ってください。質問は並行して評価されます。質問を追加してもレスポンス時間はほとんど変わらず、コードは不要な回答を無視できます。追加の質問はトークンを消費します。[複数の質問をまとめて聞く](/primitives#ask-multiple-questions-together)では詳細を説明しており、次のセクションでは1回の呼び出しで5つのChoice質問を行う例を示します。

同じロジックが1つのChoice質問内のオプションにも適用されます。Choice質問は最大255個のオプションを受け付け、オプションを追加するごとに少数のトークンがかかるため、ショートリストではなくチーム、カテゴリ、または製品のフルリストをモデルに提供してください。リストがすべての入力をカバーできないかもしれない場合は`other`または`none of the above`オプションを追加して、モデルがどれも当てはまらないと答えられるようにしてください。

深い階層や大規模なタクソノミーでドキュメントを分類するには、Choice質問をレベルごとにチェーンします。[階層的分類クックブック](/cookbooks/hierarchical_classification)では、単一のgreedy pathにコミットするのではなく、各レベルで最良の`K`個の候補パスを保持しながら、Choice確率に対してビームサーチを実行する方法を示しています。

## より複雑な例 {#a-more-complex-example}

上記の基本的な例はチケットをチームにルーティングします。より大きなサポートシステムでは、返品理由、配送問題、顧客が望んでいること、および顧客のトーンも必要になるかもしれません。

以下のリクエストは、最初のものよりも曖昧なチケット（3つのチームに関わり、顧客が何を望んでいるかが不明）について5つのChoice質問をしています。

```json title="リクエスト" theme={null}
{
  "state": "靴が2週間遅れて届き、しかもサイズが違いました。さらに、カードに120ドルの請求が2件あります。どうするつもりですか？",
  "questions": {
    "department": {
      "type": "choice",
      "instructions": "どのチームがこれを担当すべきですか？",
      "criteria": {
        "returns": "交換、誤った商品または破損した商品",
        "shipping": "配送状況、遅延、紛失した荷物",
        "billing": "請求、請求書、支払いの問題"
      }
    },
    "return_reason": {
      "type": "choice",
      "instructions": "顧客が何かを返品したい場合、その理由は何ですか？",
      "criteria": {
        "wrong_size": "商品のサイズが合わない",
        "wrong_item": "異なる商品が届いた",
        "damaged": "商品が壊れているか不良品で届いた",
        "changed_mind": "商品は問題ないが、顧客がもう必要としない",
        "other": "上記のいずれにも当てはまらない返品理由"
      }
    },
    "shipping_issue": {
      "type": "choice",
      "instructions": "これが配送の問題である場合、どの種類ですか？",
      "criteria": {
        "not_delivered": "荷物が届かなかった",
        "delayed": "荷物が遅れているが、まだ配送中",
        "wrong_address": "荷物が間違った場所に届いた",
        "damaged_in_transit": "荷物が破損した状態で届いた",
        "other": "上記のいずれにも当てはまらない配送の問題"
      }
    },
    "requested_resolution": {
      "type": "choice",
      "instructions": "顧客は何を望んでいますか？",
      "criteria": {
        "exchange": "別の商品と交換する",
        "refund": "返金",
        "replacement": "同じ商品を再送する",
        "information": "回答だけが必要で、アクションは不要"
      }
    },
    "tone": {
      "type": "choice",
      "instructions": "顧客のトーンはどうですか？",
      "criteria": {
        "calm": null,
        "frustrated": null,
        "angry": null
      }
    }
  }
}
```

これらのChoice質問のうち2つは投機的なものです：`return_reason`は`department`が`returns`の場合にのみ意味があり、`shipping_issue`は`shipping`の場合にのみ意味があります。`tone`質問はオプション名だけで意味が明確なため`null`の説明を使用しています。

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

各質問はチケットに対して独立して回答されます：

* `department`の回答は確率0.61で`returns`ですが、二重請求のために`billing`が0.35を持っています。チケットは2つのチームに関わっており、確信度0.42の分裂がそれを反映しています。
* `return_reason`は確信度1.0で`wrong_size`であり、チケットにそれが明確に記されているため、これは予想通りです。
* `shipping_issue`の回答は`delayed`と`other`の間で分かれています。これは投機的な質問で、`department`が`shipping`として返ってこなかったため、コードで無視できます（下記のサンプルコードスニペットを参照）。
* `requested_resolution`の回答は0.40で`refund`に傾いており、`replacement`と`exchange`が残りのほとんどを共有しており、確信度は0.20です。二重請求は返金を示唆し、サイズ違いは交換を示唆し、顧客はどちらが望ましいかを述べていません。
* `tone`の回答は確率0.84、確信度0.76で`frustrated`です。

以下のサンプルコードは必要な回答を読み取り、残りを無視し、確信度の低い回答を行動するのではなく確認を求める理由として扱います：

```python theme={null}
from typesafe_sdk import Choice, TypeSafeClient

TRIAGE_QUESTIONS = {
    "department": Choice(
        instructions="どのチームがこれを担当すべきですか？",
        criteria={
            "returns": "交換、誤った商品または破損した商品",
            "shipping": "配送状況、遅延、紛失した荷物",
            "billing": "請求、請求書、支払いの問題",
        },
    ),
    "return_reason": Choice(
        instructions="顧客が何かを返品したい場合、その理由は何ですか？",
        criteria={
            "wrong_size": "商品のサイズが合わない",
            "wrong_item": "異なる商品が届いた",
            "damaged": "商品が壊れているか不良品で届いた",
            "changed_mind": "商品は問題ないが、顧客がもう必要としない",
            "other": "上記のいずれにも当てはまらない返品理由",
        },
    ),
    "shipping_issue": Choice(
        instructions="これが配送の問題である場合、どの種類ですか？",
        criteria={
            "not_delivered": "荷物が届かなかった",
            "delayed": "荷物が遅れているが、まだ配送中",
            "wrong_address": "荷物が間違った場所に届いた",
            "damaged_in_transit": "荷物が破損した状態で届いた",
            "other": "上記のいずれにも当てはまらない配送の問題",
        },
    ),
    "requested_resolution": Choice(
        instructions="顧客は何を望んでいますか？",
        criteria={
            "exchange": "別の商品と交換する",
            "refund": "返金",
            "replacement": "同じ商品を再送する",
            "information": "回答だけが必要で、アクションは不要",
        },
    ),
    "tone": Choice(
        instructions="顧客のトーンはどうですか？",
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
        # どのチームに送るか不明。人が判断する。
        send_to_manual_triage(ticket)
        return

    if department.choice == "returns":
        # return_reasonの回答はここでのみ使用される
        assign(ticket, team="returns", issue=answers["return_reason"].choice)
    elif department.choice == "shipping":
        # shipping_issueの回答はここでのみ使用される
        assign(ticket, team="shipping", issue=answers["shipping_issue"].choice)
    else:
        assign(ticket, team="billing")

    # 確率の実質的なシェアを持つ2番目のチームにコピーを送る
    for team, probability in department.probabilities.items():
        if team != department.choice and probability > 0.25:
            notify(ticket, team=team)

    resolution = answers["requested_resolution"]
    if resolution.confidence < 0.5:
        # 顧客が何を望んでいるか不明。推測せず、確認する。
        ask_customer_what_they_want(ticket)
    elif resolution.choice == "refund":
        flag_for_refund_approval(ticket)

    if answers["tone"].choice == "angry":
        flag_for_senior_agent(ticket)
```

上記のチケットに対して、このコードはチケットをreturnsチームに`wrong_size`の問題として割り当て、0.35のシェアが0.25の閾値を超えているためbillingチームにコピーを送り、解決の確信度0.20が0.5を下回っているため顧客に何を望んでいるか確認します。コードは`shipping_issue`の回答を使用しません。

1回のリクエスト、5つの回答、そしてルーティングロジックは通常の`if`文です。後で顧客の言語やチケットに関する製品を知る必要が生じた場合は、`TRIAGE_QUESTIONS`に別のChoice質問を追加するだけで、リクエスト数は1のままです。

[スマートホームアシスタントデモ](/demos/smart-home)では、リクエストカテゴリ、部屋、デバイス、アクションなど、1回の呼び出しで長いChoice質問リストに対してすべてのユーザーリクエストを評価します。これらの質問のほとんどはどの1つのリクエストにも無関係であり、コードはそれらを無視します。

## 構造化されたinstructionsとcriteria {#structured-instructions-and-criteria}

まず、オプションごとに1行の説明から始めてください。2つのオプションが似ていてモデルが混同し続ける場合は、文字列の代わりにオブジェクトで各オプションを説明してください。オプションがカバーする内容、隣接するオプションに属する内容、いくつかの入力例のフィールドを設定してください。

以下の2つの回答オプション、return\_policyとreturn\_statusは混同しやすいものです。どちらに関するチケットも返品や返金に言及する可能性があるため、各オプションには何を対象としていないかを記載しています。

```json title="リクエスト" theme={null}
{
  "state": "1週間前に靴を返送しました。いつお金が戻りますか？",
  "questions": {
    "return_topic": {
      "type": "choice",
      "instructions": {
        "question": "顧客はどの返品トピックについて質問していますか？",
        "focus": "顧客が求めている情報を分類してください。"
      },
      "criteria": {
        "return_policy": {
          "what": "商品を返品できるかどうか、およびその方法",
          "not_for": "すでに送付済みの返品の進捗",
          "examples": [
            "一度履いた靴を返品できますか？",
            "注文の返品期限はいつですか？"
          ]
        },
        "return_status": {
          "what": "すでに送付済みの返品の進捗",
          "not_for": "商品を返品できるかどうか、およびその方法",
          "examples": [
            "返品品はもう届きましたか？",
            "いつ返金されますか？"
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

フィールド名`question`、`focus`、`what`、`not_for`、`examples`はAPIの一部ではなく、予約済みでもありません。オプション名を選ぶのと同じように、自分で選びます。モデルは名前と値の両方を見るので、続く内容をラベル付けする短い名前を使用してください。