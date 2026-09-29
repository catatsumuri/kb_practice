# TypeSafeを使った開発方法 {#how-to-build-with-typesafe}

> コードを主導権を持った状態に保ち、System Oneに狭く構造化された判断を委ねることで、AI搭載ソフトウェアを設計する。

System OneはTypeSafeのモデルであり、エージェントではなくAI搭載ソフトウェアの構築を目的としています。コードを生成したり、次のアクションを自律的に選択したりすることはありません。ソフトウェアに組み込むAIプリミティブを提供することで、コードが主導権を持ちながら、モデルが非構造化データに対する常識的な判断を担います。

<Info>
  **概要:** 通常のソフトウェアワークフローを構築し、AIが必要な箇所にのみSystem Oneを挿入する。

  * 制御フロー、決定論的なルール、副作用はコードで管理する。
  * 広範な判断を、明示的な指示と基準を持つ狭く型付けされた質問に分解する。
  * 各質問には必要なコンテキストのみを与える。
  * 確率と確信度を使って、アクションの実行・レビュー依頼・エスカレーションを判断する。
  * 独立した質問をまとめて送り、その回答をコードで組み合わせる。
</Info>

## 3つのソフトウェアアーキテクチャ {#three-software-architectures}

TypeSafeは**AI搭載ソフトウェア**の構築向けに設計されており、コードがワークフローを管理し、AIが狭く構造化された判断を処理します。

<Tabs>
  <Tab title="従来のソフトウェア">
    従来のコードは、シンプルなソフトウェアプリミティブで構成された複雑な決定木です。各プリミティブが信頼できるため、開発者はそれらを組み合わせてより高レベルな抽象化を構築できます。
  </Tab>

  <Tab title="LLMエージェント">
    エージェントは指示を処理し、次のステップを自律的に選択します。人間が監視している場合は有効ですが、ループが増えるたびに制御を失うリスクが高まります。
  </Tab>

  <Tab title="AI搭載ソフトウェア">
    コードが決定論的な処理を担い、制御フローを管理します。モデルは、システムがプログラム可能な常識を必要とする箇所や非構造化データを解釈する必要がある箇所にのみ登場します。各AIタスクはアトミックかつ制約された状態に保たれます。
  </Tab>
</Tabs>

