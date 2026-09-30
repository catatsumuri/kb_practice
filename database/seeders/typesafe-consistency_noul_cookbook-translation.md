# 自己一致性：nouls {#self-consistency-nouls}

> 不確かな確率を人間によるレビューにルーティングしながら、基礎となるnoul値を可視化し続けます。

このクックブックでは、1件の自動車保険請求を取り上げ、14問のルーブリックを15回実行して、
各回答が繰り返しにわたって一定に保たれるかどうかを確認します。すべてのチェックは
`Noul`なので、各回答は1つのTrue/False質問に対するP(true)です。受信した請求を支払い・
拒否・人間への送付に振り分ける請求トリアージパイプラインでは、確率が意思決定を導きます。
閾値付近の小さな変化が、どのアクションを取るかを変えてしまうことがあります。

ルーブリックは14問の`Noul`質問で構成されており、各実行は14問すべてに答える1回の呼び出し
です。条件ごとに`NUM_SAMPLES` = 15回の繰り返しを行います。条件とは1つのモデルと1つの設定
の組み合わせであり、返ってきたすべての確率を表示します。

条件：

* 非推論LLM `claude-haiku-4-5`と`gpt-5.4-mini`を、温度`0`とAPIデフォルトで使用。
* 同じ2つの非推論モデルをTrue/Falseモードで使用：質問ごとに単純なyes/noのみを返し、1.0と0.0にマッピング。
* 推論LLM `gpt-5.5`と`claude-opus-4-8`（温度の調整なし）。
* TypeSafe：14問の`Noul`質問に対して1回の`system_one`呼び出しを実行し、各呼び出しに新しい`uid`フィールド（使い捨ての一意な値）を付与。

注目すべき点：LLMの回答は実行ごとに変動し、温度`0`でも同様であり、判断を要する質問では
モデルが*自己*と矛盾します。TypeSafeの質問ごとの確率標準偏差の平均は`0.0102`で、ここで
取り上げたすべてのLLM確率条件を下回っています。`covered`の回答は`0.43`から`0.53`の
範囲にあり、`0.5`の意思決定閾値を越えています。

また、`0.30`から`0.70`までの確率を、人間によるレビューのための明示的な`uncertain`（不確か）
な結果に変換します。最終的な図解は、TypeSafeの確率をこれらのアクションにマッピングしながら、
基礎となる確率を可視化します。

## セットアップ {#setup}

```bash theme={null}
pip install anthropic openai matplotlib ipython 'cooksafe>=0.2.0,<0.3.0'
```

次に`TYPESAFE_API_KEY`、`ANTHROPIC_API_KEY`、`OPENAI_API_KEY`を設定します。
この実行では本番APIの`jev-latest`を使用し、2026年9月11日にサンプリングされました。

```python expandable theme={null}
import hashlib
import json
import os
import textwrap
from collections import Counter
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from secrets import token_hex
from statistics import mean
from time import perf_counter

import anthropic
import matplotlib
import matplotlib.pyplot as plt
import numpy as np
from cooksafe import JsonCache, make_playground_link
from IPython.display import Markdown, display
from matplotlib.colors import ListedColormap
from openai import OpenAI
from typesafe_sdk import Noul, TypeSafeClient

matplotlib.use("Agg")  # ヘッドレスレンダリング

BASE_MODELS = [
    "claude-haiku-4-5",
    "gpt-5.4-mini",
]  # 非推論モデル：温度0＋APIデフォルト
REASONING_MODELS = [
    "gpt-5.5",
    "claude-opus-4-8",
]  # 推論モデル：まず考える、温度なし
TYPESAFE_MODEL = "jev-latest"  # TypeSafeモデル
NUM_SAMPLES = 15  # 条件ごとの請求+ルーブリック呼び出しの繰り返し回数
NOUL_UNCERTAINTY_LOW = 0.30
NOUL_UNCERTAINTY_HIGH = 0.70

LLM_PRICES = {  # 100万トークンあたりの金額（入力、出力）；価格とモデルIDは2026年7月時点、READMEを参照
    "claude-haiku-4-5": (1.00, 5.00),
    "gpt-5.4-mini": (0.75, 4.50),
    "gpt-5.5": (5.00, 30.00),
    "claude-opus-4-8": (5.00, 25.00),
}
TYPESAFE_PRICE = (0.042, 0.00)  # TypeSafeの過去レート、2026年8月時点

anthropic_client = anthropic.Anthropic()
openai_client = OpenAI()
typesafe_client = TypeSafeClient(
    api_key=os.environ["TYPESAFE_API_KEY"],
    base_url="https://api.typesafe.ai",
    timeout=30.0,
)
```

