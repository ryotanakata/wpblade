---
paths:
  - "**/resources/ts/**"
---

# フロントエンド規約：React / TypeScript

`resources/ts/` 以下は React + TypeScript 専用。`resources/js/` の Vanilla JS とは分離して管理する。Vanilla JS バンドルとは完全排他で読み込む。ビルド設定・エイリアス（`@ts`）は [vite.md](vite.md)、Vanilla JS 側の設計は [javascript.md](javascript.md) を参照。

## ディレクトリ構成

```
resources/ts/
├── script.tsx             # エントリポイント（pages/ の関数を import して呼ぶ）
├── vite-env.d.ts          # CSS モジュール型定義（/// <reference types="vite/client" />）
├── types/
│   ├── globalTypes.ts     # window 拡張・グローバル型（declare global）
│   └── {名前}Types.ts     # 機能別の型定義（例: SubmitStatus = "idle" | "error"）
├── schemas/
│   └── {名前}Schema.ts    # Zod スキーマ（ファクトリ関数 + 型エクスポート）
├── constants/
│   └── {名前}Constant.ts  # TS 定数（バリデーションメッセージなど）
├── components/
│   └── ComponentName/
│       ├── index.tsx      # JSX のみ（ロジックは hooks.ts に集約）
│       ├── hooks.ts       # useComponentNameHooks()
│       └── style.module.scss
└── pages/
    └── {ページ名}Page.tsx # ページ関数（DOM 要素の有無でマウント判定して早期 return）
```

## コンポーネント規約

- `index.tsx` は JSX のみ。state・イベントハンドラは `hooks.ts` に逃がす
- `hooks.ts` は `useXxxHooks()` 関数をひとつ export。**引数なし・オブジェクト返し**を基本とする
- `index.tsx` が肥大化する場合は同フォルダ内にローカルコンポーネントとして分割する（ファイル名 = コンポーネント名）
- **named export のみ**（`default export` 禁止）

```tsx
// index.tsx
const ContactForm = () => {
  const { control, onSubmit, errors } = useContactFormHooks();
  return <form onSubmit={onSubmit}> ... </form>;
};

export { ContactForm };
```

## TypeScript 規約

- `any` 禁止 → `unknown` + 型絞り込みを使う
- `null` / `undefined` は必ず明示チェック（`strict: true` 有効）
- 型定義は `resources/ts/types/{名前}Types.ts`
- 定数（バリデーションメッセージなど）は `resources/ts/constants/{名前}Constant.ts` に置く
- コンポーネントの props 型は `type Props = ...` を作らず**インライン**で定義する
- コンポーネント内の状態型は文字列ユニオンで定義し、`types/` ファイルに書く

```typescript
// types/contactTypes.ts
type SubmitStatus = "idle" | "success" | "error";
```

```tsx
// OK: インライン
const Item = ({ label }: { label: string }) => <span>{label}</span>;

// NG: Props 型を別に定義
type Props = { label: string };
const Item = ({ label }: Props) => <span>{label}</span>;
```

## Zod スキーマ（`schemas/` に分離）

スキーマは `resources/ts/schemas/{名前}Schema.ts` に切り出す。メッセージ文字列をファクトリ関数の引数として受け取ることで、`hooks.ts` から呼び出すときに日本語テキストを注入できる。

```typescript
// schemas/contactSchema.ts
import { z } from "zod";

const REGEX_ALLOWED_NAME =
  /^[ぁ-んーァ-ヶ一-龠々a-zA-Zａ-ｚＡ-Ｚ0-9０-９ 　]+$/;
const REGEX_CONTAINS_JAPANESE = /[ぁ-んァ-ヶ一-龠々]/;

const createContactSchema = (messages: {
  required: string;
  invalidEmail: string;
  tooLongName: string;
  tooShortMessage: string;
  tooLongMessage: string;
  invalidCharacters: string;
  notJapanese: string;
}) =>
  z.object({
    name: z
      .string()
      .min(1, { message: messages.required })
      .max(100, { message: messages.tooLongName })
      .regex(REGEX_ALLOWED_NAME, { message: messages.invalidCharacters }),
    email: z
      .string()
      .min(1, { message: messages.required })
      .email(messages.invalidEmail),
    message: z
      .string()
      .min(10, { message: messages.tooShortMessage })
      .max(2000, { message: messages.tooLongMessage })
      .regex(REGEX_CONTAINS_JAPANESE, { message: messages.notJapanese }),
    honeypot: z.boolean(),
  });

type ContactSchema = z.infer<ReturnType<typeof createContactSchema>>;

export { createContactSchema, type ContactSchema };
```

スキーマ型: `type XxxSchema = z.infer<ReturnType<typeof createXxxSchema>>`

## React Hook Form + Zod

`schemas/` のファクトリ関数を import し、`useMemo` でキャッシュして生成する。各フィールドは `Controller` で管理する。

