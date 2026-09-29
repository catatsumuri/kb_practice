# Noul {#noul}

> Noulの質問は、TypeSafeモデルにyes/noの質問を評価させ、答えがyesである確率を返させます。

答えがyesまたはnoである場合にNoulを使用します。例えば、このメッセージは返金を求めているか、この履歴書は分散システムに言及しているか、このコメントには個人データが含まれているか、などです。答えがいくつかの選択肢のうちの1つである場合は[Choice](/primitives/choice)を使用します。スペクトル上の位置である場合は[Score](/primitives/score)を使用します。[質問タイプを選ぶ](/primitives#choose-a-question-type)では3つすべてを比較しています。

Noulの答えは、答えがyesである確率を表す単一の数値で、0はnoを、1はyesを意味します。

## リクエスト構造 {#request-structure}

[TypeSafe API](/api)へのPOSTリクエストボディは、他の質問タイプと同じ3つのトップレベルフィールドを持ちます: 評価するコンテンツである`state`、`model`、そして`questions`です。各Noulの質問には以下のフィールドがあります:

* `type`: 常に`"noul"`。
* `instructions`: モデルが答えるyes/noの質問、またはモデルが判断する文。
* `criteria`: 省略可能。yesとnoが何を意味するかを説明する`true`と`false`の説明を持つオブジェクト。

以下は、状態がサポートメッセージで、2つの質問がお客様が担当者を求めているかどうかと、以前にサポートに連絡したことがあるかどうかを尋ねるリクエストです:

```json title="リクエスト" theme={null}
{
  "state": "もう3回も聞いています。実際の担当者と話せませんか？",
  "questions": {
    "is_human_escalation": {
      "type": "noul",
      "instructions": "お客様は人間のエージェントを求めていますか？"
    },
    "is_repeat_contact": {
      "type": "noul",
      "instructions": "お客様は以前にこの件についてサポートに連絡したことがありますか？",
      "criteria": {
        "true": "以前の試み、チケット、または以前に聞いたことに言及している",
        "false": "以前の連絡の痕跡がない"
      }
    }
  }
}
```

質問のIDはここでは`is_human_escalation`と`is_repeat_contact`で、あなたが選びます。IDはモデルに送信されません。各答えは同じIDの下に返されます。最初の質問は`instructions`のみに依存します。2番目は`criteria`を追加して、yesとnoの条件を説明しています。

[Python SDK](/sdk/python)では、同じ質問が`Noul`オブジェクトになります:

```python theme={null}
from typesafe_sdk import Noul, NoulCriteria, TypeSafeClient

with TypeSafeClient() as client:
    response = client.system_one(
        model="jev-latest",
        state="もう3回も聞いています。実際の担当者と話せませんか？",
        questions={
            "is_human_escalation": Noul(
                instructions="お客様は人間のエージェントを求めていますか？",
            ),
            "is_repeat_contact": Noul(
                instructions="お客様は以前にこの件についてサポートに連絡したことがありますか？",
                criteria=NoulCriteria(
                    true="以前の試み、チケット、または以前に聞いたことに言及している",
                    false="以前の連絡の痕跡がない",
                ),
            ),
        },
    )

    print(response.answers["is_human_escalation"].noul)
    print(response.answers["is_repeat_contact"].noul)
```

`system_one`メソッドと`https://api.typesafe.ai/v1/systemone`エンドポイントは、どちらもTypeSafeのAIモデルである[System One](/concepts/system-one)にちなんで名付けられています。[TypeSafeを使ってビルドする方法](/concepts/how-to-build-with-system-one)では、コード内のどこで使用するかを説明しています。

コーディングエージェントを使用している場合は、まず[TypeSafeエージェントスキル](/agent-skill#installation)をインストールして、リクエストとレスポンスの形式を認識させてください。

<Note>
  `instructions`は文字列、オブジェクト、または配列にできます。文字列から始めてください。質問に付随するデータ（状態と比較するレコードなど）が必要な場合や、質問の一部がコードで構築される場合は、オブジェクトを使用します。[質問に構造を使用する](/concepts/how-to-build-with-system-one#use-structure-in-the-questions)では構造が役立つ場合を説明しており、[以下の例](#structured-instructions)ではコードで構築された質問を示しています。
</Note>

## レスポンス構造 {#response-structure}

レスポンスには、リクエストのIDの下に、質問ごとに1つの`answers`エントリがあります:

```json theme={null}
{
  "model": "jev-1.13.0",
  "answers": {
    "is_human_escalation": {
      "type": "noul",
      "noul": 0.99
    },
    "is_repeat_contact": {
      "type": "noul",
      "noul": 0.93
    }
  },
  "usage": {
    "input_tokens": 360,
    "output_tokens": 39
  }
}
```

ここでは両方の答えが1に近いです。お客様が「実際の担当者と話したい」と言っているため、`is_human_escalation`は0.99です。「もう3回も聞いています」は`is_repeat_contact`の`true`の説明に一致するため、0.93になっています。

## Noulを読む {#reading-a-noul}

この数値は答えと確信度を一体化したものです。1に近い値は強いyesです。0に近い値は強いnoです。0.5に近い値は、モデルがyesとnoに同程度の確率を与えていることを意味します。

以下の表は、異なるお客様のメッセージに対する`is_human_escalation`質問への`jev-1.13.0`の記録済み答えを示しています:

| 状態 | `noul` |
| - | - |
| ありがとうございます、解決しました！ | 0.02 |
| パスワードをリセットするにはどうすればいいですか？ | 0.07 |
| 今日中に解決が必要です、何でもします。 | 0.26 |
| あなたはボットですか？ | 0.40 |
| 請求書について誰かと話す方法はありますか？ | 0.84 |
| もう3回も聞いています。実際の担当者と話せませんか？ | 0.99 |

最初の2つと最後の2つは明確です。「今日中に解決が必要です」は緊急ですが、担当者を求めることはなく、0.26になっています。「あなたはボットですか？」は人間を求めるヒントを含んでいますが直接求めてはおらず、モデルはほぼ均等に0.40と判断しています。どちらも、コード内のしきい値に基づいて判断が必要なメッセージです。

Noulには、[Choice](/primitives/choice)や[Score](/primitives/score)とは異なり、独立した`confidence`値はありません。Noulの確率分布はyesとnoの2つの結果しかないため、単一の`noul`値で完全に表現されます。ChoiceやScoreは確率を複数の選択肢やレベルに分散させるため、`confidence`はその分散を要約します。

多くの場合、コードは`noul`をブール値にしきい値処理します:

```python theme={null}
wants_human = response.answers["is_human_escalation"].noul > 0.9

if wants_human:
    route_to_agent(ticket)
else:
    route_to_bot(ticket)
```

しきい値の設定は間違いのコストによります。yesとnoが同程度に対処しやすい場合は0.5を使用します。偽のyesで行動することが高コストな場合（誰かへの通知や返金など）は上げます。真のyesを見逃すことが高コストな場合（安全上の問題のフラグ付けの失敗など）は下げます。中間の値はどちらのコードパスにも行かず、人間に渡すことができます。これは[確信度](/confidence#three-paths-for-using-confidence-in-your-code)ページがChoiceとScoreの答えについて説明している3方向の分割と同じです。

Noulの値は0から1の範囲ですが、尋ねた事柄のスケールではありません。それは答えがyesである確率です。質問が実際には程度に関するものであれば、その値は程度を測定しません。以下では、4人の候補者について「この候補者はPythonが得意ですか？」を尋ね、経験なし、ある程度の知識、仕事での日常的な使用、深い専門知識の4レベルを持つ[Score](/primitives/score)と並べています。

| 候補者 | Noul:「この候補者はPythonが得意ですか？」 | Score:「この候補者のPython経験はどの程度ですか？」 |
| - | - | - |
| 私の経験はJavaとGoです。Pythonは使ったことがありません。 | 0.03 | 0.0（経験なし） |
| 主なJavaの仕事と並行して、小さなスクリプトに時々Pythonを使ったことがあります。 | 0.14 | 1.0（ある程度の知識） |
| 前職では主にデータパイプラインを中心に、2年間毎日Pythonを使用しました。 | 0.81 | 2.05（仕事での日常的な使用） |
| 大規模なDjangoコードベースの保守を含め、8年間毎日Pythonを書いています。 | 0.92 | 2.89（深い専門知識） |

Noulは「得意」という1つの命題を判断し、その値がどれだけ可能性があるかを示します。コード内で0から1の範囲にレベルを作成することもできます（例えば、「ある程度の経験」に0.3から0.7など）が、モデルはそれらを見ないため、答えの何もそれらに対して判断されていません。中間の値は中程度の経験を意味することも、不明確なケースを意味することもあり、候補者間の間隔はあなたが選んだものではありません。Scoreは各レベルの説明を個別に判断するため、すべての候補者はあなたが書いたレベルの上またはその近くに位置し、返された確率はモデルがどのようにレベル間で判断を分けたかを示します。異議がある場合は、レベルの表現を変えて再実行してください。[質問タイプを選ぶ](/primitives#choose-a-question-type)でその違いを説明しています。

## Noulの質問を書く {#writing-a-noul-question}

1つのNoulにつき1つのyes/no質問を尋ねます。「お客様は怒っていて返金を求めていますか？」のように2つの条件がある場合、モデルは両方を同時に判断しなければならず、値の意味が薄れます。2つのNoulを尋ねて、コードで組み合わせてください。

高い値がyesを意味するように質問を表現します。「このメッセージには個人データが含まれていますか？」は明確です。「このメッセージは個人データがありませんか？」は意味を反転させ、後でそれを読むコードが逆に理解してしまいます。

質問と同様に文も機能します。「お客様は返金を求めています」という文に対して、1に近い値はその文が真であることを意味します。自分のデータで両方の表現を試して、どちらが良いか確認してください。

yesとnoの境界を明確にします。「この候補者はPythonの経験がありますか？」は「any（いくつかでも）」が中間地帯を残さないため、うまく機能します。境界が微妙な場合は、上の`is_repeat_contact`の質問のように、`true`と`false`の説明を持つ`criteria`を追加します。多くのNoulでは`instructions`だけで十分なので、`criteria`ありとなしの両方で質問を試して、ドキュメントにより良い答えを返す方を使用してください。

## グッドプラクティス: 1回の呼び出しで複数の質問をする {#good-practice-ask-more-than-one-question-per-call}

条件のチェックリストには、1つのリクエストで多くのNoulの質問を尋ねます: 条件ごとに1つの質問、そしてコードがその組み合わせの意味を判断します。質問は並行して評価されるため、Noulを追加しても応答時間はほとんど変わりません。[複数の質問をまとめて尋ねる](/primitives#ask-multiple-questions-together)でこれについて詳しく説明しています。

## コードで複数のNoulの答えを処理する {#handling-multiple-noul-answers-in-code}

上記の2つの質問のリクエストにより、コードはメッセージをルーティングするのに十分な情報を得られます。以下の例は、お客様が担当者を求めたときにエスカレーションし、以前に連絡したことがある場合に優先度を上げます。どちらかの質問で中間の値が出た場合は、コードパスではなくレビュアーに送ります:

```python theme={null}
from typesafe_sdk import Noul, NoulCriteria, TypeSafeClient

SUPPORT_QUESTIONS = {
    "is_human_escalation": Noul(
        instructions="お客様は人間のエージェントを求めていますか？",
    ),
    "is_repeat_contact": Noul(
        instructions="お客様は以前にこの件についてサポートに連絡したことがありますか？",
        criteria=NoulCriteria(
            true="以前の試み、チケット、または以前に聞いたことに言及している",
            false="以前の連絡の痕跡がない",
        ),
    ),
}

YES = 0.8
NO = 0.2


def route(message: str) -> None:
    with TypeSafeClient() as client:
        response = client.system_one(
            model="jev-latest",
            state=message,
            questions=SUPPORT_QUESTIONS,
        )
    answers = response.answers

    wants_human = answers["is_human_escalation"].noul
    repeat = answers["is_repeat_contact"].noul

    if NO < wants_human < YES or NO < repeat < YES:
        # モデルはどちらとも判断できていません。人間に判断させます。
        send_to_review(message)
        return

    priority = "high" if repeat > YES else "normal"
    if wants_human > YES:
        route_to_agent(message, priority=priority)
    else:
        route_to_bot(message, priority=priority)
```

上記のメッセージでは、`is_human_escalation`のnoul答え値は0.99、`is_repeat_contact`は0.93であるため、コードは高優先度でエージェントにルーティングします。「パスワードをリセットするにはどうすればいいですか？」というメッセージは両方の質問で0.07であり、ボットにルーティングされます。

しきい値はコード内にあります。レビュアーへのメッセージが多すぎる場合は、`NO`と`YES`の間隔を狭めます。間違ったルートが多すぎる場合は広げます。後でメッセージに支払いへの言及があるかどうか、または個人データが含まれているかどうかを知る必要が生じた場合は、別のNoulを`SUPPORT_QUESTIONS`に追加します。リクエスト数は1のままです。

## 構造化されたinstructions {#structured-instructions}

instructionsは文字列の代わりにオブジェクトにでき、1つのフィールドに質問、他のフィールドに補足データを含めます。[質問に構造を使用する](/concepts/how-to-build-with-system-one#use-structure-in-the-questions)では、それが役立つ場合を説明しています。ここでは、コードを使って構築された質問に使用します: 届いたばかりの履歴書を、同一人物の可能性がある候補者データベースのレコードと比較します。各レコードはそのまま`potential_duplicate`フィールドに入れられ、`question`はすべてのレコードで同じであり、すべてのレコードが1つのリクエストでチェックされます。コードで生成された質問キーには各レコードのデータベースIDが含まれています:

```json title="リクエスト" theme={null}
{
  "state": {
    "resume": {
      "name": "John Smith",
      "location": "Oakland, CA",
      "summary": "PythonとGoで8年の経験を持つバックエンドエンジニア。",
      "experience": [
        {
          "employer": "Google",
          "title": "シニアバックエンドエンジニア",
          "years": "2021-2025"
        },
        {
          "employer": "Microsoft",
          "title": "ソフトウェアエンジニア",
          "years": "2017-2021"
        }
      ]
    }
  },
  "questions": {
    "same_as_record_18": {
      "type": "noul",
      "instructions": {
        "potential_duplicate": {
          "name": "Jon Smith",
          "location": "Oakland, CA",
          "last_employer": "Google"
        },
        "question": "この履歴書は`potential_duplicate`と同一人物のものですか？"
      }
    },
    "same_as_record_42": {
      "type": "noul",
      "instructions": {
        "potential_duplicate": {
          "name": "John Smith",
          "location": "Austin, TX",
          "last_employer": "Lone Star Freight"
        },
        "question": "この履歴書は`potential_duplicate`と同一人物のものですか？"
      }
    },
    "same_as_record_77": {
      "type": "noul",
      "instructions": {
        "potential_duplicate": {
          "name": "John Smithers",
          "location": "Oakland, CA",
          "last_employer": "Bay Health Clinic"
        },
        "question": "この履歴書は`potential_duplicate`と同一人物のものですか？"
      }
    }
  }
}
```

レスポンス:

```json theme={null}
{
  "model": "jev-1.13.0",
  "answers": {
    "same_as_record_18": {
      "type": "noul",
      "noul": 0.74
    },
    "same_as_record_42": {
      "type": "noul",
      "noul": 0.09
    },
    "same_as_record_77": {
      "type": "noul",
      "noul": 0.08
    }
  },
  "usage": {
    "input_tokens": 535,
    "output_tokens": 58
  }
}
```

各答えは、その履歴書がそのレコードの人物のものである確率です。レコード18は名前のスペルが異なりますが、場所と雇用主が一致しており、0.74になっています。レコード42は同じ名前ですが別の都市で異なる雇用主であり、0.09になっています。レコード77は似た名前で同じ場所ですが異なる雇用主であり、0.08になっています。[コードで複数のNoulの答えを処理する](#handling-multiple-noul-answers-in-code)のように、コード内で各値をしきい値処理し、中間値は人間に送ります。

Python SDKでは、質問は候補者レコードから構築されます。質問テキストは固定で、レコードが変わります:

```python theme={null}
from typesafe_sdk import Noul, TypeSafeClient

SAME_PERSON = "この履歴書は`potential_duplicate`と同一人物のものですか？"


def duplicate_questions(candidates: list[dict]) -> dict[str, Noul]:
    """候補者レコードごとに1つのNoul、すべて同じ質問をします。"""
    return {
        f"same_as_record_{candidate['id']}": Noul(
            instructions={
                "potential_duplicate": {
                    "name": candidate["name"],
                    "location": candidate["location"],
                    "last_employer": candidate["last_employer"],
                },
                "question": SAME_PERSON,
            },
        )
        for candidate in candidates
    }


def find_duplicates(resume: dict, candidates: list[dict]) -> list[str]:
    with TypeSafeClient() as client:
        response = client.system_one(
            model="jev-latest",
            state={"resume": resume},
            questions=duplicate_questions(candidates),
        )
    return [
        question_id
        for question_id, answer in response.answers.items()
        if answer.noul > 0.7
    ]
```

[構造化データ抽出カスケードのクックブック](/cookbooks/sde_cascade)では、抽出されたレコードを検証するために構造化されたinstructionsを使用しています。すべてのフィールドに同じ質問セットが与えられます。各質問の`instructions`オブジェクトには`main_question`プロパティに質問テキストがあります。また、フィールドごとに変わる`field_spec`と`extracted_field`プロパティもあります。

## クックブックでのNoul {#noul-in-the-cookbooks}

Noulの質問を使用したアプリを見るために、クックブックをご覧ください:

* [並行質問](/cookbooks/parallel_questions)は、1つのリクエストで1つの記事に対して13問の規制チェックリストを実行します。
* [自己一貫性: noul](/cookbooks/consistency_noul_cookbook)は、保険請求を15問のルーブリックで採点し、実行間で値がどれほど安定しているかを測定します。
* [再ランキング](/cookbooks/rerank_typesafe)は確率そのものを使用し、しきい値処理は行いません: クエリと候補のペアごとに1つのNoulを設定し、その値で候補をソートします。
* [行ごとの検索](/cookbooks/semantic_find)は、一致する行を見つけるChoiceと、ドキュメントに答えが含まれているかどうかをチェックするNoulを組み合わせています。
* [構造の復元](/cookbooks/autoformat)は行のペアごとに1つのNoulを尋ね、改行が文を分割したかどうかを判断して、プレーンテキストから段落を再構築します。