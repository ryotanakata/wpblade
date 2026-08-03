import { zodResolver } from "@hookform/resolvers/zod";
import { useMemo, useState } from "react";
import { useForm } from "react-hook-form";
import { CONTACT_VALIDATE_MESSAGES } from "@ts/constants/validateConstant";
import {
  type ContactSchema,
  createContactSchema,
} from "@ts/schemas/contactSchema";
import type { SubmitStatus } from "@ts/types/contactTypes";

const useContactFormHooks = () => {
  const [submitStatus, setSubmitStatus] = useState<SubmitStatus>("idle");

  const schema = useMemo(
    () => createContactSchema(CONTACT_VALIDATE_MESSAGES),
    []
  );

  const {
    control,
    handleSubmit,
    formState: { errors, isValid, isSubmitting },
  } = useForm<ContactSchema>({
    resolver: zodResolver(schema),
    mode: "onChange",
    defaultValues: {
      name: "",
      email: "",
      message: "",
      honeypot: false,
    },
  });

  const onSubmit = handleSubmit(async (data) => {
    const restUrl = window.wpblade?.restUrl;
    const nonce = window.wpblade?.nonce;

    if (!restUrl || !nonce) {
      setSubmitStatus("error");
      return;
    }

    try {
      const response = await fetch(`${restUrl}wpblade/v1/contact`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": nonce,
        },
        body: JSON.stringify(data),
      });

      if (!response.ok) {
        throw new Error("送信エラー");
      }

      setSubmitStatus("success");
    } catch {
      setSubmitStatus("error");
    }
  });

  return { control, onSubmit, errors, isValid, isSubmitting, submitStatus };
};

export { useContactFormHooks };