```typescript
// hooks.ts
import {
  createContactSchema,
  type ContactSchema,
} from "@ts/schemas/contactSchema";

const useContactFormHooks = () => {
  const schema = useMemo(
    () =>
      createContactSchema({
        required: "必須項目です",
        invalidEmail: "メールアドレスの形式が正しくありません",
        tooLongName: "100文字以内で入力してください",
        tooShortMessage: "10文字以上入力してください",
        tooLongMessage: "2000文字以内で入力してください",
        invalidCharacters: "使用できない文字が含まれています",
        notJapanese: "日本語でご記入ください",
      }),
    [],
  );
  const {
    control,
    handleSubmit,
    formState: { errors, isValid, isSubmitting },
  } = useForm<ContactSchema>({
    resolver: zodResolver(schema),
    mode: "onChange",
    defaultValues: { name: "", email: "", message: "", honeypot: false },
  });
  // ...
  return { control, onSubmit, errors, isValid, isSubmitting };
};
```

```tsx
// index.tsx（抜粋）
<form onSubmit={onSubmit} noValidate>
  <Controller
    name="name"
    control={control}
    render={({ field }) => (
      <input
        {...field}
        aria-invalid={!!errors.name}
        data-input-insight="input_contact_name"
      />
    )}
  />
</form>
```

## スパム対策：ハニーポット

隠しチェックボックスをフォームに忍ばせ、ボットが自動でチェックしたら PHP 側でサイレントに弾く。

```tsx
<Controller
  name="honeypot"
  control={control}
  render={({ field }) => (
    <div className={styles.honeypot} aria-hidden="true">
      <input
        type="checkbox"
        tabIndex={-1}
        autoComplete="off"
        checked={field.value}
        onChange={(e) => field.onChange(e.target.checked)}
      />
    </div>
  )}
/>
```

```scss
/* CSS Modules でハニーポットを非表示 */
.honeypot {
  display: none;
}
```

PHP 側でチェック済みなら wp_mail を送らずに 200 を返す（ボットに検知されたことを悟らせない）。

## CSS Modules + SCSS

各コンポーネントの先頭で `@use "base" as *;` をインポートする（Vite の `loadPaths` 設定により `resources/scss/base/` を参照）。SCSS の記法・mixin は [scss.md](scss.md) を参照。

```scss
// style.module.scss
@use "base" as *;

.form { ... }
.field {
  label { ... }
  input[aria-invalid="true"] { border-color: var(--wpb-color-danger); }
  @include hover { ... } // hover mixin（focus-visible も対応済み）
}
.honeypot { display: none; }
```

## `window.wpblade` の型

PHP から渡すデータは、`ViteAssets::enqueueReact()` が `wp_head` にインライン `<script>` で出力する `window.wpblade` 経由で参照する（`wp_localize_script` は使わない。後述「React バンドルの PHP 側ロード：`enqueueReact()`」）。型は `resources/ts/types/globalTypes.ts` に定義する。

```ts
// globalTypes.ts
declare global {
  interface Window {
    wpblade?: {
      restUrl: string;
      nonce: string;
    };
  }
}
export {};
```

## ページマウントのパターン

`resources/ts/pages/{ページ名}Page.tsx` にページ関数を定義し、`script.tsx` から import して呼ぶ。マウント先 DOM 要素の有無で判定して早期 return する（vanilla JS の pages/ と同じパターン）。

```tsx
// resources/ts/pages/contactPage.tsx
import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { ContactForm } from "@ts/components/ContactForm";

const contactPage = () => {
  const el = document.querySelector('[data-element="contact-form"]');
  if (!el) return;
  createRoot(el).render(
    <StrictMode>
      <ContactForm />
    </StrictMode>,
  );
};

export { contactPage };
```

```tsx
// resources/ts/script.tsx（エントリポイント）
import { contactPage } from "@ts/pages/contactPage";

contactPage();
```

## React バンドルの PHP 側ロード：`enqueueReact()`

`ViteAssets::enqueueReact()` は React ページ専用の enqueue メソッド。JS・CSS ともに **vanilla JS バンドルとは完全排他**で読み込む。

`enqueueReact()` が自動でやること:

1. `window.wpblade = { restUrl, nonce }` を `wp_head` に出力（React から REST API を叩くための認証情報）
2. グローバル CSS（`style.scss`）を `wp_enqueue_style` で登録
3. CSS Modules の CSS を `wp_enqueue_style` で登録
4. `script.tsx` バンドルを `type="module"` で `wp_enqueue_script` に登録
5. dev モードでは `@vite/client` + CSS エントリ + TS エントリを `<script type="module">` で直接出力

```php
// includes/functions/assets.php
add_action('wp_enqueue_scripts', function (): void {
    $vite = new ViteAssets();

    // React ページと vanilla JS ページは完全排他
    if (is_page(['contact'])) {
        $vite->enqueueReact(); // script.tsx バンドルのみ読み込む
        return;
    }

    $vite->enqueue(); // script.js バンドルのみ読み込む
});
```

新しい React ページを追加するたびに:

1. `resources/ts/pages/{ページ名}Page.tsx` を作成してページ関数を定義
2. `resources/ts/script.tsx` に import + 呼び出しを追加
3. `assets.php` の `is_page()` 条件に対象ページを追加

## useEffect の非同期パターン

```ts
useEffect(() => {
  let isMounted = true;
  (async () => {
    const data = await someService.fetch();
    if (!isMounted) return;
    setState(data);
  })();
  return () => {
    isMounted = false;
  };
}, [deps]);
```