## 状態：JSONとしての自動車保険請求 {#the-state-an-auto-insurance-claim-as-json}

いくつかのボーダーラインなケースを組み込んだ1件の請求：

* 損失はトラックデーイベント（ポリシーは「トラック/競技走行」を除外）で発生しましたが、
  サーキット上ではなく、車が静止している観客用駐車場で起きました。
* レンタカーの費用が請求されていますが、ポリシーにはレンタカー補償がありません。
* 警察報告書が添付されていませんが、2,000ドルを超える衝突事故にはポリシー上必要です。
* 自動トリアージのメモが、人間によるレビューも控除額の差し引きもなく、すでに「承認・全額
  支払い」とマークしています。

以下のルーブリック質問の中には明確なものもあれば、サンプリングされたLLMの回答が散らばり、
モデル間で意見が異なるボーダーラインなものも複数あります。

請求はJSON構造です。LLMはプロンプトで`json.dumps(CLAIM)`を受け取り、TypeSafeは構造を
状態として直接受け取ります。

```python expandable theme={null}
CLAIM = {
    "policy": {
        "policy_id": "AP-77413",
        "policyholder": "Dana M.",
        "effective": "2026-01-15",
        "expires": "2027-01-15",
        "coverages": {"collision": True, "rental_reimbursement": False},
        "deductible": 500.00,
        "per_incident_limit": 10000.00,
        "listed_drivers": ["Dana M.", "Sam M."],
        "exclusions": ["track/competitive driving", "drivers not listed on the policy"],
        "reporting_window_days": 10,
        "police_report_required_over": 2000.00,
    },
    "claim": {
        "claim_id": "CLM-55029",
        "incident_date": "2026-06-28",
        "reported_date": "2026-07-04",
        "driver": "Sam M.",
        "description": "トラックデーイベントに参加中、静止状態の観客用駐車場で別の車に追突された。"
        "サーキット上での出来事ではない。",
        "amount_claimed": 3250.00,
        "line_items": [
            {"item": "リアバンパー交換", "cost": 1700.00},
            {"item": "塗装＋仕上げ", "cost": 800.00},
            {"item": "駐車センター再キャリブレーション", "cost": 450.00},
            {"item": "レンタカー（6日間）", "cost": 300.00},
        ],
        "documentation": ["修理見積書（PDF）", "損傷写真8枚"],
    },
    "adjuster_notes": [
        {
            "author": "auto-triage",
            "note": "衝突補償が有効。承認済み。保険契約者へ3,250ドルを"
            "5〜10営業日以内に支払う。",
        }
    ],
    "claim_history": {"claims_last_12mo": 2, "prior_denied": 0},
}
```

## ルーブリック：14問の`Noul`質問 {#the-rubric-14-noul-questions}

1行につき1つの`キー -> 質問`エントリで、yesが確認対象のことが真であることを意味するよう
に表現されています。これにより、すべての行が比較可能になります：各モデルの確率と
TypeSafeの`noul`が同じことを測定します。

```python theme={null}
QUESTIONS = {
    "covered": "この損失はポリシーの衝突補償の対象になりますか？",
    "exclusion": "この損失にポリシーの除外条項が適用されますか？",
    "on_circuit": "車両がレーストラック上を走行中に衝突が発生しましたか？",
    "deductible": "支払い前に500ドルの控除額が正しく適用されますか？",
    "docs_sufficient": "添付された書類は、現状のまま請求を裁定するのに十分ですか？",
    "within_limit": "請求金額はインシデントあたりの補償限度額の範囲内ですか？",
    "within_window": "損失はポリシーの有効補償期間内に発生しましたか？",
    "reported_timely": "損失はポリシーの所定期間内に報告されましたか？",
    "rental_eligible": "レンタカー費用はこのポリシーの補償対象として還付の資格がありますか？",
    "fraud_flag": "不正審査を要する兆候がありますか？",
    "human_review": "支払いは人間の損害査定者のレビューなしに自動トリアージで承認されましたか？",
    "manual_review": "この請求は支払い前に手動・監督者レビューにルーティングされるべきですか？",
    "line_items_sum": "請求された明細費用の合計は請求総額と一致しますか？",
    "subrogation": "保険会社が代位弁済回収を追求できる可能性のある過失のある第三者はいますか？",
}
```

