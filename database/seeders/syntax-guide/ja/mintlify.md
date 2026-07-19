## Mintlify 構文

> 参考:
> - https://starter.mintlify.com/essentials/markdown
> - https://mintlify.wiki/motleyai/docs/essentials/markdown
> - https://www.mintlify.com/docs/components/index

Mintlify にはドキュメントサイト向けの MDX 形式コンポーネントが付属しています。このページでは ThinkStream の Markdown パイプラインがサポートするコンポーネントを扱います。

---

## コールアウト

Mintlify には5種類のコールアウトがあります。それぞれ内部的には Zenn スタイルの `:::message` ディレクティブに対応しています。

<Note>
  これは note コールアウトです。中立的な情報に使います。
</Note>

<Tip>
  これは tip コールアウトです。役立つアドバイスに使います。
</Tip>

<Info>
  これは info コールアウトです。補足情報に使います。
</Info>

<Warning>
  これは warning コールアウトです。注意を促す情報に使います。
</Warning>

<Check>
  これは check コールアウトです。成功状態や確認事項に使います。
</Check>

ソース:

```mdx
<Note>
  これは note コールアウトです。中立的な情報に使います。
</Note>

<Tip>
  これは tip コールアウトです。役立つアドバイスに使います。
</Tip>

<Info>
  これは info コールアウトです。補足情報に使います。
</Info>

<Warning>
  これは warning コールアウトです。注意を促す情報に使います。
</Warning>

<Check>
  これは check コールアウトです。成功状態や確認事項に使います。
</Check>
```

Zenn スタイルのディレクティブ構文を直接使うこともできます:

```md
:::message note
note の内容。
:::

:::message tip
tip の内容。
:::

:::message
info の内容。
:::

:::message alert
warning の内容。
:::

:::message check
check の内容。
:::
```

---

## カード

Mintlify では `<Card>` ブロックを使ってリンク付きのナビゲーションタイルを作成できます。

表示例:

<Card title="タブ" icon="folder" href="/syntax/index">
  関連するコンテンツを切り替え可能なタブ UI に整理します。
</Card>

<Card title="コールアウト" icon="message-square-warning" href="/syntax/zenn-syntax">
  スタイル付きのアラートで重要な情報を強調します。
</Card>

ソース:

```mdx
<Card title="タブ" icon="folder" href="/syntax/index">
  関連するコンテンツを切り替え可能なタブ UI に整理します。
</Card>
```

---

## カードグループ

カードはグループ化して、ドキュメントの目次のように使われることがよくあります。

表示例:

<CardGroup cols={2}>
  <Card title="タブ" icon="folder" href="/syntax/index">
    関連するコンテンツを切り替え可能なタブ UI に整理します。
  </Card>
  <Card title="ステップ" icon="list-ordered" href="/syntax/zenn-syntax">
    順を追ったステップで読者を手順に沿って案内します。
  </Card>
  <Card title="コールアウト" icon="message-square-warning" href="/syntax/extended-syntax">
    スタイル付きのアラートで重要な情報を強調します。
  </Card>
  <Card title="コードブロック" icon="code" href="/syntax/index">
    ファイル名オプション付きでシンタックスハイライトされたコードを表示します。
  </Card>
</CardGroup>

自己終了カード（本文なし）:

<CardGroup cols={3}>
  <Card title="npm" icon="download" href="/syntax/index" />
  <Card title="yarn" icon="zap" href="/syntax/index" />
  <Card title="pnpm" icon="rocket" href="/syntax/index" />
</CardGroup>

ソース:

```mdx
<CardGroup cols={2}>
  <Card title="タブ" icon="folder" href="/syntax/index">
    関連するコンテンツを切り替え可能なタブ UI に整理します。
  </Card>
  <Card title="ステップ" icon="list-ordered" href="/syntax/zenn-syntax">
    順を追ったステップで読者を手順に沿って案内します。
  </Card>
</CardGroup>
```

同じソースのレンダリング結果:

<CardGroup cols={2}>
  <Card title="タブ" icon="folder" href="/syntax/index">
    関連するコンテンツを切り替え可能なタブ UI に整理します。
  </Card>
  <Card title="ステップ" icon="list-ordered" href="/syntax/zenn-syntax">
    順を追ったステップで読者を手順に沿って案内します。
  </Card>
</CardGroup>

---

## タブ

Mintlify では、例を切り替えるためによく `<Tabs>` と `<Tab>` が使われます。

表示例:

