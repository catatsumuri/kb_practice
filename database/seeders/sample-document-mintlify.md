このドキュメントは inkstream2(AST ベースの Mintlify タグエンジン)のレンダリングテストです。

## コールアウト

<Note>
これは補足情報です。**Markdown** も中で使えます。
</Note>

<Warning>単一行のタグペアもレンダリングできます。</Warning>

<Tip>
タグの前後に空行がなくても正規化パスが吸収します。
</Tip>

## ステップ

<Steps>
<Step title="依存をインストール">
`npm install` を実行します。
</Step>
<Step title="開発サーバーを起動">
`composer run dev` を実行します。
</Step>
<Step title="確認">
このページが正しく表示されていれば成功です。
</Step>
</Steps>

## カード

<CardGroup cols={2}>
<Card title="inkstream2" href="https://github.com/catatsumuri/inkstream2">
新しい AST ベースのエンジン。ネスト制限なし。
</Card>
<Card title="自己閉じカード" />
</CardGroup>

## タブとアコーディオン(ネスト)

<Tabs>
<Tab title="npm">

```bash
npm install
```

</Tab>
<Tab title="解説">
<AccordionGroup>
<Accordion title="なぜ AST ベースなのか">
コロンフェンスの深さエンコードが不要になり、ネスト段数の上限がなくなります。

<Note>
タブ → アコーディオン → コールアウトの 3 段ネストです。
</Note>
</Accordion>
</AccordionGroup>
</Tab>
</Tabs>

## インラインタグ

これは <Badge color="green">New</Badge> なバッジと <Tooltip tip="Abstract Syntax Tree">AST</Tooltip> という用語です。文中の他のテキストを崩さずにペアリングされます。

## コードフェンス変換 (tree/quiz/chart)

```tree
.
├── src
│   ├── index.ts
│   └── lib
│       └── a.ts
└── package.json
```

```quiz
question: inkstream2 のタグペアリングはどのデータ構造の上で動く?
A: 文字列の正規表現置換
B: mdast (Markdown AST)
C: DOM
correct: B
explanation: v1 は文字列前処理でしたが、v2 は remark が作る mdast ツリーの上で直接タグをペアリングします。
```

```chart:bar
_title: golden corpus 一致状況
一致: 3
差分: 6
```

## ネイティブディレクティブ (:::message / :::details)

Mintlify タグとは別に、Zenn 記法の `:::` ディレクティブを直接書くこともできます。

:::message alert
これはコロンフェンス記法で直接書いた警告メッセージです。Mintlify の `<Warning>` タグと同じ `aside.msg` コンポーネントで描画されます。
:::

:::details 詳しい仕組み
これは remark-directive が要求する `[label]` 形式へ、行頭のショートハンドを正規化してから解釈されます。
:::

## エラー耐性

閉じ忘れたタグは親の終端で自動クローズされ、警告が vfile に記録されます。

<Info>
このタグは閉じられていませんが、ドキュメント全体が壊れることはありません。