## 質問の方法 {#how-we-ask}

各LLM呼び出しは`json.dumps(CLAIM)`と14問すべての質問を含む1つのプロンプトです。モデルは
各質問のキーを確率にマッピングするJSONオブジェクトを返します。呼び出しはモデル名によって
AnthropicまたはOpenAIにルーティングされます：非推論モデルは`temperature`（`0`またはAPI
デフォルト）を受け取り、推論モデルは最初に考えてから温度なしで応答します。

非推論モデルはTrue/Falseバリアントも実行します：各質問に対して単純なyes/noのみで答え、
1.0と0.0にマッピングします。これにより強制的な決定が行われ、これらのモデルが不確かな中間
に確率を残せない場合の動作が示されます。

TypeSafe呼び出しは、同じ請求と同じ14問の`Noul`質問に対する1回の`system_one`リクエストです。
各回答の`noul`はP(true)です。

すべてのクエリには新しい`uid`（使い捨ての一意な値）も付与されます。これは請求やルーブリック
を変更せずに実行ごとに変わります。LLMプロンプトとTypeSafeの状態の追加フィールドとして
現れます。このセットアップでは、無関係なフィールドへの感度と、同一リクエストで発生する
変動を分離することはできません。

> **注意：** "ONLY a JSON object"という指示にもかかわらず、`claude-haiku-4-5`はほぼ
> すべての返答を、厳密な`json.loads`が拒否する` ```json ... ``` `フェンスで囲みます
>（他のモデルはベアなJSONを返します）。ヘルパーはフェンスを取り除きます。それでも
> パースに失敗した返答はパース失敗としてカウントされますが、スコアには含まれません。

各ヘルパーは回答、推定コスト、往復レイテンシを返します。

````python expandable theme={null}
def rubric_prompt(mode: str, sample_index: int) -> str:
    """請求と14問の質問を1つのプロンプトにまとめます。``mode``は回答形式を選択します。

    ``mode="prob"``は質問ごとに確率を要求し、``mode="yesno"``は単純なTrue/Falseを要求します。
    ``sample_index``はuidバスターにシードを与え、繰り返しごとに独立した別々のドローになるようにします。"""
    if mode == "yesno":
        answer_format = (
            "\n\n各質問にyes（はい）またはno（いいえ）で答えてください。\n"
            "各質問のキーを\"yes\"または\"no\"にマッピングするJSONオブジェクトのみを返し、"
            "1質問につき1エントリとしてください。"
        )
    else:
        answer_format = (
            "\n\n各質問について、答えがyesである確率を示してください。\n"
            "各質問のキーを0.00から1.00の数値にマッピングするJSONオブジェクトのみを返し、"
            "1質問につき1エントリとしてください。"
        )
    return (
        f"uid: {sample_index}:{token_hex(4)}\n\n"
        f"文書（自動車保険請求）：\n{json.dumps(CLAIM, indent=2)}\n\n質問：\n"
        + "\n".join(f"- {key}: {question}" for key, question in QUESTIONS.items())
        + answer_format
    )


def _cost(prices: tuple[float, float], input_tokens: int, output_tokens: int) -> float:
    return input_tokens / 1e6 * prices[0] + output_tokens / 1e6 * prices[1]


def _call_llm(model: str, prompt: str, temperature: float | None):
    """1回のLLM呼び出し -> (テキスト, コスト_USD, レイテンシ_秒)、モデル名でルーティング。"""
    reasoning = model in REASONING_MODELS
    started = perf_counter()
    if model.startswith("claude"):
        kwargs = {
            "model": model,
            "max_tokens": 4096,
            "messages": [{"role": "user", "content": prompt}],
        }
        if reasoning:
            kwargs["thinking"] = {"type": "adaptive"}
        elif temperature is not None:
            kwargs["temperature"] = temperature
        response = anthropic_client.messages.create(**kwargs)
        text = next((b.text for b in response.content if b.type == "text"), "")
        usage = (response.usage.input_tokens, response.usage.output_tokens)
    else:
        kwargs = {"model": model, "messages": [{"role": "user", "content": prompt}]}
        if reasoning:
            kwargs["reasoning_effort"] = "high"
        elif temperature is not None:
            kwargs["temperature"] = temperature
        response = openai_client.chat.completions.create(**kwargs)
        text = response.choices[0].message.content
        usage = (response.usage.prompt_tokens, response.usage.completion_tokens)
    return text, _cost(LLM_PRICES[model], *usage), perf_counter() - started