<Tabs>
  <Tab title="npm">
    ```bash
    npm install
    ```
  </Tab>
  <Tab title="yarn">
    ```bash
    yarn install
    ```
  </Tab>
  <Tab title="pnpm">
    ```bash
    pnpm install
    ```
  </Tab>
</Tabs>

ソース例:

````mdx
<Tabs>
  <Tab title="npm">
    ```bash
    npm install
    ```
  </Tab>
  <Tab title="yarn">
    ```bash
    yarn install
    ```
  </Tab>
  <Tab title="pnpm">
    ```bash
    pnpm install
    ```
  </Tab>
</Tabs>
````

タブが未対応の場合のフォールバック案: `### npm` や `### pnpm` のような見出しにコンテンツを分割する。

---

## アコーディオン

アコーディオンコンポーネントは折りたたみ可能なセクションを作成します。`title` 属性が表示ラベルになります。任意の `icon` 属性も受け付けますが、このレンダラーでは無視されます。

表示例:

<Accordion title="Mintlify とは？">
  Mintlify は、美しく高性能なドキュメントサイトを作成できるドキュメントプラットフォームです。
</Accordion>

<Accordion title="どうやって始めればいいですか？" icon="rocket">
  数分でドキュメントサイトをセットアップするには、[クイックスタートガイド](/quickstart) を参照してください。
</Accordion>

ソース:

```mdx
<Accordion title="Mintlify とは？">
  Mintlify は、美しく高性能なドキュメントサイトを作成できるドキュメントプラットフォームです。
</Accordion>

<Accordion title="どうやって始めればいいですか？" icon="rocket">
  数分でドキュメントサイトをセットアップするには、[クイックスタートガイド](/quickstart) を参照してください。
</Accordion>
```

Zenn の `:::details` 構文を直接使うこともできます:

```md
:::details Mintlify とは？
Mintlify はドキュメントプラットフォームです。
:::
```

#### AccordionGroup

`<AccordionGroup>` を使うと、複数の `<Accordion>` を1つの視覚的にまとまったブロックとしてラップできます。

表示例:

<AccordionGroup>
  <Accordion title="ThinkStream とは？">
    ThinkStream は Laravel と React で構築されたドキュメントプラットフォームです。
  </Accordion>
  <Accordion title="どの構文がサポートされていますか？">
    ThinkStream は Markdown、GFM、Zenn 構文、Mintlify の MDX コンポーネントをサポートしています。
  </Accordion>
  <Accordion title="カスタムアイコンは使えますか？">
    `icon` 属性は受け付けられますが、現在このレンダラーでは無視されます。
  </Accordion>
</AccordionGroup>

ソース:

```mdx
<AccordionGroup>
  <Accordion title="ThinkStream とは？">
    ThinkStream は Laravel と React で構築されたドキュメントプラットフォームです。
  </Accordion>
  <Accordion title="どの構文がサポートされていますか？">
    ThinkStream は Markdown、GFM、Zenn 構文、Mintlify の MDX コンポーネントをサポートしています。
  </Accordion>
</AccordionGroup>
```

> 注: `<AccordionGroup>` 内の `<Accordion>` の中に、他のコンテナコンポーネント（`<Steps>` など）をネストすることはまだサポートされておらず、正しくレンダリングされません。

---

## ステップ

ステップ形式のウォークスルーには `<Steps>` と `<Step title="...">` を使い、番号付きの手順ガイドを作成します。

表示例:

<Steps>
  <Step title="ファイルを作成する">
    ドキュメントディレクトリに新しい MDX ファイルを作成します。
  </Step>
  <Step title="フロントマターを追加する">
    `title` と `description` を含む YAML フロントマターを追加します。
  </Step>
  <Step title="コンテンツを書く">
    MDX 構文を使ってドキュメントを書きます。
  </Step>
  <Step title="プレビューする">
    `mint dev` を実行して変更内容をプレビューします。
  </Step>
</Steps>

ソース:

```mdx
<Steps>
  <Step title="ファイルを作成する">
    ドキュメントディレクトリに新しい MDX ファイルを作成します。
  </Step>
  <Step title="フロントマターを追加する">
    `title` と `description` を含む YAML フロントマターを追加します。
  </Step>
  <Step title="コンテンツを書く">
    MDX 構文を使ってドキュメントを書きます。
  </Step>
  <Step title="プレビューする">
    `mint dev` を実行して変更内容をプレビューします。
  </Step>
</Steps>
```

---

## バッジ

`<Badge>` を使うと、ステータスインジケーターやラベル、メタデータを本文中にインラインで、あるいは単独の要素として表示できます。

表示例:

