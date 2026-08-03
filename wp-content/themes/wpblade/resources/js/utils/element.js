/**
 * 要素を表示状態にする
 * @param {HTMLElement} element - 表示させたい要素
 * @description element が存在しない場合は何もしない。存在する場合は hidden プロパティを false にして表示する。
 */
const showElement = (element) => element && (element.hidden = false);

/**
 * 指定した複数の要素を表示状態にする
 * @param {...HTMLElement} elements - 表示させたい要素（複数可）
 */
const showElements = (...elements) => elements.forEach((el) => showElement(el));

/**
 * 要素を非表示状態にする
 * @param {HTMLElement} element - 非表示にしたい要素
 * @description element が存在しない場合は何もしない。存在する場合は hidden プロパティを true にして非表示にする。
 */
const hideElement = (element) => element && (element.hidden = true);

/**
 * 指定した複数の要素を非表示状態にする
 * @param {...HTMLElement} elements - 非表示にしたい要素（複数可）
 */
const hideElements = (...elements) => elements.forEach((el) => hideElement(el));

/**
 * 要素を操作可能状態にする
 * @param {HTMLInputElement|HTMLButtonElement|HTMLElement} element - 有効化したいフォーム要素など
 * @description element が存在しない場合は何もしない。存在する場合は disabled プロパティを false にして操作可能にする。
 */
const enableElement = (element) => element && (element.disabled = false);

/**
 * 指定した複数の要素を操作可能状態にする
 * @param {...(HTMLInputElement|HTMLButtonElement|HTMLElement)} elements - 有効化したい要素（複数可）
 */
const enableElements = (...elements) =>
  elements.forEach((el) => enableElement(el));

/**
 * 要素を操作不可状態にする
 * @param {HTMLInputElement|HTMLButtonElement|HTMLElement} element - 無効化したいフォーム要素など
 * @description element が存在しない場合は何もしない。存在する場合は disabled プロパティを true にして操作不可にする。
 */
const disableElement = (element) => element && (element.disabled = true);

/**
 * 指定した複数の要素を操作不可状態にする
 * @param {...(HTMLInputElement|HTMLButtonElement|HTMLElement)} elements - 無効化したい要素（複数可）
 */
const disableElements = (...elements) =>
  elements.forEach((el) => disableElement(el));

/**
 * 要素の aria-disabled 属性を true に設定して、支援技術に「操作不可能」な要素として通知する
 * @param {HTMLElement} element - aria-disabled を付与したい要素
 * @description element が存在しない場合は何もしない。存在する場合は aria-disabled="true" を付与する。
 */
const ariaDisableElement = (element) =>
  element && element.setAttribute("aria-disabled", "true");

/**
 * 指定した複数の要素の aria-disabled 属性を true に設定する
 * @param {...HTMLElement} elements - aria-disabled を付与したい要素（複数可）
 */
const ariaDisableElements = (...elements) =>
  elements.forEach((el) => ariaDisableElement(el));

/**
 * 要素の aria-disabled 属性を false に設定して、支援技術に「操作可能」な要素として通知する
 * @param {HTMLElement} element - aria-disabled を解除したい要素
 * @description element が存在しない場合は何もしない。存在する場合は aria-disabled="false" を付与する。
 */
const ariaEnableElement = (element) =>
  element && element.setAttribute("aria-disabled", "false");

/**
 * 指定した複数の要素の aria-disabled 属性を false に設定する
 * @param {...HTMLElement} elements - aria-disabled を解除したい要素（複数可）
 */
const ariaEnableElements = (...elements) =>
  elements.forEach((el) => ariaEnableElement(el));

/**
 * 要素の aria-selected 属性を true に設定して、支援技術に「選択されている」要素として通知する
 * @param {HTMLElement} element - aria-selected を付与したい要素
 * @description element が存在しない場合は何もしない。存在する場合は aria-selected="true" を付与する。
 */
const ariaSelectElement = (element) =>
  element && element.setAttribute("aria-selected", "true");

/**
 * 指定した複数の要素の aria-selected 属性を false に設定する
 * @param {...HTMLElement} elements - aria-selected を解除したい要素（複数可）
 * @description element が存在しない場合は何もしない。存在する場合は aria-selected="false" を付与する。
 */
const ariaUnselectElements = (...elements) =>
  elements.forEach((el) => el && el.setAttribute("aria-selected", "false"));

/**
 * 要素の tabindex 属性を 0 に設定して、キーボードフォーカスを受け取れるようにする
 * @param {HTMLElement} element - tabindex を設定したい要素
 * @description element が存在しない場合は何もしない。存在する場合は tabindex="0" を設定する。
 */
const setTabindexFocusable = (element) =>
  element && element.setAttribute("tabindex", "0");

/**
 * 指定した複数の要素の tabindex 属性を 0 に設定する
 * @param {...HTMLElement} elements - tabindex を設定したい要素（複数可）
 */
const setTabindexFocusables = (...elements) =>
  elements.forEach((el) => setTabindexFocusable(el));

/**
 * 要素の tabindex 属性を -1 に設定して、キーボードフォーカスを受け取れないようにする
 * @param {HTMLElement} element - tabindex を設定したい要素
 * @description element が存在しない場合は何もしない。存在する場合は tabindex="-1" を設定する。
 */
const setTabindexUnfocusable = (element) =>
  element && element.setAttribute("tabindex", "-1");

/**
 * 指定した複数の要素の tabindex 属性を -1 に設定する
 * @param {...HTMLElement} elements - tabindex を設定したい要素（複数可）
 */
const setTabindexUnfocusables = (...elements) =>
  elements.forEach((el) => setTabindexUnfocusable(el));

/**
 * 要素にフォーカスを移動する
 * @param {HTMLElement} element - フォーカスを移動したい要素
 * @description element が存在しない場合は何もしない。存在する場合は focus() を呼び出す。
 */
const focusElement = (element) => element && element.focus();

export {
  showElement,
  showElements,
  hideElement,
  hideElements,
  enableElement,
  enableElements,
  disableElement,
  disableElements,
  ariaDisableElement,
  ariaDisableElements,
  ariaEnableElement,
  ariaEnableElements,
  ariaSelectElement,
  ariaUnselectElements,
  setTabindexFocusable,
  setTabindexFocusables,
  setTabindexUnfocusable,
  setTabindexUnfocusables,
  focusElement,
};