# すべてのサンプル（LLMとTypeSafe）は``json_cache.json``にキャッシュされます。このファイルは
# クックブックに同梱されているため、再レンダリング時にはAPI使用なしで公開済みの数値を再現
# できます。``sample_index``はキャッシュキーの一部であるため、NUM_SAMPLESの繰り返しのそれぞれが
# 独立した別々のドローになります。ライブで再サンプリングするにはファイルを削除してください。
json_cache = JsonCache(Path("json_cache.json"))


def _rubric_fingerprint() -> str:
    """プロンプト/ルーブリックの形を決めるすべてのものの短いダイジェスト：状態と各質問の
    テキスト。以下のキャッシュ呼び出しに渡されるため、請求や質問を編集するとキャッシュキーが
    変わり、古い文言で生成された古い回答を無言で返す代わりに新しいサンプルを強制します。"""
    payload = json.dumps([CLAIM, QUESTIONS], sort_keys=True, default=str)
    return hashlib.sha256(payload.encode()).hexdigest()[:12]


RUBRIC_HASH = _rubric_fingerprint()


@json_cache
def _call_typesafe(sample_index: int, rubric_hash: str, model: str):
    """1回の呼び出しに対するnoul、トークン使用量、レイテンシ、モデルメタデータを返します。

    ``rubric_hash``と``model``は、ルーブリックやモデルが変更された場合の再利用を防ぎます。
    エイリアスが後で別のバージョンに解決される可能性があるため、返されたモデルを保持します。
    """
    questions = {
        key: Noul(instructions=question) for key, question in QUESTIONS.items()
    }
    started = perf_counter()
    response = typesafe_client.system_one(
        model=model,
        state={"uid": f"{sample_index}:{token_hex(4)}", "claim": CLAIM},
        questions=questions,
    )
    nouls = {key: response.answers[key].noul for key in QUESTIONS}
    return (
        nouls,
        response.usage.input_tokens,
        response.usage.output_tokens,
        perf_counter() - started,
        {"requested_model": model, "response_model": response.model},
    )


def _parse_answer(answer: object, mode: str) -> float:
    """1つの生の質問ごとの回答 -> 確率；欠落または使用不可の場合はNaN。

    ``mode="prob"``は回答を数値として読み取り；``mode="yesno"``はTrue/Falseを1.0/0.0にマッピングします。
    それ以外（欠落キー、数値以外、yes/no以外の返答）はNaNで、正当に見える値にはなりません。"""
    if answer is None:
        return float("nan")
    if mode == "yesno":
        text = str(answer).strip().lower()
        if text == "yes":
            return 1.0
        if text == "no":
            return 0.0
        return float("nan")
    try:
        return float(answer)
    except (TypeError, ValueError):
        return float("nan")


@json_cache
def ask_llm_rubric(
    model: str,
    mode: str,
    temperature: float | None,
    sample_index: int,
    rubric_hash: str,
):
    """1回のLLMルーブリッククエリ -> (質問キーでキー付けされた質問ごとの確率, コスト_USD,
    レイテンシ_秒)；返答がパースできない箇所はNaN。``rubric_hash``は本体では使用されません——
    呼び出し側が``RUBRIC_HASH``を渡すことで、状態/ルーブリックを編集するとキャッシュが
    バストされ、古い文言で生成された古い回答を返す代わりに新しいサンプルを強制します。"""
    prompt = rubric_prompt(mode, sample_index)
    text, cost, latency = _call_llm(model, prompt, temperature)
    # 単一の```json ... ```フェンスを取り除きます（claude-haiku-4-5は"ONLY a JSON object"にもかかわらず追加します）。
    stripped = text.strip()
    if stripped.startswith("```"):
        stripped = stripped[stripped.find("\n") + 1 :] if "\n" in stripped else ""
        if stripped.rstrip().endswith("```"):
            stripped = stripped.rstrip()[: -len("```")]
    try:
        raw = json.loads(stripped)
    except (ValueError, json.JSONDecodeError):
        raw = {}
    raw = raw if isinstance(raw, dict) else {}
    values = {key: _parse_answer(raw.get(key), mode) for key in QUESTIONS}
    return values, cost, latency
