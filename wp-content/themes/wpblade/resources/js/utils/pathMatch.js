/**
 * パスの末尾のスラッシュを削除して返す（ただし、ルートパスの場合はスラッシュを削除しない）
 * @param {string} path - スラッシュを削除するパス
 * @returns {string} - 末尾のスラッシュが削除されたパス
 */
const removeTrailSlash = (path) =>
  path === "/" ? path : path.replace(/\/$/, "");

/**
 * 現在のウィンドウの場所が指定された静的パスと一致するかを確認
 * @param {string} path - 現在のウィンドウの場所と比較する静的パス
 * @returns {boolean} - 現在のウィンドウの場所が指定された静的パスと一致する場合はtrue、そうでない場合はfalseを返却
 */
const isCurrentStaticPath = (path) =>
  removeTrailSlash(window.location.pathname) === removeTrailSlash(path);

/**
 * 現在のウィンドウの場所が指定された静的パスと一致しないかを確認
 * @param {string} path - 現在のウィンドウの場所と比較する静的パス
 * @returns {boolean} - 現在のウィンドウの場所が指定された静的パスと一致しない場合はtrue、そうでない場合はfalseを返却
 */
const isNotCurrentStaticPath = (path) =>
  removeTrailSlash(window.location.pathname) !== removeTrailSlash(path);

/**
 * 現在のウィンドウの場所が指定された動的パスパターンと一致するかを確認
 * @param {RegExp} regex - 現在のウィンドウの場所と比較する正規表現パターン
 * @returns {boolean} - 現在のウィンドウの場所が指定された動的パスパターンと一致する場合はtrue、そうでない場合はfalseを返却
 */
const isCurrentDynamicPath = (regex) =>
  regex.test(removeTrailSlash(window.location.pathname));

/**
 * 現在のウィンドウの場所が指定された動的パスパターンと一致しないかを確認
 * @param {RegExp} regex - 現在のウィンドウの場所と比較する正規表現パターン
 * @returns {boolean} - 現在のウィンドウの場所が指定された動的パスパターンと一致しない場合はtrue、そうでない場合はfalseを返却
 */
const isNotCurrentDynamicPath = (regex) =>
  !regex.test(removeTrailSlash(window.location.pathname));

/**
 * 指定されたパスとクエリパラメータが現在のURLと一致するかを確認
 * @param {Object} config - { pathname: string, search: Object }
 * @returns {boolean}
 */
const isCurrentUrlPattern = ({ pathname, search }) => {
  const isPathMatch = isCurrentStaticPath(pathname);
  const currentSearchParams = new URLSearchParams(window.location.search);

  const isSearchParamsMatch = Object.entries(search).every(
    ([key, value]) => currentSearchParams.get(key) === value
  );

  return isPathMatch && isSearchParamsMatch;
};

export {
  isCurrentStaticPath,
  isNotCurrentStaticPath,
  isCurrentDynamicPath,
  isNotCurrentDynamicPath,
  isCurrentUrlPattern,
};