<Badge>Badge</Badge>
<Badge color="blue">新着</Badge>
<Badge color="green" icon="circle-check">安定版</Badge>
<Badge stroke color="orange">ベータ</Badge>
<Badge disabled icon="lock" color="gray">ロック中</Badge>

インライン利用:

この機能には <Badge color="orange" size="sm">プレミアム</Badge> プランが必要で、このエンドポイントは <Badge color="blue" shape="pill">JSON</Badge> 形式で返します。

ソース:

```mdx
<Badge>Badge</Badge>
<Badge color="blue">新着</Badge>
<Badge color="green" icon="circle-check">安定版</Badge>
<Badge stroke color="orange">ベータ</Badge>
<Badge disabled icon="lock" color="gray">ロック中</Badge>

この機能には <Badge color="orange" size="sm">プレミアム</Badge> プランが必要です。
```

---

## API フィールド

#### ResponseField

`<ResponseField>` を使って API レスポンスのフィールドを説明します。`name`、`type`、`required`、`default`、`deprecated` に対応しています。

表示例:

<ResponseField name="id" type="string" required>
  リソースの一意な識別子。
</ResponseField>

<ResponseField name="title" type="string" required>
  投稿のタイトル。
</ResponseField>

<ResponseField name="published_at" type="string | null">
  ISO 8601 形式のタイムスタンプ。投稿が未公開の場合は `null`。
</ResponseField>

<ResponseField name="slug" type="string" required deprecated>
  URL スラッグ。代わりに `handle` を使ってください。
</ResponseField>

ソース:

```mdx
<ResponseField name="id" type="string" required>
  リソースの一意な識別子。
</ResponseField>

<ResponseField name="published_at" type="string | null">
  ISO 8601 形式のタイムスタンプ。投稿が未公開の場合は `null`。
</ResponseField>

<ResponseField name="slug" type="string" required deprecated>
  URL スラッグ。代わりに `handle` を使ってください。
</ResponseField>
```

#### ParamField

`<ParamField>` を使ってリクエストパラメータを説明します。属性キー（`path`、`query`、`body`）がパラメータの出現位置を示し、その値がパラメータ名になります。

表示例:

<ParamField path="slug" type="string" required>
  ページの解決に使うスラッグ。
</ParamField>

<ParamField query="include" type="string">
  レスポンスに含める関連データのカンマ区切りリスト。
</ParamField>

<ParamField body="title" type="string" required>
  投稿のタイトル。
</ParamField>

ソース:

```mdx
<ParamField path="slug" type="string" required>
  ページの解決に使うスラッグ。
</ParamField>

<ParamField query="include" type="string">
  レスポンスに含める関連データのカンマ区切りリスト。
</ParamField>

<ParamField body="title" type="string" required>
  投稿のタイトル。
</ParamField>
```

---

## CodeGroup

`<CodeGroup>` は複数のコードブロックをタブ切り替え UI として表示します。タブのタイトルは言語識別子の後のメタ文字列から取得されます。タブラベルにアイコンを表示したい場合は、メタ文字列に `icon="..."` を追加します。タブを選択すると、そのページ内のすべての CodeGroup インスタンスで選択状態が保持されます。

表示例:

<CodeGroup>

```javascript JavaScript icon="javascript"
const response = await fetch('https://api.example.com/users', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ name: 'Alice', email: 'alice@example.com' }),
});
const data = await response.json();
```

```python Python icon="python"
import requests

response = requests.post(
    'https://api.example.com/users',
    json={'name': 'Alice', 'email': 'alice@example.com'},
)
data = response.json()
```

```php PHP icon="php"
$response = Http::post('https://api.example.com/users', [
    'name' => 'Alice',
    'email' => 'alice@example.com',
]);
$data = $response->json();
```

</CodeGroup>

2つ目の CodeGroup は1つ目と同期します — 上で Python を選択すると、こちらも Python が選択されます:

<CodeGroup>

```javascript JavaScript icon="javascript"
console.log('Hello from JavaScript');
```

```python Python icon="python"
print('Hello from Python')
```

```php PHP icon="php"
echo 'Hello from PHP';
```

</CodeGroup>

ソース:

````mdx
<CodeGroup>

```javascript JavaScript icon="javascript"
const response = await fetch('https://api.example.com/users', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ name: 'Alice', email: 'alice@example.com' }),
});
const data = await response.json();
```

```python Python icon="python"
import requests

response = requests.post(
    'https://api.example.com/users',
    json={'name': 'Alice', 'email': 'alice@example.com'},
)
data = response.json()
```