````

## 実験条件 {#experimental-conditions}

### 実験グリッド {#experiment-grid}

| モデルグループ | モデル | 確率 (t=0) | 確率 (デフォルト) | Yes/no (t=0) |
| - | - | :-: | :-: | :-: |
| 非推論モデル | `claude-haiku-4-5` | ✓ | ✓ | ✓ |
| 非推論モデル | `gpt-5.4-mini` | ✓ | ✓ | ✓ |
| 推論モデル | `gpt-5.5` | — | ✓ | — |
| 推論モデル | `claude-opus-4-8` | — | ✓ | — |
| TypeSafe | `jev-latest` (`typesafe_noul`) | — | ✓ | — |

* チェックマークは1つの条件で、15回実行されます。ダッシュはテストされていない組み合わせです。
* デフォルト列は温度引数を送信しません：非推論モデルはAPIデフォルトを使用し、推論モデルと
  TypeSafeは温度設定なしで実行されます。
* Yes/noの回答は`1.0`/`0.0`にマッピングされます。
* 温度`0`は再現性のための一般的なアドバイスですので、APIデフォルトと比較します。

条件ごとに`NUM_SAMPLES` = 15回の繰り返しを行います。各繰り返しは独自のキャッシュキーを
持ち、独立した別々のドローとしてカウントされます。キャッシュ（`json_cache.json`）は
クックブックに同梱されているため、再レンダリング時に再利用されAPIコールは発生しません。
ライブで再サンプリングするにはキャッシュを削除してください。

```python expandable theme={null}
CONDITIONS = []
for model in BASE_MODELS:  # 非推論モデル：確率、次にTrue/False
    for temp_value, temp_label in ((0, "0"), (None, "default")):
        CONDITIONS.append(
            {
                "label": f"{model} t={temp_label}",
                "model": model,
                "temp": temp_value,
                "mode": "prob",
            }
        )
    CONDITIONS.append(
        {
            "label": f"{model} yes/no t=0",
            "model": model,
            "temp": 0,
            "mode": "yesno",
        }
    )
CONDITIONS += [  # 推論モデル：それぞれ1つの確率条件
    {
        "label": f"{model}-reasoning",
        "model": model,
        "temp": None,
        "mode": "prob",
    }
    for model in REASONING_MODELS
]
LABELS = [condition["label"] for condition in CONDITIONS]

runs: dict[
    str, list
] = {}  # ラベル -> {質問キー: 確率}のNUM_SAMPLESサンプル
stats: dict[str, list] = {}  # ラベル -> NUM_SAMPLESの(コスト_USD, レイテンシ_秒)ペア
with ThreadPoolExecutor(max_workers=16) as pool:
    futures = {
        condition["label"]: [
            pool.submit(
                ask_llm_rubric,
                condition["model"],
                condition["mode"],
                condition["temp"],
                sample_index,
                RUBRIC_HASH,
            )
            for sample_index in range(NUM_SAMPLES)
        ]
        for condition in CONDITIONS
    }
    for label, sample_futures in futures.items():
        results = [future.result() for future in sample_futures]
        runs[label] = [result[0] for result in results]
        stats[label] = [(result[1], result[2]) for result in results]

# TypeSafeのサンプルはLLM呼び出しの後に順次取得されます。キャッシュ済みの再レンダリングでは
# 何も呼び出されません。
typesafe_usage_results = [
    _call_typesafe(sample_index, RUBRIC_HASH, TYPESAFE_MODEL)
    for sample_index in range(NUM_SAMPLES)
]
# 実行内でエイリアスの変更が見えるよう、返されたすべてのバージョンを報告します。
typesafe_model_counts = Counter(
    result[4]["response_model"]
    for result in typesafe_usage_results
)
print(f"TypeSafe requestedモデル: {TYPESAFE_MODEL}")
print(f"TypeSafe returnedモデル（呼び出し回数）: {dict(sorted(typesafe_model_counts.items()))}")
# 新しいサンプルを必要とせずに価格変更を反映できるよう、キャッシュ取得後に価格を適用します。
typesafe_results = [
    (nouls, _cost(TYPESAFE_PRICE, input_tokens, output_tokens), latency)
    for nouls, input_tokens, output_tokens, latency, _metadata in typesafe_usage_results
]
typesafe_runs = [result[0] for result in typesafe_results]
stats["typesafe_noul"] = [(result[1], result[2]) for result in typesafe_results]
```

