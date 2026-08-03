import { z } from "zod";

const REGEX_ALLOWED_NAME =
  /^[ぁ-んーァ-ヶ一-龠々a-zA-Zａ-ｚＡ-Ｚ0-9０-９ 　]+$/;

const REGEX_ALLOWED_MESSAGE =
  /^[ぁ-んァ-ヶ一-龠々a-zA-Z0-9ａ-ｚＡ-Ｚ０-９ .,、。！？「」『』【】（）()[\]{}<>＜＞《》～ー\-_=+*/\\|:;"'@#$%^&\n\u{1F300}-\u{1F6FF}\u{1F900}-\u{1F9FF}\u{1F1E6}-\u{1F1FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}]+$/u;

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
      .regex(REGEX_ALLOWED_MESSAGE, { message: messages.invalidCharacters })
      .regex(REGEX_CONTAINS_JAPANESE, { message: messages.notJapanese }),
    honeypot: z.boolean(),
  });

type ContactSchema = z.infer<ReturnType<typeof createContactSchema>>;

export { createContactSchema, type ContactSchema };