```php PHP icon="php"
$response = Http::post('https://api.example.com/users', [
    'name' => 'Alice',
    'email' => 'alice@example.com',
]);
$data = $response->json();
```

</CodeGroup>
````

---

## ツールチップ

`<Tooltip>` はインラインテキストをラップし、ホバー時にポップオーバーを表示します。`tip` はツールチップ本文、`headline` は太字のタイトル、`cta` + `href` は任意の CTA リンクに使います。

表示例:

定義については <Tooltip tip="Application Programming Interface: ソフトウェアコンポーネント同士が通信するためのプロトコルの集合。" headline="API" cta="続きを見る" href="/syntax/index">API</Tooltip> にカーソルを合わせてください。

シンプルな例: <Tooltip tip="Hypertext Markup Language — ウェブページの標準的な言語。">HTML</Tooltip> にカーソルを合わせてみてください。

ソース:

```mdx
定義については <Tooltip tip="Application Programming Interface: ソフトウェアコンポーネント同士が通信するためのプロトコルの集合。" headline="API" cta="続きを見る" href="/syntax/index">API</Tooltip> にカーソルを合わせてください。

シンプルな例: <Tooltip tip="Hypertext Markup Language — ウェブページの標準的な言語。">HTML</Tooltip> にカーソルを合わせてみてください。
```

---

## ツリー

`<Tree>` は折りたたみ可能なフォルダを持つファイルシステム階層を表示します。ディレクトリには `<Tree.Folder>`、ファイルには `<Tree.File>` を使います。フォルダに `defaultOpen` を追加すると読み込み時に展開されます。

表示例:

<Tree>
  <Tree.Folder name="app" defaultOpen>
    <Tree.Folder name="components" defaultOpen>
      <Tree.File name="Button.tsx" />
      <Tree.File name="Card.tsx" />
    </Tree.Folder>
    <Tree.Folder name="pages">
      <Tree.File name="index.tsx" />
      <Tree.File name="about.tsx" />
    </Tree.Folder>
    <Tree.File name="layout.tsx" />
  </Tree.Folder>
  <Tree.Folder name="public">
    <Tree.File name="favicon.ico" />
  </Tree.Folder>
  <Tree.File name="package.json" />
  <Tree.File name="tsconfig.json" />
</Tree>

ソース:

```mdx
<Tree>
  <Tree.Folder name="app" defaultOpen>
    <Tree.Folder name="components" defaultOpen>
      <Tree.File name="Button.tsx" />
      <Tree.File name="Card.tsx" />
    </Tree.Folder>
    <Tree.Folder name="pages">
      <Tree.File name="index.tsx" />
      <Tree.File name="about.tsx" />
    </Tree.Folder>
    <Tree.File name="layout.tsx" />
  </Tree.Folder>
  <Tree.Folder name="public">
    <Tree.File name="favicon.ico" />
  </Tree.Folder>
  <Tree.File name="package.json" />
  <Tree.File name="tsconfig.json" />
</Tree>
```

---

## Update

`<Update>` はタイムライン形式で変更履歴を表示します。`label` には日付やバージョン（アンカーリンクにもなります）、`description` にはサブタイトル、`tags` にはフィルター用のラベルを指定します。

表示例:

<Update label="2024-10-11" description="v0.2.0" tags={["Feature", "Improvement"]}>

#### カードアイコン対応の改善

`simple-icons` ライブラリのブランドアイコンが、Lucide アイコンに加えてカードでも使えるようになりました。`<Card>` の `icon` prop に任意のブランド名を指定してください。

</Update>

<Update label="2024-09-01" description="v0.1.0" tags={["Initial release"]}>

#### 初回リリース

Markdown、GFM、Zenn 構文、そしてコールアウト・カード・タブ・ステップ・コードグループを含むコア Mintlify コンポーネントに対応した Thinkstream の最初のリリースです。

</Update>

ソース:

```mdx
<Update label="2024-10-11" description="v0.2.0" tags={["Feature", "Improvement"]}>

#### カードアイコン対応の改善

`simple-icons` ライブラリのブランドアイコンが、Lucide アイコンに加えてカードでも使えるようになりました。`<Card>` の `icon` prop に任意のブランド名を指定してください。

</Update>

<Update label="2024-09-01" description="v0.1.0" tags={["Initial release"]}>

#### 初回リリース

Markdown、GFM、Zenn 構文、そしてコールアウト・カード・タブ・ステップ・コードグループを含むコア Mintlify コンポーネントに対応した Thinkstream の最初のリリースです。

</Update>
```