```
TypeSafe requested model: jev-latest
TypeSafe returned models (calls): {'jev-1.13.0': 15}
```

### コスト＋速度（ルーブリッククエリあたり） {#cost-speed-per-rubric-query}

以下のコストは、TypeSafeの`speed_latest`レートを含む、セットアップの過去の価格前提を使用
しています。これらは検証済みの`jev-latest`価格や現在の請求金額ではありません。

1行は14問すべてを含む1回のルーブリック呼び出しです。`time/call`と`cost/call`は15回の
呼び出しの平均であり、`vs ts_noul`列はTypeSafeの数値で割った値です。

```python theme={null}
typesafe_cost = mean([cost for cost, _latency in stats["typesafe_noul"]])
typesafe_latency = mean([latency for _cost, latency in stats["typesafe_noul"]])
name_w = max(len(name) for name in [*LABELS, "typesafe_noul"]) + 2
# 相対速度とコストの列を狭く保てるよう、比較ヘッダーを積み重ねます。
print(
    f"{'':<{name_w + 31}}{'速度 vs':>11}{'コスト vs':>11}\n"
    f"{'条件':<{name_w}}{'呼び出し':>7}{'time/call':>11}{'cost/call':>13}"
    f"{'ts_noul':>11}{'ts_noul':>11}"
)
for name in LABELS + ["typesafe_noul"]:
    costs, latencies = zip(*stats[name])
    cost = mean(costs)
    latency = mean(latencies)
    print(
        f"{name:<{name_w}}{len(costs):>7}{latency * 1000:>9.0f}ms"
        f"{'$' + format(cost, '.6f'):>13}"
        f"{format(latency / typesafe_latency, '.1f') + 'x':>11}"
        f"{format(cost / typesafe_cost, '.1f') + 'x':>11}"
    )
```

```
                                                               speed vs    cost vs
condition                      calls  time/call    cost/call    ts_noul    ts_noul
claude-haiku-4-5 t=0              15     1780ms    $0.001798      16.0x      42.2x
claude-haiku-4-5 t=default        15     1644ms    $0.001798      14.8x      42.2x
claude-haiku-4-5 yes/no t=0       15     1485ms    $0.001650      13.4x      38.8x
gpt-5.4-mini t=0                  15     1405ms    $0.001089      12.7x      25.6x
gpt-5.4-mini t=default            15     1177ms    $0.001179      10.6x      27.7x
gpt-5.4-mini yes/no t=0           15     1113ms    $0.000950      10.0x      22.3x
gpt-5.5-reasoning                 15    11125ms    $0.033157     100.2x     778.9x
claude-opus-4-8-reasoning         15    13886ms    $0.034275     125.0x     805.1x
typesafe_noul                     15      111ms    $0.000043       1.0x       1.0x
```

この実行では、TypeSafeの平均往復レイテンシは111msです。LLM条件は上記の同時実行設定の下で、
呼び出しあたり1.1〜13.9秒の範囲です。

## プロット：ヒートマップとしてのすべてのサンプル {#plot-every-sample-as-a-heatmap}

読み方：

* 外側の行グループ：質問。
* 内側の行：条件。
* 列：1回の完全なルーブリック呼び出し。
* セルの色：赤はP(yes)が高い、緑は低い。リスク質問では、赤いセルはルーブリックがフラグを
  立てたものです。

`typesafe_noul`は`covered`（`0.43`から`0.53`）と`exclusion`（`0.53`から`0.62`）で最も
変動します。一部のLLM行は温度`0`でも変動します。条件は判断を要する質問で意見が異なります。

