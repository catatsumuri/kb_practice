# クイックスタート {#quick-start}

> すぐに試してみたい方へ。今すぐ始めるために必要なすべてをまとめました。

## 試してみる：Playground {#try-it-the-playground}

1. **[Playground](https://console.typesafe.ai/playground)を開いて**ログインします。
2. **任意のテキストを貼り付けて**状態として入力します。

```plaintext title="サンプルの状態" theme={null}
Stripeアカウントを3日間接続しようとしていますが、インテグレーションが失敗し続けています。売上が失われています。至急助けてください。
```

3. **質問を追加します。** Noulの質問を試してみましょう：`"Does this message express urgency?"`

```json theme={null}
{
  "urgency": {
    "type": "noul",
    "instructions": "このメッセージは緊急性を表していますか？"
  }
}
```

4. **質問をさらに追加します。** Noul、Choice、Scoreを1回の呼び出しで組み合わせて、すべての結果を一度に確認しましょう。

## 呼び出す：API {#call-it-the-api}

1. **APIキーを取得**します（[ダッシュボード](https://console.typesafe.ai/keys)から）
2. **POSTリクエストを送信**します（APIエンドポイントへ）
3. **[APIリファレンス](/api)を確認**して詳細を確認します。

```http theme={null}
POST https://api.typesafe.ai/v1/systemone
Authorization: Bearer <API_KEY>
Content-Type: application/json
```

### サンプルcURLコマンド {#sample-curl-command}

```bash theme={null}
curl -X POST https://api.typesafe.ai/v1/systemone \
  -H "Authorization: Bearer $TYPESAFE_API_KEY" \
  -H "Content-Type: application/json" \
  -d @- <<'EOF'
  {
    "state": "Stripeアカウントを3日間接続しようとしていますが、インテグレーションが失敗し続けています。売上が失われています。至急助けてください。",
    "model": "jev-latest",
    "questions": {
      "urgency": {
        "type": "noul",
        "instructions": "このメッセージは緊急性を表していますか？"
      }
    }
  }
EOF
```

### リクエストボディ {#request-body}

```json theme={null}
{
  "state": "Stripeアカウントを3日間接続しようとしていますが、インテグレーションが失敗し続けています。売上が失われています。至急助けてください。",
  "model": "jev-latest",
  "questions": {
    "department": {
      "type": "choice",
      "instructions": "このリクエストを担当すべきチームはどこか",
      "criteria": {
        "billing": "支払いまたはサブスクリプションに関する問題",
        "technical": "バグまたはインテグレーションの問題",
        "sales": "料金またはアカウントに関する質問"
      }
    },
    "frustration": {
      "type": "score",
      "instructions": "顧客がどの程度不満を感じているか",
      "criteria": [
        "冷静で、事実を述べているだけ",
        "不満はあるが礼儀正しい",
        "非常に怒っており、強い言葉を使っている"
      ]
    },
    "is_urgent": {
      "type": "noul",
      "instructions": "メッセージが緊急性または時間的な切迫感を伝えている"
    }
  }
}
```

### レスポンスボディ {#response-body}

```json theme={null}
{
  "model": "jev-1.13.0",
  "answers": {
    "department": {
      "type": "choice",
      "choice": "technical",
      "confidence": 0.78,
      "probabilities": {
        "technical": 0.85,
        "sales": 0.0,
        "billing": 0.15
      }
    },
    "frustration": {
      "type": "score",
      "score": 1.0,
      "confidence": 1.0,
      "legend": {
        "0": "冷静で、事実を述べているだけ",
        "1": "不満はあるが礼儀正しい",
        "2": "非常に怒っており、強い言葉を使っている"
      },
      "probabilities": {
        "0": 0.0,
        "1": 1.0,
        "2": 0.0
      }
    },
    "is_urgent": {
      "type": "noul",
      "noul": 1.0
    }
  },
  "usage": {
    "input_tokens": 392,
    "output_tokens": 65
  }
}
```

詳細については[APIリファレンス](/api)を参照してください。

## コードで使う：Python SDK {#code-it-the-python-sdk}

1. **SDKをインストールします**（Python >= 3.10が必要です）。

```bash title="pipを使う場合" theme={null}
pip install typesafe-sdk
```

```bash title="uvを使う場合" theme={null}
uv add typesafe-sdk
```

2. **SDKを使用します。** クライアントは環境変数から`TYPESAFE_API_KEY`を読み取り、デフォルトで`jev-latest`を呼び出します。

```python theme={null}
from typesafe_sdk import Choice, Noul, Score, TypeSafeClient

client = TypeSafeClient()

ticket = "Stripeアカウントを3日間接続しようとしていますが、インテグレーションが失敗し続けています。売上が失われています。至急助けてください。"

response = client.system_one(
    state=ticket,
    questions={
        "department": Choice(
            instructions="このリクエストを担当すべきチームはどこか",
            criteria={
                "billing": "支払いまたはサブスクリプションに関する問題",
                "technical": "バグまたはインテグレーションの問題",
                "sales": "料金またはアカウントに関する質問",
            },
        ),
        "frustration": Score(
            instructions="顧客がどの程度不満を感じているか",
            criteria=[
                "冷静で、事実を述べているだけ",
                "不満はあるが礼儀正しい",
                "非常に怒っており、強い言葉を使っている",
            ],
        ),
        "is_urgent": Noul(
            instructions="メッセージが緊急性または時間的な切迫感を伝えている",
        ),
    },
)

print(response.answers["department"].choice)  # "technical"
print(response.answers["frustration"].score)  # 1.0
print(response.answers["is_urgent"].noul)     # 1.0
```

インストール方法と詳細な使い方については[クライアントSDK](/sdk)を参照してください。

## バイブコーディング：エージェントスキル {#vibe-it-the-agent-skill}

1. **[TypeSafeスキルをインストール](/agent-skill#installation)** します。Claude Codeプラグインか`npx skills add typesafe-ai/skills --skill typesafe-ai`を使用します。[GitHubでSKILL.mdを読む](https://github.com/typesafe-ai/skills/blob/main/skills/typesafe-ai/SKILL.md)こともできます。

<Tabs>
  <Tab title="Claude Code">
    ターミナルで次の2つのコマンドを実行します：

    ```bash theme={null}
    claude plugin marketplace add typesafe-ai/skills
    claude plugin install typesafe@typesafe-ai
    ```
  </Tab>

  <Tab title="その他のエージェント">
    ```bash theme={null}
    npx skills add typesafe-ai/skills --skill typesafe-ai
    ```

    プロンプトが表示されたらエージェントを選択します。インストールはデフォルトでプロジェクトローカルです。グローバルにインストールするには`-g`を追加します。
  </Tab>

  <Tab title="エージェントにコピーする">
    コーディングエージェントにこのプロンプトを貼り付けます：

    ```text wrap theme={null}
    TypeSafeスキルをインストールしてください。Claude Codeを使用している場合は`claude plugin marketplace add typesafe-ai/skills`を実行し、次に`claude plugin install typesafe@typesafe-ai`を実行します。別のエージェントを使用している場合は`npx skills add typesafe-ai/skills --skill typesafe-ai`を実行してエージェントを選択します。インストール方法は1つだけ使用してください。スキルはhttps://github.com/typesafe-ai/skills/blob/main/skills/typesafe-ai/SKILL.md（raw: https://raw.githubusercontent.com/typesafe-ai/skills/main/skills/typesafe-ai/SKILL.md）で直接読むことができます。その後、このプロジェクトの作業にTypeSafeスキルを使用してください。
    ```
  </Tab>
</Tabs>

2. **コーディングエージェントに**ビルド中にTypeSafeスキルを使うよう指示します！

```plaintext title="コーディングエージェントへのプロンプト" theme={null}
TypeSafe APIを使って、指定されたドキュメント群を複数の観点で評価するシンプルなCLIを作りましょう。TypeSafeスキルを使ってTypeSafe APIの使い方とシステムの構成方法を理解してください。どのようなドキュメントをどのような観点で評価したいかについて、私に質問してください。
```

詳細については[エージェントスキル](/agent-skill)ページを参照してください。