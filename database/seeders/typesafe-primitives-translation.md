# プリミティブ（質問） {#primitives-questions}

> TypeSafeの3種類の質問タイプ（Choice、Score、Noul）、それぞれが返す型付き回答、選び方、複数まとめて質問する方法。

TypeSafeのプリミティブは、コード上で組み合わせる小さな型付きビルディングブロックです。ペアで提供されます：質問は[System Oneモデル](/concepts/system-one)が[状態](/concepts/state)について行う1つの判断を定義し、その回答は返ってくる型付きの値です。コード内で回答を組み合わせて意思決定を行います。質問タイプは3種類あり、それぞれ異なる形の回答を返します。

| タイプ | 答えるもの | 返り値 |
| - | - | - |
| [Choice](/primitives/choice) | これらのオプションのどれか？ | `choice`、`probabilities`、`confidence` |
| [Score](/primitives/score) | どのレベルか？ | `score`、`legend`、`probabilities`、`confidence` |
| [Noul](/primitives/noul) | これは真か？ | `noul`（0から1） |

質問を1つ送ることも、複数まとめて送ることもできます。リクエスト内のすべての質問は同じ状態を参照し、独立して評価され、選択したIDのもとに型付き回答を返します。

## 質問ごとに1つの瞬間的な判断を求める {#ask-for-one-snap-judgment-per-question}

System Oneモデルは、高速で焦点を絞った判断のために設計されています。適切なコンテキストが与えられた知識ある人が1秒で行う判断を求めてください。「このメッセージは緊急性を伝えているか？」は良い質問です。「このメッセージを分析して最善の対応を決定してください」はそうではありません。それは遅い推論を必要とし、タスクを小さな質問に分解してコードで回答を組み合わせるべきサインです。