![従来のソフトウェア、エージェント、AI搭載ソフトウェアを3つの異なるシステムアーキテクチャとして示した図。](https://mintcdn.com/ts-docs/aFVnpmCIX68NpsV1/images/how-to-build-with-typesafe/software-architectures-light.webp?fit=max&auto=format&n=aFVnpmCIX68NpsV1&q=85&s=35c7622176190d1b1f19dc712f2fbf11#only-light)

![従来のソフトウェア、エージェント、AI搭載ソフトウェアを3つの異なるシステムアーキテクチャとして示した図。](https://mintcdn.com/ts-docs/aFVnpmCIX68NpsV1/images/how-to-build-with-typesafe/software-architectures-dark.webp?fit=max&auto=format&n=aFVnpmCIX68NpsV1&q=85&s=8e6c2c73bdd4c9b541c4f9294bd829b5#only-dark)

## System Oneが組み合わせ可能な理由 {#what-makes-system-one-composable}

<Columns cols={2}>
  <Card title="構造化" icon="braces">
    System Oneは構造上タイプセーフです。判断と確率は、コードが期待する構造化ソフトウェア型とJSONスキーマに準拠するため、生成されたテキストから値を復元する必要がありません。
  </Card>

  <Card title="並列処理" icon="split">
    質問は独立して並列に評価されます。あるプリミティブの結果が隠れたコンテキストとなって別のプリミティブの結果に影響することはありません。
  </Card>

  <Card title="比較可能" icon="arrow-up-down">
    出力はソート可能で、スマートな`if`文、閾値、比較処理を駆動できます。
  </Card>

  <Card title="高速" icon="gauge">
    ほとんどのクエリは約100ミリ秒で完了します。System Oneはリアルタイムのリクエストパスやユーザーインターフェースに対応できる速度を備えています。
  </Card>

  <Card title="キャリブレーション済み確信度" icon="chart-no-axes-combined">
    [RLCD](/introduction/machine-learning-primer)は、過信に傾く代わりに、キャリブレーション済み確率を通じて不確実性を伝えます。
  </Card>

  <Card title="自己整合性" icon="repeat-2">
    System Oneは繰り返し評価を行っても安定した回答を返すよう設計されています。[自己整合性クックブック](/cookbooks/consistency_noul_cookbook)を参照してください。
  </Card>
</Columns>

すべての出力は提供されたオプションに制約されるため、モデルはスキーマ外の値を作り出すのではなく、それらのオプションに対する完全な確率分布を返します。TypeSafeの目標は、知性対速度・コスト比で100倍以上を達成することです。より安価な知性がより多くの需要を生み出すという考え方に基づいています。

## System Oneワークフローの設計 {#design-a-system-one-workflow}

<Steps titleSize="h3">
  <Step title="できる限りコードを使う">
    決定論的な処理はコードで行いましょう。信頼性が高く、コストも低いです。ソフトウェアワークフローで同じ動作を表現できる場合は、エージェントの`while`ループを避けてください。

    <Accordion title="例: 決定論的なルールをコードで管理する">
      ```python theme={null}
      days_overdue = (today - invoice.due_date).days

      if days_overdue > 30:
          route_to_collections(invoice)
      ```
    </Accordion>

    モデルの判断をコードと組み合わせる限定的な方法については、[System Oneパターン](/patterns)を参照してください。
  </Step>

  <Step title="入力状態を分解する">
    現在の質問に関連するコンテキストのみを含めましょう。これにより、モデルが余計な情報に惑わされたり、コンテキストロットが発生したりするのを防ぎます。現在の情報を自分のナレッジベースから取得できる場合は、モデルの重みに保存された知識に依存しないでください。

    <Accordion title="例: 関連するコンテキストのみを送信する">
      ```json title="リクエスト" theme={null}
      {
        "state": {
          "ticket_message": "フライトがキャンセルされました。返金してもらえますか？",
          "refund_policy": "キャンセルされたフライトは全額返金の対象となります。"
        },
        "questions": {
          "policy_supports_refund": {
            "type": "noul",
            "instructions": "返金ポリシーはチケットに記載された返金リクエストを支持していますか？"
          }
        }
      }
      ```
    </Accordion>
  </Step>

  <Step title="入力状態に構造を使う">
    `state`フィールドと`questions`フィールドにはネストされたJSONを使いましょう。曖昧さをなくせる場合は質問を特定の値に向け、質問の中の各パスをバッククォートで囲んでください。

    <Accordion title="例: ネストされた値を参照する">
      バッククォートで囲んだドットとインデックスのパスを使って、`support.tickets[0].message`のように特定のネストされた値を質問から指定しましょう。

      ```json title="リクエスト" theme={null}
      {
        "state": {
          "support": {
            "tickets": [
              {
                "message": "注文A-104で2回請求されました。"
              },
              {
                "message": "パスワードをリセットするにはどうすればいいですか？"
              }
            ]
          },
          "commerce": {
            "orders": [
              {
                "id": "A-104",
                "charges": [
                  {
                    "amount_usd": 49,
                    "status": "captured"
                  },
                  {
                    "amount_usd": 49,
                    "status": "captured"
                  }
                ]
              }
            ]
          },
          "account": {
            "security": {
              "password_reset": "登録済みのメールアドレスにリセットリンクを送信する。"
            }
          }
        },
        "questions": {
          "duplicate_charge": {
            "type": "noul",
            "instructions": "`support.tickets[0].message`と`commerce.orders[0].charges`は二重請求を示していますか？"
          },
          "password_reset_supported": {
            "type": "noul",
            "instructions": "`account.security.password_reset`は`support.tickets[1].message`のリクエストを解決できますか？"
          }
        }
      }
      ```
    </Accordion>
  </Step>

  <Step title="質問を分解する">
    できる限り明確で、狭く、具体的で、アトミックな質問をしましょう。複雑または曖昧な質問は、それぞれ1つのプロパティを評価する別々の質問に分解してください。

    <Info>
      これはおそらくこのガイドの中で最も重要な概念です。広範な質問は複数の判断を1つの回答の裏に隠してしまいます。アトミックな質問はそれらの判断を露出させ、コードで検査・調整・組み合わせを行えるようにします。
    </Info>

    <Accordion title="例: スパム検出を分解する">
      ```json title="1つの広範な質問（悪い例）" theme={null}
      {
        "is_spam": {
          "type": "noul",
          "instructions": "`message`はスパムですか？"
        }
      }
      ```

      ```json title="分解された質問（良い例）" theme={null}
      {
        "requests_credentials": {
          "type": "noul",
          "instructions": "`message.body`は受信者にパスワードやその他のログイン認証情報を提供するよう求めていますか？"
        },
        "offers_unexpected_reward": {
          "type": "noul",
          "instructions": "`message.body`は受信者が予期しない賞品、支払い、または報酬を受け取ったと主張していますか？"
        },
        "creates_time_pressure": {
          "type": "noul",
          "instructions": "`message.subject`または`message.body`は受信者に早急な行動を促していますか？"
        },
        "sender_identity_mismatch": {
          "type": "noul",
          "instructions": "`message.sender.display_name`に記載された組織名は`message.sender.email`のドメインと矛盾していますか？"
        },
        "link_domain_mismatch": {
          "type": "noul",
          "instructions": "`message.links[0].url`のドメインは`message.sender.display_name`に記載された組織名と矛盾していますか？"
        },
        "disguises_link_destination": {
          "type": "noul",
          "instructions": "`message.links[0].text`は`message.links[0].url`のリンク先を隠したり偽って表示していますか？"
        }
      }
      ```
    </Accordion>

    <Accordion title="例: ツールコールのトレースを検証する">
      ```json title="1つの広範な質問（悪い例）" theme={null}
      {
        "tool_calls_are_correct": {
          "type": "noul",
          "instructions": "`trace.tool_calls`は`request`と`available_tools`に対して正しいですか？"
        }
      }
      ```

      ```json title="分解された質問（良い例）" theme={null}
      {
        "geocode_tool_is_relevant": {
          "type": "noul",
          "instructions": "`trace.tool_calls[0].name`は`request.location`を解決するのに適切なツールですか？"
        },
        "geocode_location_matches": {
          "type": "noul",
          "instructions": "`trace.tool_calls[0].arguments.city`は`request.location`と一致していますか？"
        },
        "geocode_arguments_match_schema": {
          "type": "noul",
          "instructions": "`trace.tool_calls[0].arguments`は`available_tools.geocode_city.parameters`に準拠していますか？"
        },
        "geocode_result_matches_call": {
          "type": "noul",
          "instructions": "`trace.tool_results[0].tool_call_id`は`trace.tool_calls[0].id`と一致していますか？"
        },
        "weather_tool_is_relevant": {
          "type": "noul",
          "instructions": "`trace.tool_calls[1].name`は`request.text`に回答するのに適切なツールですか？"
        },
        "weather_arguments_match_schema": {
          "type": "noul",
          "instructions": "`trace.tool_calls[1].arguments`は`available_tools.get_weather.parameters`に準拠していますか？"
        },
        "weather_uses_geocoded_coordinates": {
          "type": "noul",
          "instructions": "`trace.tool_calls[1].arguments`の座標は`trace.tool_results[0].output`の座標と一致していますか？"
        },
        "weather_date_matches": {
          "type": "noul",
          "instructions": "`trace.tool_calls[1].arguments.date`は`request.date`と一致していますか？"
        },
        "weather_unit_matches": {
          "type": "noul",
          "instructions": "`trace.tool_calls[1].arguments.unit`は`request.unit`と一致していますか？"
        }
      }
      ```
    </Accordion>
  </Step>

  <Step title="質問に構造を使う">
    質問は短く保ちましょう。`instructions`と`criteria`は通常は文字列であり、短くて曖昧さのない質問であれば文字列だけで十分です。オブジェクトや配列にすることもできます。質問を1つのフィールドに入れ、質問を導くデータを他のフィールドに入れましょう。

    構造が役立つ場面：

    * 質問にコンテキストや例が必要な場合。長い背景情報や入力例のリストは、質問を書き直さずにコードで追加・置換できるよう、質問の横に名前付きフィールドとして配置するのが適切です。
    * 質問の一部がコードに由来する場合。値がデータベースから来る場合は、文字列テンプレートに直接埋め込むのではなく、専用のフィールドに入れましょう。
    * 複数の質問が似た指示を持つ場合。1つのリクエストは1つの状態を受け取り、複数の質問を含めることができます。補足データを追加することで質問を明確に区別できます。

    <Accordion title="例: コードからレコードを参照する">
      このNoulは、状態に含まれる履歴書を候補者データベースのレコードと照合します。レコードはそのまま`potential_duplicate`に入れられ、質問はその名前で参照します。

      ```json title="questions" theme={null}
      {
        "same_as_record_18": {
          "type": "noul",
          "instructions": {
            "potential_duplicate": {
              "name": "山田太郎",
              "location": "東京都渋谷区",
              "last_employer": "Google"
            },
            "question": "この履歴書は`potential_duplicate`と同一人物のものですか？"
          }
        }
      }
      ```
    </Accordion>

    コードから取得される「potential\_duplicate」のデータは時間とともに変化する可能性があります。「question」はバッククォートを使ってそれを参照しています。

    `criteria`内の説明もオブジェクトにできます。Choiceの場合、各オプションの説明をオブジェクトにして、そのオプションがカバーする内容、別のオプションに属する内容、いくつかの例を記述できます。モデルが直接比較できるよう、各オプションで同じフィールド名を使いましょう。

    <Accordion title="例: 対比的なChoiceのcritiaを定義する">
      ```json title="questions" theme={null}
      {
        "card_help_topic": {
          "type": "choice",
          "instructions": {
            "question": "ユーザーはどの使い捨てバーチャルカードのトピックについて質問していますか？",
            "focus": "ユーザーが求めている情報を分類する。"
          },
          "criteria": {
            "get_disposable_virtual_card": {
              "what": "目的、適格性、または設定方法",
              "not_for": "枚数、取引、または加盟店の制限",
              "examples": [
                "使い捨てバーチャルカードはどうすれば手に入りますか？",
                "使い捨てカードは何に使うものですか？"
              ]
            },
            "disposable_card_limits": {
              "what": "枚数、取引、または加盟店の制限",
              "not_for": "目的、適格性、または設定方法",
              "examples": [
                "1日に使い捨てカードを何枚まで作れますか？",
                "使い捨てカードはどこで使えますか？"
              ]
            }
          }
        }
      }
      ```
    </Accordion>

    各質問タイプのページには実例があります：

    * [Noul](/primitives/noul#structured-instructions)は1つの履歴書を複数の候補者レコードと比較します。レコードごとに1つの質問を作り、質問はコードで構築されます。
    * [Choice](/primitives/choice#structured-instructions-and-criteria)は混同しやすい2つのオプションを、それぞれがカバーする内容、対象外の内容、例を用いて説明します。
    * [Score](/primitives/score#structured-level-descriptions)は各レベルに説明と状況例を付与します。

    [構造化データ抽出カスケードクックブック](/cookbooks/sde_cascade)では、抽出されたレコードのすべてのフィールドに同じ一連の質問を問いかける、共通表現のユースケースを紹介しています。

    短く曖昧さのない質問や基準は文字列のままにしておいて構いません。そうしなければ混在してしまうガイダンスを分離する場合に構造を追加しましょう。構造が受け付けられる箇所の完全なリストについては、[上級編: 構造](/primitives/advanced)を参照してください。
  </Step>

  <Step title="多くの質問をする">
    同じ状態について、狭く独立した多くの質問を1つのリクエストで送りましょう。これがAPIで効果とドル当たりの知性を最大化する方法です。質問は並列に実行され、コードはシリアルなモデルのラウンドトリップを増やすことなくそれらのシグナルを組み合わせられます。

    [投機的ファンアウトパターン](/patterns/fan-out)と[並列質問クックブック](/cookbooks/parallel_questions)を参照してください。
  </Step>

  <Step title="質問の出力をコードで組み合わせる（または古典的な機械学習モデルに入力する）">
    独立した回答を決定論的なルールや重み付き和で組み合わせましょう。学習による組み合わせには、確率をダウンストリームの古典的な機械学習モデルの特徴量として使用します。

    <Accordion title="例: 重み付きスコアでシグナルを組み合わせる">
      ```python theme={null}
      answers = response.answers

      # 独立したシグナルをアプリケーション固有の1つのスコアに組み合わせる。
      quality = (
          0.4 * answers["answers_request"].noul
          + 0.4 * answers["citations_are_supported"].noul
          + 0.2 * (1 - answers["contradicts_context"].noul)
      )
      ```
    </Accordion>

    [複合スコアリング](/patterns/composite-scoring)では、個別の判断を保持しながら組み合わせる方法を示しています。ダウンストリームモデルのラベルがない場合は、高価な推論モデルのアンサンブルを使って生成しましょう。[AutoResearchクックブック](/cookbooks/autoresearch_feature_discovery)では、System Oneの出力を使って古典的なモデルを訓練する方法を紹介しています。
  </Step>

  <Step title="不確実性に基づいてルーティングする">
    確信度が高い回答と低い回答でコードが異なるアクションを取るようにしましょう。不確実なケースは人間またはより高価な推論モデルにエスカレートします。自分のデータで確信度と精度をプロットして閾値をテストしましょう。

    <Accordion title="例: 確信度でルーティングする">
      ```python theme={null}
      answer = response.answers["card_help_topic"]

      if answer.confidence < 0.8:
          route_to_human_review(ticket)
      else:
          route_to_handler(answer.choice, ticket)
      ```
    </Accordion>

    閾値の選択と各アクションのリスクへの対応については、[確信度](/confidence)と[確信度ゲートルーティング](/patterns/confidence-routing)を参照してください。
  </Step>
</Steps>

<Tip>
  分解によってラウンドトリップが増えることはありません。同じ状態に対する質問は並列に実行されます。
</Tip>

## すべてをまとめる {#putting-it-all-together}

このサポートチケットワークフローは、決定論的な処理をコードで管理し、関連する構造化コンテキストのみを送信し、1つのリクエストで多くのアトミックな質問を評価し、明示的な確信度ゲートで回答を組み合わせています。

```python title="triage_ticket.py" theme={null}
from typesafe_sdk import Choice, Noul, NoulCriteria, Score, TypeSafeClient


def triage_ticket(ticket, customer):
    # モデルを呼び出さずに決定論的な状態を処理する。
    if ticket["status"] == "closed":
        return "no_action"

    open_orders = [
        order for order in customer["orders"] if order["status"] != "delivered"
    ]

    # 以下の質問に必要な構造化コンテキストのみを含める。
    state = {
        "ticket": {
            "message": ticket["message"],
            "sender": ticket["sender"],
            "links": ticket["links"],
        },
        "customer": {
            "plan": customer["plan"],
            "open_orders": open_orders,
        },
        "policy": {
            "sensitive_credentials": ["パスワード", "セキュリティコード", "APIキー"],
        },
    }

    # 並列実行されるよう、構造化されたアトミックな質問をまとめて送信する。
    questions = {
        "topic": Choice(
            instructions={
                "question": "`ticket.message`はどのチームが対応すべきですか？",
                "focus": "顧客の主なリクエストを分類する。",
            },
            criteria={
                "billing": {
                    "what": "請求、請求書、返金、またはサブスクリプション",
                    "not_for": "注文追跡またはアカウントアクセス",
                    "examples": ["二重に請求されました", "返金はどこですか？"],
                },
                "orders": {
                    "what": "注文状況、配送、キャンセル、または返品",
                    "not_for": "請求またはアカウントアクセス",
                    "examples": ["注文はどこですか？", "出荷をキャンセルしてください"],
                },
                "account": {
                    "what": "ログイン、プロフィール、権限、またはセキュリティ",
                    "not_for": "請求または注文追跡",
                    "examples": ["パスワードをリセットしてください", "サインインできません"],
                },
            },
        ),
        "requests_credentials": Noul(
            instructions={
                "question": "メッセージは機密の認証情報を要求していますか？",
                "compare": [
                    "`ticket.message`",
                    "`policy.sensitive_credentials`",
                ],
                "focus": "認証情報自体の開示を求めているかどうかを確認する。",
            },
            criteria=NoulCriteria(
                true={
                    "what": "列挙された認証情報の開示を受信者に求めている",
                    "examples": [
                        "パスワードを返信してください",
                        "APIキーを送ってください",
                    ],
                },
                false={
                    "what": "認証情報の開示を受信者に求めていない",
                    "not_for": "認証情報のリセットを促す正当な指示",
                    "examples": ["このリンクからパスワードをリセットしてください"],
                },
            ),
        ),
        "sender_identity_mismatch": Noul(
            instructions={
                "question": "送信者の主張するアイデンティティはそのドメインと矛盾していますか？",
                "compare": [
                    "`ticket.sender.display_name`",
                    "`ticket.sender.email`",
                ],
                "focus": "記載された組織名とメールドメインを比較する。",
            },
            criteria=NoulCriteria(
                true={
                    "what": "メールドメインと無関係の組織を名乗っている",
                    "examples": ["Acme Payrollがclaim-bonus.exampleから送信"],
                },
                false={
                    "what": "アイデンティティとドメインが一致しているか、矛盾する主張がない",
                    "examples": ["Acme Payrollがacme.exampleから送信"],
                },
            ),
        ),
        "unexpected_reward": Noul(
            instructions={
                "question": "メッセージは予期しない報酬を告知していますか？",
                "inspect": "`ticket.message`",
                "focus": "未要求の賞品、支払い、または報酬の主張を探す。",
            },
            criteria=NoulCriteria(
                true={
                    "what": "未要求の賞品、支払い、または報酬を告知している",
                    "examples": ["1,000ドルのボーナスに選ばれました"],
                },
                false={
                    "what": "報酬の主張が含まれていないか、予定された支払いについて話している",
                    "not_for": "承認済みの返金や給与振込について尋ねる顧客",
                    "examples": ["承認された返金はいつ届きますか？"],
                },
            ),
        ),
        "refund_requested": Noul(
            instructions={
                "question": "顧客は明示的に返金またはクレジットを要求していますか？",
                "inspect": "`ticket.message`",
                "focus": "苦情だけでなく、具体的な救済手段の要求を必要とする。",
            },
            criteria=NoulCriteria(
                true={
                    "what": "返金またはアカウントクレジットを直接求めている",
                    "examples": ["二重請求の返金をお願いします"],
                },
                false={
                    "what": "返金またはクレジットを求めていない",
                    "not_for": "具体的な救済手段のない苦情や請求に関する質問",
                    "examples": ["なぜ二重に請求されたのですか？"],
                },
            ),
        ),
        "mentions_open_order": Noul(
            instructions={
                "question": "メッセージは提供されたオープン注文を参照していますか？",
                "compare": [
                    "`ticket.message`",
                    "`customer.open_orders`",
                ],
                "focus": "注文IDまたはその他の識別情報を照合する。",
            },
            criteria=NoulCriteria(
                true={
                    "what": "IDまたは識別情報でオープン注文を参照している",
                    "examples": ["注文A-104はどこですか？"],
                },
                false={
                    "what": "提供されたオープン注文を特定していない",
                    "not_for": "一致する詳細のない一般的な注文に関する質問",
                    "examples": ["通常、配送にはどのくらいかかりますか？"],
                },
            ),
        ),
        "frustration": Score(
            instructions={
                "question": "顧客はどの程度不満を示していますか？",
                "inspect": "`ticket.message`",
                "focus": "問題の深刻さではなく、表明された不満を判断する。",
            },
            criteria=[
                {
                    "what": "落ち着いていて事務的",
                    "signals": ["中立的な言葉遣い", "体験への苦情がない"],
                },
                {
                    "what": "不満はあるが礼儀的",
                    "signals": ["苛立ちを表明している", "建設的な態度を保っている"],
                },
                {
                    "what": "非常に怒っているか退会を示唆している",
                    "signals": ["敵対的な言葉遣い", "解約やチャーンを示唆している"],
                },
            ],
        ),
    }

    with TypeSafeClient() as client:
        response = client.system_one(
            state=state,
            questions=questions,
        )

    # コードで制御された重みを使って独立したスパムシグナルを組み合わせる。
    answers = response.answers
    spam_risk = (
        0.45 * answers["requests_credentials"].noul
        + 0.30 * answers["sender_identity_mismatch"].noul
        + 0.25 * answers["unexpected_reward"].noul
    )

    # 推測するのではなく、不確実な判断はエスカレートする。
    spam_is_uncertain = 0.4 < spam_risk < 0.6
    if spam_is_uncertain or answers["topic"].confidence < 0.75:
        return route_to_human_review(ticket)
    if spam_risk >= 0.6:
        return quarantine_as_spam(ticket)

    # このパスでどの投機的な回答が重要かはコードが判断する。
    if answers["topic"].choice == "billing":
        return route_to_billing(
            ticket,
            refund_requested=answers["refund_requested"].noul >= 0.7,
        )
    if answers["topic"].choice == "orders":
        return route_to_orders(
            ticket,
            mentions_open_order=answers["mentions_open_order"].noul >= 0.7,
        )

    priority = (
        "high"
        if answers["frustration"].confidence >= 0.7
        and answers["frustration"].score >= 1.5
        else "normal"
    )
    return route_to_account_support(ticket, priority=priority)
```