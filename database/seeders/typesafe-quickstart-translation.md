# クイックスタート {#quick-start}

> すぐに始めたいですか？今すぐ開始するために必要なすべてがここにあります。

## 試してみる：Playground {#try-it-the-playground}

1. **[Playground](https://console.typesafe.ai/playground) を開き**、ログインします。
2. **任意のテキストを貼り付け**、状態として設定します。

```plaintext title="Sample state" theme={null}
Hi, I've been trying to connect my Stripe account for 3 days and the integration keeps failing. I'm losing sales. Please help ASAP.
```

3. **質問を追加します。** Noul 質問を試してみましょう：`"Does this message express urgency?"`

```json theme={null}
{
  "urgency": {
    "type": "noul",
    "instructions": "Does this message express urgency?"
  }
}
```

4. **さらに質問を追加します。** Noul、Choice、Score を1回の呼び出しで組み合わせて、すべての結果を一度に確認できます。

## 呼び出す：API {#call-it-the-api}

1. **APIキーを取得**します（[ダッシュボード](https://console.typesafe.ai/keys)から）。
2. **API エンドポイントに POST リクエストを送信**します。
3. **[API リファレンス](/api)** で詳細を確認します。

```http theme={null}
POST https://api.typesafe.ai/v1/systemone
Authorization: Bearer <API_KEY>
Content-Type: application/json
```

### cURL コマンドの例 {#sample-curl-command}

```bash theme={null}
curl -X POST https://api.typesafe.ai/v1/systemone \
  -H "Authorization: Bearer $TYPESAFE_API_KEY" \
  -H "Content-Type: application/json" \
  -d @- <<'EOF'
  {
    "state": "Hi, I've been trying to connect my Stripe account for 3 days and the integration keeps failing. I'm losing sales. Please help ASAP.",
    "model": "jev-latest",
    "questions": {
      "urgency": {
        "type": "noul",
        "instructions": "Does this message express urgency?"
      }
    }
  }
EOF
```

### リクエストボディ {#request-body}

```json theme={null}
{
  "state": "Hi, I've been trying to connect my Stripe account for 3 days and the integration keeps failing. I'm losing sales. Please help ASAP.",
  "model": "jev-latest",
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
    "frustration": {
      "type": "score",
      "instructions": "How frustrated the customer appears",
      "criteria": [
        "Calm, just stating facts",
        "Frustrated but civil",
        "Very angry, strong language"
      ]
    },
    "is_urgent": {
      "type": "noul",
      "instructions": "The message conveys urgency or time-sensitivity"
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
        "0": "Calm, just stating facts",
        "1": "Frustrated but civil",
        "2": "Very angry, strong language"
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

詳細については [API リファレンス](/api) を参照してください。

## コードで使う：Python SDK {#code-it-the-python-sdk}

1. **SDK をインストール**します（Python >= 3.10 が必要です）。

```bash title="With pip" theme={null}
pip install typesafe-sdk
```

```bash title="With uv" theme={null}
uv add typesafe-sdk
```

2. **SDK を使用します。** クライアントは環境変数から `TYPESAFE_API_KEY` を読み取り、デフォルトで `jev-latest` を呼び出します。

```python theme={null}
from typesafe_sdk import Choice, Noul, Score, TypeSafeClient

client = TypeSafeClient()

ticket = "Hi, I've been trying to connect my Stripe account for 3 days and the integration keeps failing. I'm losing sales. Please help ASAP."

response = client.system_one(
    state=ticket,
    questions={
        "department": Choice(
            instructions="Which team should handle this",
            criteria={
                "billing": "Payment or subscription issues",
                "technical": "Bugs or integration problems",
                "sales": "Pricing or account questions",
            },
        ),
        "frustration": Score(
            instructions="How frustrated the customer appears",
            criteria=[
                "Calm, just stating facts",
                "Frustrated but civil",
                "Very angry, strong language",
            ],
        ),
        "is_urgent": Noul(
            instructions="The message conveys urgency or time-sensitivity",
        ),
    },
)

print(response.answers["department"].choice)  # "technical"
print(response.answers["frustration"].score)  # 1.0
print(response.answers["is_urgent"].noul)     # 1.0
```

インストール方法と詳細な使い方については [クライアント SDK](/sdk) を参照してください。

## エージェントで使う：エージェントスキル {#vibe-it-the-agent-skill}

1. **[TypeSafe スキルをインストール](/agent-skill#installation)** します。Claude Code プラグイン、または `npx skills add typesafe-ai/skills --skill typesafe-ai` を使用します。[GitHub で SKILL.md を読む](https://github.com/typesafe-ai/skills/blob/main/skills/typesafe-ai/SKILL.md) こともできます。

<Tabs>
  <Tab title="Claude Code">
    ターミナルで以下の2つのコマンドを実行します：

    ```bash theme={null}
    claude plugin marketplace add typesafe-ai/skills
    claude plugin install typesafe@typesafe-ai
    ```
  </Tab>

  <Tab title="Other agents">
    ```bash theme={null}
    npx skills add typesafe-ai/skills --skill typesafe-ai
    ```

    プロンプトが表示されたらエージェントを選択します。インストールはデフォルトでプロジェクトローカルです。グローバルにインストールするには `-g` を追加してください。
  </Tab>

  <Tab title="Copy to your agent">
    コーディングエージェントに以下のプロンプトを貼り付けます：

    ```text wrap theme={null}
    Install the TypeSafe skill. If you're in Claude Code, run `claude plugin marketplace add typesafe-ai/skills`, then `claude plugin install typesafe@typesafe-ai`. If you're in another agent, run `npx skills add typesafe-ai/skills --skill typesafe-ai` and select your agent. Use one installation method. You can read the skill directly at https://github.com/typesafe-ai/skills/blob/main/skills/typesafe-ai/SKILL.md (raw: https://raw.githubusercontent.com/typesafe-ai/skills/main/skills/typesafe-ai/SKILL.md). Then use the TypeSafe skill when working on this project.
    ```
  </Tab>
</Tabs>

2. **コーディングエージェントに指示**して、TypeSafe スキルをビルドに活用しましょう！

```plaintext title="Coding agent prompt" theme={null}
Let's build a simple CLI that uses the TypeSafe API to evaluate a set of supplied documents on multiple dimensions. Use the TypeSafe skill to understand how to use the TypeSafe API and how to structure the system. Ask me questions about what kinds of documents I want to evaluate and on what dimensions.
```

詳細については [エージェントスキル](/agent-skill) ページを参照してください。