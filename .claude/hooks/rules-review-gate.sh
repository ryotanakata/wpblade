#!/usr/bin/env bash
# 規約レビューゲート（AI レビューを git commit の前提条件にする仕組みの決定的な部分）。
#
#   --pass  : 現在の作業ツリー状態を「レビュー済み」として記録する（/wpblade-rules-review が全違反解消後に呼ぶ）
#   --check : PreToolUse hook（settings.json から登録）。Bash ツールのコマンドを stdin の JSON で受け取り、
#             git commit を含む場合にレビュー済みマーカーと作業ツリーの状態ハッシュを照合する。
#             未レビュー or レビュー後に変更があればブロック（exit 2。stderr が Claude に返る）。
#
# レビュー後に1文字でもファイルが変わればハッシュが一致しなくなるため、
# 「レビューした状態のままコミットする」ことだけが通る。ハッシュは作業ツリーの
# 内容そのもの（git write-tree）なので、コミットしても作業ツリーが変わらない限り
# 有効なまま＝レビュー済みの変更を意味のある単位に「分割コミット」できる。
set -u

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(git rev-parse --show-toplevel 2>/dev/null || pwd)}"
MARKER_FILE="$PROJECT_DIR/.claude/tmp/rules-review-pass"

# 作業ツリーの内容ハッシュ。一時 index に全ファイル（追跡 + 未追跡、.gitignore 除外）を
# ステージして tree oid を得る。実際の index には触れない。HEAD に依存しないため
# コミットで失効せず、ファイル内容の変更でのみ変わる。
# マーカー自身（.claude/tmp/）は、コピー先リポジトリの .gitignore 漏れで
# 自己失効しないよう明示的に除外する。
state_hash() {
  cd "$PROJECT_DIR" || return 1
  local tmp_index
  tmp_index=$(mktemp) || return 1
  (
    export GIT_INDEX_FILE="$tmp_index"
    git read-tree --empty
    git add -A 2>/dev/null
    git rm -r --cached -q --ignore-unmatch .claude/tmp 2>/dev/null
    git write-tree
  )
  local status=$?
  rm -f "$tmp_index"
  return $status
}

case "${1:-}" in
  --pass)
    mkdir -p "$(dirname "$MARKER_FILE")"
    state_hash > "$MARKER_FILE"
    echo "rules-review: 現在の変更をレビュー済みとして記録しました（$MARKER_FILE）"
    ;;

  --check)
    input="$(cat)"

    # stdin の hook JSON から実行コマンドを抜く。python3 が無い等の異常時は fail-open（コミットを妨げない）
    command=$(printf '%s' "$input" | python3 -c \
      'import json,sys; print(json.load(sys.stdin).get("tool_input",{}).get("command",""))' \
      2>/dev/null || true)
    [ -z "$command" ] && exit 0

    # git commit を含むコマンドだけ検査する
    printf '%s' "$command" | grep -qE '\bgit\b[^|;&]*\bcommit\b' || exit 0

    # 明示スキップ（人間の判断で省略する場合のみ。Claude が自発的に付けるのは禁止 → CLAUDE.md 参照）
    case "$command" in
      *SKIP_RULES_REVIEW=1*) exit 0 ;;
    esac

    if [ -f "$MARKER_FILE" ] && [ "$(cat "$MARKER_FILE")" = "$(state_hash)" ]; then
      exit 0
    fi

    {
      echo "コミットをブロックしました: 現在の変更は規約レビュー未実施か、前回レビューの後にファイルが変更されています。"
      echo "wpblade-rules-review スキルを実行し、違反を解消してから再度コミットしてください。"
      echo "レビューが通ると .claude/hooks/rules-review-gate.sh --pass がマーカーを記録し、コミットが通るようになります。"
    } >&2
    exit 2
    ;;

  *)
    echo "usage: rules-review-gate.sh --pass|--check" >&2
    exit 1
    ;;
esac
