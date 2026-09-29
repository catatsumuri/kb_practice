# AIプライマー {#ai-primer}

> TypeSafeがテキスト生成の最適化ではなく、キャリブレーション済み確率による意思決定モデルの訓練を行う理由。

ほとんどのAI製品は、モデルと人間の会話を中心に構築されています。TypeSafeは異なる前提から出発しています。大規模な自動化はAI同士およびAIとソフトウェアのインタラクションが主流になるため、チャットインターフェースよりもマシンインターフェースの方が重要だという考え方です。

> **これをMachine Native Intelligenceと呼びます：**
>
> 構造、信頼性、観測可能性、テスト可能性、速度、一貫性、低コストといったソフトウェア的な特性を持つAI。

## 神を目指さず、本番を目指す {#building-prod-not-god}

TypeSafeはあらゆることをこなすモデルの構築を目指していません。コードが検査・実行できる狭い意思決定を必要とする本番システムのために設計されています。

大規模なAI自動化は、マシン同士のインタラクションが99%、人間のインタラクションが1%に近いものになると予想しています。これにより、設計目標が「読んで気持ちいいレスポンス」から「ソフトウェア内で予測可能に動作する出力」へとシフトします。

[TypeSafeマニフェスト](https://typesafe.ai/manifesto)をお読みください。

## 3つのポストトレーニングアプローチ {#three-post-training-approaches}

事前学習済み言語モデルは主に2つの方法で適応されてきました。TypeSafeはそこに3つ目を加えます。RLHFとRLVRは参考として示しています。TypeSafeの学習パスはRLCDです。

<Columns cols={3}>
  <Card title="RLHF" icon="messages-square" type="note">
    **人間のフィードバックによる強化学習**は、事前学習済みモデルをチャットボットに変えました。人々が好むレスポンスを生成するようモデルを訓練します。
  </Card>

  <Card title="RLVR" icon="brain-circuit" type="note">
    **検証可能な報酬による強化学習**は、数学などのタスクに強い推論モデルを生み出しましたが、速度が遅くコストも高くなっています。
  </Card>

  <Card title="RLCD" icon="binary" type="tip">
    **キャリブレーション済み意思決定のための強化学習**は、テキスト生成の代わりに意思決定とキャリブレーション済み確率を返すようTypeSafeを訓練します。
  </Card>
</Columns>

RLHFはInstructGPTとChatGPTの訓練に使用され、TypeSafeの共同創業者である[Diogo Almeida](https://scholar.google.com/citations?user=0T4y07QAAAAJ\&hl=en)によって共同発明されました。

![事前学習済み言語モデルが、控えめなRLHFとRLVRのパスと、強調されたRLCDの意思決定モデルパスに分岐する図。](https://mintcdn.com/ts-docs/aFVnpmCIX68NpsV1/images/ai-primer/training-paths-light.webp?fit=max&auto=format&n=aFVnpmCIX68NpsV1&q=85&s=61898215ac31388d3be15bf583b743ee#only-light)

![事前学習済み言語モデルが、控えめなRLHFとRLVRのパスと、強調されたRLCDの意思決定モデルパスに分岐する図。](https://mintcdn.com/ts-docs/aFVnpmCIX68NpsV1/images/ai-primer/training-paths-dark.webp?fit=max&auto=format&n=aFVnpmCIX68NpsV1&q=85&s=2747633edb0e54fa3f14a8aba830f4fd#only-dark)

## RLCDとキャリブレーション済み意思決定 {#rlcd-and-calibrated-decisions}

RLCDは異なる出力契約に最適化します：

* モデルはテキストを生成しません。
* 意思決定と確率を返します。
* 確率が高いほど、その答えが正しい可能性が高くなります。

キャリブレーションにより、不確実性をソフトウェアで活用できるようになります。キャリブレーション済みモデルによる多数の予測において：

* `0.2`の確率が割り当てられた結果は、約20%の確率で発生するはずです。
* `0.8`の確率が割り当てられた結果は、約80%の確率で発生するはずです。
* `1.0`の確率が割り当てられた結果は、100%の確率で発生するはずです。

これらの割合は予測のグループを表すものであり、個々の回答に対する保証ではありません。ソフトウェアがいつ処理を実行し、いつエスカレーションすべきかの判断については、[確信度](/confidence)をご覧ください。

## RLHFの問題点 {#the-problems-with-rlhf}

RLHFは、人々が好む発言をするようモデルを訓練します。この目標はチャットボットには適していますが、迎合的な振る舞いや自信ありげなハルシネーションを助長することもあります。

また、選好の最適化は**モード脱落**を引き起こします。モデルは指示への従順さなど特定のスタイルを好むように学習し、他の可能な出力の確率を低下させます。

![ベースモデルの確率分布と、RLHF後に狭まりモード脱落した分布の比較。](https://mintcdn.com/ts-docs/aFVnpmCIX68NpsV1/images/ai-primer/mode-dropping-light.webp?fit=max&auto=format&n=aFVnpmCIX68NpsV1&q=85&s=d51758a6212b526fc243cc9a81572cc7#only-light)

![ベースモデルの確率分布と、RLHF後に狭まりモード脱落した分布の比較。](https://mintcdn.com/ts-docs/aFVnpmCIX68NpsV1/images/ai-primer/mode-dropping-dark.webp?fit=max&auto=format&n=aFVnpmCIX68NpsV1&q=85&s=4330e6251ca335515a61f61794f21389#only-dark)

<Warning>
  出力が人間にとって説得力があっても、無人の自動化に十分な信頼性を持つとは限りません。人間の選好とマシンの信頼性は、異なる最適化目標です。
</Warning>

モード脱落は**モード崩壊**のより軽微なバージョンです。古典的なGenerative Adversarial Networkの失敗パターンでは、ジェネレーターが同じ種類の出力を繰り返し生成するように学習します。それがディスクリミネーターを欺き続けるためです。

<Accordion title="モード崩壊のアナロジー">
  ![同じキャラクターの繰り返しがモード崩壊に苦しむGANを表している図。](https://mintcdn.com/ts-docs/aFVnpmCIX68NpsV1/images/ai-primer/mode-collapse-light.webp?fit=max&auto=format&n=aFVnpmCIX68NpsV1&q=85&s=2896f125ad1a5835b31b088fbc64eff1#only-light)

  ![同じキャラクターの繰り返しがモード崩壊に苦しむGANを表している図。](https://mintcdn.com/ts-docs/aFVnpmCIX68NpsV1/images/ai-primer/mode-collapse-dark.webp?fit=max&auto=format&n=aFVnpmCIX68NpsV1&q=85&s=95645bdefd0bb3fa093edc3dd9308337#only-dark)
</Accordion>

RLHFは会話モデルには依然として適しています。TypeSafeの立場は、本番の自動化には異なる訓練目標——制約された意思決定とキャリブレーション済みの不確実性を中心としたもの——が必要だというものです。