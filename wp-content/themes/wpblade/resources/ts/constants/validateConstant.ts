const CONTACT_VALIDATE_MESSAGES = {
  required: "必須項目です",
  invalidEmail: "メールアドレスの形式が正しくありません",
  tooLongName: "100文字以内で入力してください",
  tooShortMessage: "10文字以上入力してください",
  tooLongMessage: "2000文字以内で入力してください",
  invalidCharacters: "使用できない文字が含まれています",
  notJapanese: "日本語でご記入ください",
} as const;

export { CONTACT_VALIDATE_MESSAGES };
