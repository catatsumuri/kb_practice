# プリミティブ（質問） {#primitives-questions}

> 3つのTypeSafeの質問タイプ（Choice、Score、Noul）、それぞれが返す型付きの回答、使い分け方、そして複数まとめて質問する方法。

TypeSafeのプリミティブは、コードで組み合わせる小さな型付きビルディングブロックです。ペアで提供されます。質問は[System Oneモデル](/concepts/system-one)が[状態](/concepts/state)について行う1つの判断を定義し、その回答は返ってくる型付きの値です。コードの中で回答を組み合わせて意思決定を行います。質問タイプは3種類あり、それぞれ異なる形の回答を返します。

| タイプ | 何に答えるか | 返す値 |
| - | - | - |
| [Choice](/primitives/choice) | これらのオプションのどれか？ | `choice`、`probabilities`、`confidence` |
| [Score](/primitives/score) | どのレベルか？ | `score`、`legend`、`probabilities`、`confidence` |
| [Noul](/primitives/noul) | これは真か？ | `noul`（0から1） |

1つの質問を送ることも、複数まとめて送ることもできます。リクエスト内のすべての質問は同じ状態を参照し、独立して評価され、選んだIDの下に型付きの回答が返ります。

## 質問ごとに1つの直感的判断を求める {#ask-for-one-snap-judgment-per-question}

System Oneモデルは高速で集中した判断のために作られています。適切なコンテキストが与えられれば知識のある人が1秒で下せるような判断を求めてください。「このメッセージは緊急性を伝えているか？」は良い質問です。「このメッセージを分析し、最善の行動方針を決めてください」はそうではありません。それには遅い推論が必要であり、タスクを小さな質問に分割してコードで回答を組み合わせるべきシグナルです。