```python expandable theme={null}
rows_per_block = len(LABELS) + 1  # 質問ブロックあたりの行数
GAP = 1  # 質問ブロック間の空白スペーサー行数
row_values, row_labels, blocks = [], [], []
for question_index, (question_key, question_text) in enumerate(QUESTIONS.items()):
    if question_index:  # 空白スペーサー行（NaN -> 白でレンダリング）でブロックを区切る
        row_values.extend([np.nan] * NUM_SAMPLES for _ in range(GAP))
        row_labels.extend([""] * GAP)
    blocks.append(
        (len(row_values), question_key, question_text)
    )  # (このブロックの最初の行, 質問キー, 質問テキスト)
    for label in LABELS:
        row_values.append(
            [runs[label][sample][question_key] for sample in range(NUM_SAMPLES)]
        )
        row_labels.append(label)
    row_values.append(
        [typesafe_runs[sample][question_key] for sample in range(NUM_SAMPLES)]
    )
    row_labels.append("typesafe_noul")
heatmap_matrix = np.array(row_values)
cmap = plt.get_cmap("RdYlGn_r").copy()  # 赤 = P(yes)が高い、緑 = P(yes)が低い
cmap.set_bad("white")  # スペーサー（NaN）行は空白としてレンダリング

fig, ax = plt.subplots(figsize=(11, 0.26 * len(row_values) + 1))
ax.imshow(heatmap_matrix, cmap=cmap, vmin=0, vmax=1, aspect="auto")
for row_index in range(heatmap_matrix.shape[0]):
    for col_index in range(heatmap_matrix.shape[1]):
        value = heatmap_matrix[row_index, col_index]
        if np.isnan(value):
            continue
        ax.text(
            col_index,
            row_index,
            f"{value:.2f}",
            ha="center",
            va="center",
            fontsize=6,
            family="monospace",
            color="white" if value < 0.22 or value > 0.78 else "black",
        )

ax.set_xticks(range(NUM_SAMPLES))
ax.set_xticklabels(range(1, NUM_SAMPLES + 1), fontsize=7)
ax.set_xlabel("ルーブリッククエリ")
ax.set_yticks(range(len(row_labels)))
ax.set_yticklabels(row_labels, fontsize=7)
ax.tick_params(length=0)
for edge in ("top", "right", "left", "bottom"):
    ax.spines[edge].set_visible(False)

# マルチインデックスの外側レベル：質問キー、ブロックごとに1回印刷して中央揃え、
# 質問テキストをすぐ下に折り返して表示
y_axis_transform = ax.get_yaxis_transform()
for start, question_key, question_text in blocks:
    center = start + (rows_per_block - 1) / 2
    ax.text(
        -0.2,
        center - 0.7,
        question_key,
        transform=y_axis_transform,
        ha="right",
        va="center",
        fontsize=8,
        fontweight="bold",
    )
    ax.text(
        -0.2,
        center + 0.1,
        textwrap.fill(question_text, 34),
        transform=y_axis_transform,
        ha="right",
        va="top",
        fontsize=6,
        style="italic",
        color="gray",
    )

ax.set_title(
    f"ヒートマップとしてのすべてのサンプル（行 = ルーブリック質問 x 条件、{NUM_SAMPLES}列）",
    pad=12,
)
fig.tight_layout()
display(fig)
```

