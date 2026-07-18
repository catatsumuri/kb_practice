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

## エラー耐性

閉じ忘れたタグは親の終端で自動クローズされ、警告が vfile に記録されます。

<Info>
このタグは閉じられていませんが、ドキュメント全体が壊れることはありません。