求める判断が複数の独立した要素に依存する場合は、各要素について個別に質問し、独自のロジックで回答を組み合わせてください。「このスタートアップのピッチを評価してください」の代わりに、市場規模、技術的実現可能性、差別化について質問し、相対的な重要度に基づいてコードで重み付けします。優先度が変わったときは、プロンプトを書き直すのではなく重みの値を変更してください。[複数の質問をまとめて送る](#ask-multiple-questions-together) でその方法を説明します。

## 質問を定義する {#define-a-question}

すべての質問にはID、`type`、`instructions` があります。ChoiceとScoreの質問には`criteria` も指定します。`criteria` はChoice質問のオプション、Scoreのレベルを定義します。Noul質問では`criteria` はyesとnoの意味を明確にするオプションの補足として受け付けます。

* ID。`refund_requested` のように自分で決めるキーです。レスポンス内で回答を識別します。
* `type`。`choice`、`score`、`noul` のいずれかです。
* `instructions`。状態について尋ねている質問です。評価ロジックをここに記述します。明確で具体的な質問、またはモデルが判断する文として書いてください。ほとんどの質問では文字列で十分です。オブジェクトや配列にすることもでき、その場合は質問を1つのフィールドに、参照するデータを別のフィールドに置きます。詳細は[質問の中で構造を使う](/concepts/how-to-build-with-system-one#use-structure-in-the-questions) を参照してください。
* `criteria`。可能な回答です。Choice質問ではオプションのマップ、Scoreでは順序付きのレベルリスト、Noulではyesとnoのオプションの説明です。各質問タイプのページでその形状を説明します。

この質問は顧客が返金を要求したかどうかを尋ねます。

```python theme={null}
from typesafe_sdk import Noul

questions = {
    "refund_requested": Noul(
        instructions="Does the customer request a refund?",
    ),
}
```

<Tip>
  質問IDはコードのためのものです。モデルには送信されません。IDが自明に見える場合でも、完全な質問を`instructions` に記述してください。
</Tip>

## 質問タイプを選ぶ {#choose-a-question-type}

必要な回答の形に合ったタイプを選んでください。

* **Choice** は、回答が順序のない既知のオプションセットの1つである場合に適しています。チケットを部門にルーティングする、ドキュメントタイプを分類する、プログラミング言語を検出する、といった用途です。オプションの完全なリストを提供し、リストがすべての入力をカバーしない可能性がある場合は`other` や`none of the above` オプションを追加してください。

* **Score** は、回答がスペクトラム上にあり、そのスペクトラムの各点の意味を説明できる場合に適しています。バグの深刻度、顧客の不満、スキルレベルなどです。レベルは自分で定義し、モデルはその上の位置を返します。

* **Noul** は、確率自体が有用なシグナルとなるクリーンなyes/no質問に適しています。このメッセージに個人を特定できる情報が含まれているか、顧客が返金を求めているか、履歴書に分散システムへの言及があるか、といった用途です。

<Note>
  yes/noの判断にはNoulを使い、スペクトラム上の位置を測定するにはScoreを使ってください。「この候補者はPythonが得意か？」には「得意」の明確な定義が必要です。Noulの値0.5は、モデルがyesとnoに等しい確率を与えることを意味します。候補者のスキルレベルが中程度であることを意味するわけではありません。定義が不明確だと、その確率の解釈が難しくなります。

  スキルレベルを測定したい場合は、経験なし、ある程度の知識あり、日常的に使用、深い専門知識のような定義されたレベルを持つScoreを使ってください。yes/noの判断が必要な場合は、「候補者が職場でPythonを使用したと履歴書に記載されているか？」のように条件を明確に定義してください。
</Note>

2つのタイプがどちらも当てはまる場合は、コードが直接使える回答を返す方を選んでください。`refund`、`rebook`、`information` の間のChoiceは3つのコードパスに直接対応します。顧客の不満のScoreはしきい値に対応します。Noulは`if` に対応します。

## 返ってくるもの {#what-comes-back}

回答もプリミティブです。各質問タイプは、コードで比較、しきい値処理、ソート、さらなるロジックへの受け渡し、またはフォローアップリクエストの状態への組み込みができる型付きの値を返します（[ある質問が別の質問に依存する場合](#when-one-question-depends-on-another) を参照）。

| タイプ | 回答フィールド | 読み方 |
| - | - | - |
| Choice | `choice`、`probabilities`、`confidence` | `choice` は選択されたオプションです。`probabilities` はすべてのオプションにわたる分布です。`confidence` はその分布の集中度を要約します。 |
| Score | `score`、`legend`、`probabilities`、`confidence` | `score` はレベルに沿った位置であり、2つのレベルの間に落ちることもあります。`legend` はレベルを番号で繰り返します。`probabilities` はレベルにわたる分布です。 |
| Noul | `noul` | 回答がyesである確率です。1に近いほど強いyes、0に近いほど強いno、0.5に近いほど不確かです。Noulには別途`confidence` はありません。 |

これらの回答を組み合わせ可能にする2つの特性があります。

* **すべての回答は指定したオプションに制約されます。** モデルはオプションまたはレベルにわたる確率分布を返し、それらの外側の値は返しません。コードが生成されたテキストから値を復元する必要は一切ありません。
* **すべての回答は独立しています。** ある質問の回答が別の質問の隠れたコンテキストになることはありません。他の質問の結果を変えることなく質問を追加・削除できます。

[確信度](/confidence) では、`confidence` が`probabilities` からどのように導出されるか、そして自動的に行動するかどうかを判断するためにどう使うかを説明します。

## 特定のフィールドを参照する {#reference-specific-fields}

評価対象のコンテンツである[状態](/concepts/state)は、会話、レコード、ポリシーなど複数の部分を持つJSONオブジェクトであることがよくあります。質問がそのうちの1つの部分に関するものであれば、バッククォートを含めてキーへのドットとインデックスのパスで`instructions` 内にその部分を指定してください。そうすることでモデルは状態のどの部分を判断すべきかを把握します。

Stateページのサポート会話を例として使います。

```json theme={null}
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

この2つの質問は、パスによって顧客のメッセージ、ポリシー、請求を指しています。

```python theme={null}
questions = {
    "refund_requested": {
        "type": "noul",
        "instructions": "Does `ticket.messages[0].text` request a refund?",
    },
    "policy_supports_refund": {
        "type": "noul",
        "instructions": (
            "Does `refund_policy` support the refund requested "
            "in `ticket.messages[0].text`, given `order.charges`?"
        ),
    },
}
```

明示的なパスにより、構造化された状態のどの部分が各判断に使われるべきかが明確になります。入力の構造化については[状態](/concepts/state) を参照してください。

## 複数の質問をまとめて送る {#ask-multiple-questions-together}

同じ状態を使うすべての質問を1つのリクエストで送ってください。質問タイプは自由に組み合わせられます。System Oneモデルはリクエスト内のすべての質問を並列で評価します。質問を追加してもレスポンス時間はほとんど変わらず、コストは追加の質問のトークン分だけで、これは安価です。必要かどうかわからない質問を送ることもほぼ無料です。

このリクエストは顧客のメッセージを分類し、緊急性を確認し、不満度をスコアリングするのをすべて一度に行います。

```json title="request" theme={null}
{
  "state": "Our API integration started returning 500 errors on every request about 20 minutes ago, and we can't process any customer orders until this is fixed.",
  "questions": {
    "department": {
      "type": "choice",
      "instructions": "Which team should handle this",
      "criteria": {
        "billing": "Payment or subscription issues",
        "technical": "Bugs or integration problems",
        "sales": "Pricing or account questions"
      }
    },
    "is_urgent": {
      "type": "noul",
      "instructions": "The message conveys urgency or time-sensitivity"
    },
    "frustration": {
      "type": "score",
      "instructions": "How frustrated the customer appears",
      "criteria": [
        "Calm, just stating facts",
        "Frustrated but civil",
        "Very angry, strong language"
      ]
    }
  }
}
```

[クライアントSDK](/sdk) は型付きの質問と回答を提供します。Pythonでは、`Choice`、`Noul`、`Score` オブジェクトの`questions` ディクショナリを`client.system_one(...)` に渡します。このリクエストはチケットと返金ポリシーを一度送り、各質問に対して型付きの回答を受け取ります。

```python theme={null}
from typesafe_sdk import Choice, Noul, Score, TypeSafeClient

state = {
    "ticket_message": "My flight was cancelled. Can I get a refund?",
    "refund_policy": "Cancelled flights are eligible for a full refund.",
}

with TypeSafeClient() as client:
    response = client.system_one(
        state=state,
        questions={
            "refund_requested": Noul(
                instructions="Does `ticket_message` request a refund?",
            ),
            "request_type": Choice(
                instructions="What is the main request in `ticket_message`?",
                criteria={
                    "refund": "The customer wants money returned.",
                    "rebooking": "The customer wants a replacement flight.",
                    "information": "The customer is asking for information only.",
                },
            ),
            "frustration": Score(
                instructions="How frustrated does the customer appear in `ticket_message`?",
                criteria=[
                    "Calm and neutral.",
                    "Concerned but civil.",
                    "Very angry or using strong language.",
                ],
            ),
        },
    )

print(response.answers["refund_requested"].noul)
print(response.answers["request_type"].choice)
print(response.answers["frustration"].score)
```

インストールと各言語での使用方法については[クライアントSDK](/sdk) を参照してください。

### 投機的な質問を送る {#ask-speculative-questions}

コードが必要とするかもしれないすべての質問（一部の入力でのみ回答が重要になるものを含む）を送り、どの回答を使うかをコードに決めさせてください。チケットがバグレポートでないとわかったら、深刻度の回答は無視します。これを[投機的ファンアウト](/patterns/fan-out)パターンと呼びます。[並列質問クックブック](/cookbooks/parallel_questions) では、13の質問を1回の呼び出しにまとめることで13回の個別呼び出しと比べて11.5倍安く9.6倍速くなり、回答に変化がないことを示しています。

<Tip>
  コーディングエージェントは人間よりも1回の呼び出しに1つの質問を送る習慣に陥りがちです。[TypeSafeエージェントスキル](/agent-skill#installation) はエージェントに対して、一部の入力でのみ重要になるものも含め、各呼び出しに多くの質問を入れるよう指示します。
</Tip>

### 複雑な判断を複数の質問に分割する {#split-a-complex-judgment-into-several-questions}

複数のことに依存する判断は、1つのことにつき1つの質問に分割するのが最善です。コードで回答を組み合わせ、相対的な重要度に応じて各回答に重みを付けます。重みは自分で決めます。組み合わせた結果がチームの判断と一致しない場合は、コードで重みを変更して再度実行してください。質問は1つのリクエスト内で並列実行されるため、追加してもレスポンス時間はほとんど変わりません。分割にかかるコストは数個の追加質問トークンだけです。

例えば、チケットの優先度は3つのScore質問から構築できます。バグの深刻度、顧客の不満度、レポートがエンジニアにどれだけ手がかりを与えるかです。Scoreページの[複雑な判断を複数のScoreに分割する](/primitives/score#splitting-a-complex-judgment-into-several-scores) では、このリクエストと回答を正規化して重み付けするコードを説明しています。この技術は[複合スコアリング](/patterns/composite-scoring)パターンと呼ばれます。

### ある質問が別の質問に依存する場合 {#when-one-question-depends-on-another}

同じリクエスト内の質問は独立しています。ある回答が別の質問のコンテキストになることはありません。後の判断が前の回答に依存する場合は、コードで2回目のリクエストを作成してください。依存関係が本物なのは、最初の回答を得るまで2回目のリクエストを構築できない場合のみです。つまり、追加データを取得するために状態に回答が必要な場合、状態の構成を決めるために回答が必要な場合、または次の質問のオプションを選ぶために回答が必要な場合です。それ以外の場合は、質問をまとめて送り、コードで不要な回答を無視してください。

2回のリクエストは例外であり、原則ではありません。2回目のリクエストの質問が元の状態に対して送れたであれば、1回目のリクエストで送り、不要な回答はコードで無視させてください。3つのクックブックが本当の理由で2回目のリクエストを行っています。[スキル提案](/cookbooks/skill_suggestion) は1回のリクエストで182のスキルをランク付けし、上位3つのフルテキストを取得してより良いエビデンスに照らして再度判断します。[構造復元](/cookbooks/autoformat) は各改行が文を分割したかどうかを尋ね、その回答から行をブロックにまとめ、ブロックを分類します（ブロックは最初のリクエストが回答するまで存在しません）。[階層的分類](/cookbooks/hierarchical_classification) は各Choiceの回答を使って、次のリクエストが提示するオプションを決定します。

判断に焦点を当てたワークフローの分割については[TypeSafeでの構築方法](/concepts/how-to-build-with-system-one) を参照してください。

## 次のステップ {#next-steps}

<Columns cols={3}>
  <Card title="Choice" href="/primitives/choice" icon="list">
    固定リストから1つのオプションを選ぶ。
  </Card>

  <Card title="Score" href="/primitives/score" icon="gauge">
    順序付きレベルに沿って状態を評価する。
  </Card>

  <Card title="Noul" href="/primitives/noul" icon="circle-check">
    文が真である確率を取得する。
  </Card>
</Columns>

これらをシステムアーキテクチャにどう組み合わせるかを見るには、[パターン](/patterns) をご覧ください。