求めたい判断が複数の独立した要因に依存する場合、各要因について個別に質問し、独自のロジックで回答を組み合わせます。「このスタートアップのピッチを評価してください」の代わりに、市場規模、技術的な実現可能性、差別化について個別に質問し、相対的な重要度に基づいてコード内で重み付けします。優先度が変わったときは、プロンプトを書き直すのではなく、コード内の重みの値を変更します。これを実現する方法は[複数の質問をまとめて質問する](#ask-multiple-questions-together)に示しています。

## 質問を定義する {#define-a-question}

すべての質問にはID、`type`、`instructions`があります。ChoiceとScoreの質問には`criteria`も指定します。Choiceの場合はオプションを、Scoreの場合はレベルを定義します。Noulの質問では、「はい」と「いいえ」の意味を明確にするオプションの補足として`criteria`を使えます。

* ID。`refund_requested`のような自分で選ぶキー。レスポンス内で回答を識別します。
* `type`。`choice`、`score`、`noul`のいずれか。
* `instructions`。状態について質問する内容。評価ロジックはここに記述します。明確で具体的な質問、またはモデルが判断する文として書きます。ほとんどの質問では文字列で十分です。オブジェクトや配列にすることもでき、その場合は質問を1つのフィールドに、参照するデータを他のフィールドに格納します。詳しくは[質問に構造を使う](/concepts/how-to-build-with-system-one#use-structure-in-the-questions)を参照してください。
* `criteria`。可能な回答：Choiceの質問ではオプションのマップ、Scoreでは順序付きのレベルリスト、Noulでは「はい」と「いいえ」の任意の説明。各質問タイプのページでその形状について説明しています。

この質問は、顧客が返金を要求したかどうかを確認します：

```python theme={null}
from typesafe_sdk import Noul

questions = {
    "refund_requested": Noul(
        instructions="顧客は返金を求めていますか？",
    ),
}
```

<Tip>
  質問IDはコードのためのものです。モデルには送信されません。IDが自明に見える場合でも、`instructions`に完全な質問を書いてください。
</Tip>

## 質問タイプを選ぶ {#choose-a-question-type}

必要な回答の形に合ったタイプを選んでください。

* **Choice**は、回答が順序のない既知のオプションセットの1つである場合に適しています：チケットを部門にルーティングする、ドキュメントタイプを分類する、プログラミング言語を検出する。オプションの完全なリストを提供し、リストがすべての入力をカバーしない可能性がある場合は`other`や`none of the above`のオプションを追加してください。

* **Score**は、回答がスペクトル上に位置し、そのスペクトルの各点の意味を説明できる場合に適しています：バグの深刻度、顧客の不満度、スキルレベル。レベルは自分で定義し、モデルはそのレベル上の位置を返します。

* **Noul**は、確率自体が有用なシグナルとなる明確なyes/no質問に適しています：このメッセージに個人を特定できる情報が含まれているか、顧客が返金を要求しているか、履歴書に分散システムへの言及があるか。

<Note>
  yes/noの判断にはNoulを、スペクトル上の位置を測定するにはScoreを使用してください。「この候補者はPythonが得意か？」には「得意」の明確な定義が必要です。Noulの値が0.5であることは、モデルがyesとnoに等しい確率を与えることを意味します。候補者のスキルレベルが中程度であることを意味するのではありません。不明確な定義はその確率を解釈しにくくします。

  スキルレベルを測定したい場合は、経験なし、多少の知識あり、日常的に使用、深い専門知識などの定義されたレベルを持つScoreを使用してください。yes/noの判断が必要な場合は、「履歴書に候補者が職場でPythonを使用したと記載されているか？」のように条件を明確に定義してください。
</Note>

2つのタイプがどちらも適合する場合は、コードが直接操作できる回答を返すほうを選んでください。`refund`、`rebook`、`information`の間のChoiceは3つのコードパスに直接対応します。顧客の不満度のScoreは閾値に対応します。Noulは`if`文に対応します。

## 返ってくるもの {#what-comes-back}

回答もプリミティブです。各質問タイプは、コードで比較、閾値処理、ソート、さらなるロジックへの引き渡し、またはフォローアップリクエストの状態への格納ができる型付きの値を返します（[1つの質問が別の質問に依存する場合](#when-one-question-depends-on-another)を参照）。

| タイプ | 回答フィールド | 読み方 |
| - | - | - |
| Choice | `choice`、`probabilities`、`confidence` | `choice`は選択されたオプション。`probabilities`はすべてのオプションにわたる分布。`confidence`はその分布の集中度を要約します。 |
| Score | `score`、`legend`、`probabilities`、`confidence` | `score`はレベル上の位置で、2つのレベルの間に位置することもあります。`legend`は番号でレベルを繰り返します。`probabilities`はレベルにわたる分布。 |
| Noul | `noul` | 回答がyesである確率。1に近いほど強いyes、0に近いほど強いno、0.5に近いほど不確かです。Noulには別途`confidence`はありません。 |

これらの回答を組み合わせ可能にする2つの特性があります：

* **すべての回答は指定したオプションに制約されます。** モデルはオプションまたはレベルにわたる確率分布を返し、その外の値を返すことはありません。コードが生成されたテキストから値を復元する必要はありません。
* **すべての回答は独立しています。** 1つの質問の回答が別の質問の隠れたコンテキストになることはありません。他の質問の結果を変えることなく、質問を追加または削除できます。

[確信度](/confidence)では、`confidence`が`probabilities`からどのように導出されるか、そして自動的に実行するかエスカレーションするかを決定するためにどう使うかを説明しています。

## 特定のフィールドを参照する {#reference-specific-fields}

評価対象のコンテンツである[状態](/concepts/state)は、会話、レコード、ポリシーなど複数のパーツを持つJSONオブジェクトであることが多いです。質問がそれらのパーツの1つに関するものである場合、`instructions`内でそのキーへのドットとインデックスのパスをバッククォート付きで指定してください。モデルはどの状態のパーツを判断すべきかを認識します。

Stateページのサポート会話を例にとります：

```json theme={null}
{
  "ticket": {
    "subject": "二重請求",
    "messages": [
      {"from": "customer", "text": "注文A-104で二重に請求されました。重複分の返金をお願いします。"},
      {"from": "support", "text": "請求を確認しています。"}
    ]
  },
  "order": {
    "id": "A-104",
    "charges": [
      {"amount_usd": 49, "status": "captured"},
      {"amount_usd": 49, "status": "captured"}
    ]
  },
  "refund_policy": "二重請求は返金の対象となります。"
}
```

次の2つの質問は、顧客のメッセージ、ポリシー、請求をパスで参照しています：

```python theme={null}
questions = {
    "refund_requested": {
        "type": "noul",
        "instructions": "`ticket.messages[0].text`は返金を求めていますか？",
    },
    "policy_supports_refund": {
        "type": "noul",
        "instructions": (
            "`refund_policy`は`order.charges`を考慮した上で、"
            "`ticket.messages[0].text`で要求された返金を支持していますか？"
        ),
    },
}
```

明示的なパスにより、構造化された状態のどのパーツが各判断に情報を提供すべきかが明確になります。入力の構造化については[状態](/concepts/state)を参照してください。

## 複数の質問をまとめて質問する {#ask-multiple-questions-together}

同じ状態を使用するすべての質問を1つのリクエストで送信してください。質問タイプは自由に混在させることができます。System Oneモデルはリクエスト内のすべての質問を並列で評価します。質問を追加してもレスポンス時間はほとんど変わらず、追加質問のトークン分のコストしかかかりません（安価です）。必要になるかもしれない質問をしておくことはほぼ無料です。

このリクエストは、顧客メッセージの分類、緊急性の確認、不満度のスコアリングを一度に行います：

```json title="request" theme={null}
{
  "state": "約20分前からAPIインテグレーションがすべてのリクエストで500エラーを返し始め、修正されるまで顧客の注文を処理できません。",
  "questions": {
    "department": {
      "type": "choice",
      "instructions": "このリクエストを担当すべきチームを選んでください",
      "criteria": {
        "billing": "支払いやサブスクリプションの問題",
        "technical": "バグやインテグレーションの問題",
        "sales": "価格やアカウントに関する質問"
      }
    },
    "is_urgent": {
      "type": "noul",
      "instructions": "メッセージが緊急性や時間的切迫感を伝えている"
    },
    "frustration": {
      "type": "score",
      "instructions": "顧客がどの程度不満を持っているか",
      "criteria": [
        "落ち着いており、事実を述べているだけ",
        "不満はあるが礼儀正しい",
        "非常に怒っており、強い言葉を使っている"
      ]
    }
  }
}
```

[クライアントSDK](/sdk)は型付きの質問と回答を提供します。Pythonでは、`Choice`、`Noul`、`Score`オブジェクトの`questions`辞書を`client.system_one(...)`に渡します。このリクエストはチケットと返金ポリシーを一度送信し、各質問に対して型付き回答を取得します：

```python theme={null}
from typesafe_sdk import Choice, Noul, Score, TypeSafeClient

state = {
    "ticket_message": "フライトがキャンセルされました。返金してもらえますか？",
    "refund_policy": "キャンセルされたフライトは全額返金の対象となります。",
}

with TypeSafeClient() as client:
    response = client.system_one(
        state=state,
        questions={
            "refund_requested": Noul(
                instructions="`ticket_message`は返金を求めていますか？",
            ),
            "request_type": Choice(
                instructions="`ticket_message`の主なリクエストは何ですか？",
                criteria={
                    "refund": "顧客が返金を求めている。",
                    "rebooking": "顧客が代替フライトを求めている。",
                    "information": "顧客は情報を求めているだけ。",
                },
            ),
            "frustration": Score(
                instructions="`ticket_message`で顧客はどの程度不満を示していますか？",
                criteria=[
                    "落ち着いており、中立的。",
                    "懸念はあるが礼儀正しい。",
                    "非常に怒っているか、強い言葉を使っている。",
                ],
            ),
        },
    )

print(response.answers["refund_requested"].noul)
print(response.answers["request_type"].choice)
print(response.answers["frustration"].score)
```

インストールと各言語での使用方法については[クライアントSDK](/sdk)を参照してください。

### 投機的な質問をする {#ask-speculative-questions}

一部の入力にのみ答えが必要な質問も含め、コードが必要とする可能性のあるすべての質問を質問し、どの回答を使用するかはコードに決めさせます。チケットがバグレポートでないとわかった場合は、深刻度の回答を無視します。これを[投機的なファンアウト](/patterns/fan-out)パターンと呼びます。[並列質問クックブック](/cookbooks/parallel_questions)では、13の質問を1つのコールにまとめることで、13回の個別コールと比べて11.5倍安価で9.6倍高速になり、回答に変化がないことを示しています。

<Tip>
  コーディングエージェントは人よりも1コール1質問の習慣に陥りがちです。[TypeSafeエージェントスキル](/agent-skill#installation)は、一部の入力にのみ必要な質問も含め、各コールに多くの質問を含めるようエージェントに指示します。
</Tip>

### 複雑な判断を複数の質問に分割する {#split-a-complex-judgment-into-several-questions}

複数の要素に依存する判断は、要素ごとに1つの質問に分割するのが最善です。コードで回答を組み合わせ、相対的な重要度に応じて各回答に重みを付けます。重みは自分で決めます。組み合わせた結果がチームの判断と一致しない場合は、コードで変更して再実行してください。質問は1つのリクエスト内で並列実行されるため、質問を追加してもレスポンス時間はほとんど変わりません。分割にかかる追加コストは少量の質問トークンだけです。

たとえば、チケットの優先度は3つのScore質問から構築できます：バグの深刻度、顧客の不満度、エンジニアが作業するための情報量。Scoreページでは、このリクエストとスコアを正規化・重み付けするコードを[複雑な判断を複数のScoreに分割する](/primitives/score#splitting-a-complex-judgment-into-several-scores)で詳しく説明しています。このテクニックは[複合スコアリング](/patterns/composite-scoring)パターンと呼ばれます。

### 1つの質問が別の質問に依存する場合 {#when-one-question-depends-on-another}

同じリクエスト内の質問は独立しています：1つの回答が別の質問のコンテキストになることはありません。後の判断が前の回答に依存する場合は、コードで2回目のリクエストを行います。依存関係が本物であるのは、コードが最初の回答を得るまで2回目のリクエストを構築できない場合のみです：回答が状態のためにより多くのデータを取得するために必要、状態の構成を決定するために必要、または次の質問のオプションを選ぶために必要な場合です。そうでなければ、質問をまとめて質問し、コードで不要な回答を無視してください。

2つのリクエストは例外であり、原則ではありません。2回目のリクエストの質問が元の状態に対して質問できた場合は、最初のリクエストで質問し、必要でない質問の回答はコードで無視させます。3つのクックブックが本当の理由で2回目のリクエストを行っています。[スキル提案](/cookbooks/skill_suggestion)は1回のリクエストで182のスキルをランク付けし、上位3つの全文を取得してより良い証拠に基づいて再度判断します。[構造の復元](/cookbooks/autoformat)は各改行が文を分割しているかどうかを確認し、その回答から行をブロックにまとめ、最初のリクエストが回答するまで存在しなかったブロックを分類します。[階層的分類](/cookbooks/hierarchical_classification)は各Choiceの回答を使って、次のリクエストが提供するオプションを決定します。

ワークフローを焦点を絞った判断に分割するためのガイダンスは[TypeSafeでの構築方法](/concepts/how-to-build-with-system-one)を参照してください。

## 次のステップ {#next-steps}

<Columns cols={3}>
  <Card title="Choice" href="/primitives/choice" icon="list">
    固定リストから1つのオプションを選択する。
  </Card>

  <Card title="Score" href="/primitives/score" icon="gauge">
    順序付きレベルに沿って状態を評価する。
  </Card>

  <Card title="Noul" href="/primitives/noul" icon="circle-check">
    文が真である確率を取得する。
  </Card>
</Columns>

これらをシステムアーキテクチャに組み合わせる方法については、[パターン](/patterns)を参照してください。