![output](https://mintcdn.com/ts-docs/BBcnWK7wRF0qekMh/cookbooks/consistency_noul_cookbook/consistency_noul_cookbook.executed.1.png?fit=max&auto=format&n=BBcnWK7wRF0qekMh&q=85&s=a50edf2fb3abafd64374936a630d57bc)

事実確認はほとんどの条件で安定しています。LLMの行が動くのは判断が重い質問です：
`exclusion`、`rental_eligible`、`fraud_flag`、`manual_review`はサンプル間でシフトするか、
モデル間で意見が異なります。TypeSafeの`covered`行は`0.5`を越えており、残りの13問はこの
実行全体を通じてその閾値の片側に留まっています。

## yes/noを強制する代わりに不確かな決定を許可する {#allow-an-uncertain-decision-instead-of-forcing-yes-or-no}

閾値`0.5`では、`0.49`と`0.51`の確率はどちらも相当な不確かさを表しているにもかかわらず、
反対のアクションを引き起こします。アプリケーションは代わりに以下を返すことができます：

* `0.30`未満：`no`；
* `0.30`から`0.70`（両端を含む）：`uncertain`；
* `0.70`超：`yes`。

不確かなケースは人間に渡されます。エスカレーションは返された確率に対するアプリケーション
ロジックです：新しい質問も2回目のAPI呼び出しも不要です。このバンドは例示的なものであり、
キャリブレーション済みの保証でも最適化された閾値でもありません。本番用の境界値は、
ラベル付きの例と、誤った決定のコストおよびレビューのコストから設定してください。

以下の図解では、記録されたTypeSafeの確率にこのバンドを適用します。

```python expandable theme={null}
def noul_decision_with_uncertainty(probability: float) -> str:
    """有効なTypeSafeの確率を、包括的な不確かさバンドを通じてマッピングします。"""
    if probability < NOUL_UNCERTAINTY_LOW:
        return "no"
    if probability > NOUL_UNCERTAINTY_HIGH:
        return "yes"
    return "uncertain"


# 各TypeSafeのアプリケーション決定の下に確率を見えるように保ちます。
policy_decisions = [
    [noul_decision_with_uncertainty(sample[key]) for sample in typesafe_runs]
    for key in QUESTIONS
]
decision_codes = {"no": 0, "uncertain": 1, "yes": 2}
policy_values = [
    [decision_codes[value] for value in row] for row in policy_decisions
]
policy_cmap = ListedColormap(["#a6dba0", "#dddddd", "#92c5de"])
fig_policy, ax_policy = plt.subplots(figsize=(13, 6))
ax_policy.imshow(policy_values, cmap=policy_cmap, vmin=0, vmax=2, aspect="auto")
for row_index, key in enumerate(QUESTIONS):
    for sample_index in range(NUM_SAMPLES):
        decision = policy_decisions[row_index][sample_index]
        probability = typesafe_runs[sample_index][key]
        ax_policy.text(sample_index, row_index, f"{decision}\n{probability:.2f}",
                       ha="center", va="center", fontsize=6)
ax_policy.set_yticks(range(len(QUESTIONS)), list(QUESTIONS))
ax_policy.set_xticks(range(NUM_SAMPLES), range(1, NUM_SAMPLES + 1))
ax_policy.set_xlabel("ルーブリッククエリ")
ax_policy.set_title(
    "TypeSafeのアプリケーション決定：グレーは不確か "
    f"（{NOUL_UNCERTAINTY_LOW:.2f}から{NOUL_UNCERTAINTY_HIGH:.2f}を含む）"
)
fig_policy.tight_layout()
display(fig_policy)
```

![output](https://mintcdn.com/ts-docs/BBcnWK7wRF0qekMh/cookbooks/consistency_noul_cookbook/consistency_noul_cookbook.executed.2.png?fit=max&auto=format&n=BBcnWK7wRF0qekMh&q=85&s=5f41dccb24038661ad751bb550f9dd19)

レビューバンドは`0.5`付近の変動を吸収し、反対の自動アクションを発行しません。ただし
独自の端点があります。外側の境界付近の値は、`uncertain`とyes/noの間で依然として動く可能性
があります。モデルはそのために決定論的になるわけではなく、バンドをクリアした自動決定が
正しいとは示されません。

## TypeSafe Playgroundで開く {#open-it-in-the-typesafe-playground}

以下のリンクは、同じ請求とルーブリックをPlaygroundで開きます：1件の請求、同じ14問の
`Noul`質問、TypeSafe `jev-latest`。上記で使用した変化する`uid`フィールドは省略しています。

```python theme={null}
playground_link = make_playground_link(
    {"claim": CLAIM},
    {key: Noul(instructions=question) for key, question in QUESTIONS.items()},
    models=[TYPESAFE_MODEL],
)
display(
    Markdown(
        f"🔗 [この請求+ルーブリックをTypeSafe Playgroundで開く]({playground_link})"
    )
)
```

<a href="https://console.typesafe.ai/playground#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-lTeK+k6y-RmhCs0sw3EbzkhAIoT8JY8AruShBfyqUAsMefSm85Z5rSrF4Doo94xSDGBNekECjC1iEljX29d+hYQqKCzk-QN4cnhRuGAEqpUKSYrzYwHBC5Qw0CAQ21AABq7xrzI28IoaGgxlieFmDYCAhhyApC+CAVKbYpBUFyjgCEEwgA" target="_blank" rel="noreferrer" className="text-primary">この請求+ルーブリックをTypeSafe Playgroundで開く →</a>
