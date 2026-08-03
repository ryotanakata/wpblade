/**
 * ページのスクロールをロックまたは解除する関数
 * `shouldLock` が true の場合、body　の　overflow　に　hidden　を設定し、スクロールをロック
 * `shouldLock` が false の場合、overflow を空文字に設定し、スクロールロックを解除
 *
 * @param {boolean} shouldLock - true の場合、スクロールをロックします。false の場合、スクロールロックを解除します。
 */
const toggleScrollLock = (shouldLock) => {
  document.body.style.overflow = shouldLock ? "hidden" : "";
};

export { toggleScrollLock };
