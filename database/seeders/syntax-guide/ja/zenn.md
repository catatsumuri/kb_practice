## Zenn 構文

> 参考: https://zenn.dev/zenn/articles/markdown-guide

Zenn では、通常の Markdown に加えていくつかの便利な画像記法がサポートされています。

---

## 基本的な画像

```md
![](/images/example.jpg)
```

![](/images/example.jpg)

---

## サイズ指定した画像

画像 URL の後に `=250x` を付けると、幅をピクセル単位で指定できます。

```md
![](/images/example.jpg =250x)
```

![](/images/example.jpg =250x)

---

## Alt テキスト

```md
![サンプル風景画像](/images/example.jpg =250x)
```

![サンプル風景画像](/images/example.jpg =250x)

---

## キャプション

次の行にイタリック体のテキストを置くと、キャプションのように表示されます。

```md
![](/images/example.jpg =250x)
*サンプル風景画像*
```

![](/images/example.jpg =250x)
*サンプル風景画像*

---

## リンク付き画像

```md
[![](/images/example.jpg =250x)](https://zenn.dev)
```

[![](/images/example.jpg =250x)](https://zenn.dev)

---

## メッセージ

`:::message` で囲むと、情報コールアウトを表示できます。

```md
:::message
ここにメッセージを書きます
:::
```

:::message
ここにメッセージを書きます
:::

警告には `:::message alert` を使います。

```md
:::message alert
ここに警告を書きます
:::
```

:::message alert
ここに警告を書きます
:::

このシステムでは、非標準の拡張として追加のバリエーションもサポートしています。Zenn の仕様には含まれていませんが、ここでは動作します。

```md
:::message note
中立的な注記。
:::

:::message tip
役立つヒント。
:::

:::message check
成功や確認事項。
:::
```

:::message note
中立的な注記。
:::

:::message tip
役立つヒント。
:::

:::message check
成功や確認事項。
:::

---

## 詳細（折りたたみ）

`:::details` の後にタイトルを続けて囲むと、折りたたみ可能なブロックを作成できます。内容は読者がクリックして開くまで非表示になります。

```md
:::details クリックして展開
このコンテンツはデフォルトで非表示です。
:::
```

:::details クリックして展開
このコンテンツはデフォルトで非表示です。
:::

段落・コード・リスト、さらには他のディレクティブなど、任意のブロックコンテンツを中に含めることができます。

```md
:::details コード例を表示
以下は非表示のコードです:

```ts
function add(a: number, b: number): number {
    return a + b;
}
` ``

リストもあります:

- 項目1
- 項目2
:::
```

:::details コード例を表示
以下は非表示のコードです:

```ts
function add(a: number, b: number): number {
    return a + b;
}
```

リストもあります:

- 項目1
- 項目2
:::

ディレクティブをネストするには、外側のブロックのコロンを増やします。

```md
::::details ネストしたコールアウトを含む詳細
:::message
この注記は折りたたみセクションの中にあります。
:::
::::
```

::::details ネストしたコールアウトを含む詳細
:::message
この注記は折りたたみセクションの中にあります。
:::
::::

---

## リンクカード

URL を単独の行に置くと、自動的にカードとして表示されます。

```md
https://zenn.dev
```

https://zenn.dev

アンダースコアを含む URL には `@[card](URL)` 形式を使います。

```md
@[card](https://zenn.dev/zenn/articles/markdown-guide)
```

@[card](https://zenn.dev/zenn/articles/markdown-guide)

YouTube の URL は自動的に動画プレーヤーとして埋め込まれます。

```md
https://www.youtube.com/watch?v=WRVsOCh907o
```

https://www.youtube.com/watch?v=WRVsOCh907o

---

## ファイル名付きコードブロック

言語名の後に `:filename` を追加すると、コードブロックの上にファイル名ラベルが表示されます。

`` ```php:index.php `` でファイル名を付ける:

```php:index.php
<?php

echo 'Hello, world!';
```

`` ```ts:src/utils.ts `` でパスを付ける:

```ts:src/utils.ts
export function greet(name: string): string {
    return `Hello, ${name}!`;
}
```

---

## Diff ハイライト

フェンスの先頭に `diff` を付け、続けて言語名を書くと差分ハイライトが有効になります。`+` で始まる行は緑、`-` で始まる行は赤で表示されます。

`` ```diff js `` で差分ハイライト:

```diff js
@@ -4,6 +4,5 @@
+    const foo = bar.baz([1, 2, 3]) + 1;
-    let foo = bar.baz([1, 2, 3]);
     return foo;
```

`` ```diff ts:src/utils.ts `` のように `diff` とファイル名を組み合わせることもできます:

```diff ts:src/utils.ts
@@ -1,5 +1,5 @@
-export function greet(name: string) {
+export function greet(name: string): string {
     return `Hello, ${name}!`;
 }
```

---

## GitHub 埋め込み

GitHub のファイル URL を単独の行に置くと、自動的にコードブロックとして埋め込まれます。

```md
https://github.com/zenn-dev/zenn-editor/blob/canary/lerna.json
```

https://github.com/zenn-dev/zenn-editor/blob/canary/lerna.json

`#L{start}-L{end}` で行範囲を指定すると、特定の部分だけを表示できます。

```md
https://github.com/zenn-dev/zenn-editor/blob/canary/lerna.json#L1-L3
```

https://github.com/zenn-dev/zenn-editor/blob/canary/lerna.json#L1-L3

`@[github](URL)` 形式も使用できます。

```md
@[github](https://github.com/zenn-dev/zenn-editor/blob/canary/lerna.json)
```

@[github](https://github.com/zenn-dev/zenn-editor/blob/canary/lerna.json)
