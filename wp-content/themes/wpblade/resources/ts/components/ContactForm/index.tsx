import { Controller } from "react-hook-form";
import { useContactFormHooks } from "./hooks";
import styles from "./style.module.scss";

const ContactForm = () => {
  const { control, onSubmit, errors, isValid, isSubmitting, submitStatus } =
    useContactFormHooks();

  if (submitStatus === "success") {
    return (
      <div className={styles.success} role="status">
        <p>お問い合わせを受け付けました。</p>
        <p>内容を確認のうえ、担当者よりご連絡いたします。</p>
      </div>
    );
  }

  return (
    <form onSubmit={onSubmit} noValidate className={styles.form}>
      <div className={styles.field}>
        <label>
          <span className={styles.labelText}>
            お名前 <span aria-hidden="true">*</span>
          </span>
          <Controller
            name="name"
            control={control}
            render={({ field }) => (
              <input
                {...field}
                type="text"
                autoComplete="name"
                required
                aria-required="true"
                aria-invalid={!!errors.name}
                aria-describedby={
                  errors.name ? "contact-name-error" : undefined
                }
                data-input-insight="input_contact_name"
              />
            )}
          />
        </label>
        {errors.name && (
          <p id="contact-name-error" className={styles.error} role="alert">
            {errors.name.message}
          </p>
        )}
      </div>

      <div className={styles.field}>
        <label>
          <span className={styles.labelText}>
            メールアドレス <span aria-hidden="true">*</span>
          </span>
          <Controller
            name="email"
            control={control}
            render={({ field }) => (
              <input
                {...field}
                type="email"
                autoComplete="email"
                required
                aria-required="true"
                aria-invalid={!!errors.email}
                aria-describedby={
                  errors.email ? "contact-email-error" : undefined
                }
                data-input-insight="input_contact_email"
              />
            )}
          />
        </label>
        {errors.email && (
          <p id="contact-email-error" className={styles.error} role="alert">
            {errors.email.message}
          </p>
        )}
      </div>

      <div className={styles.field}>
        <label>
          <span className={styles.labelText}>
            お問い合わせ内容 <span aria-hidden="true">*</span>
          </span>
          <Controller
            name="message"
            control={control}
            render={({ field }) => (
              <textarea
                {...field}
                rows={6}
                required
                aria-required="true"
                aria-invalid={!!errors.message}
                aria-describedby={
                  errors.message ? "contact-message-error" : undefined
                }
                data-input-insight="input_contact_message"
              />
            )}
          />
        </label>
        {errors.message && (
          <p id="contact-message-error" className={styles.error} role="alert">
            {errors.message.message}
          </p>
        )}
      </div>

      {/* ハニーポット：ボットには見えるが人間には非表示 */}
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

      {submitStatus === "error" && (
        <p className={styles.submitError} role="alert">
          送信に失敗しました。しばらくしてから再度お試しください。
        </p>
      )}

      <button
        type="submit"
        disabled={!isValid || isSubmitting}
        aria-disabled={!isValid || isSubmitting}
        className={styles.submit}
        data-click-insight="click_contact_submit"
      >
        {isSubmitting ? "送信中..." : "送信する"}
      </button>
    </form>
  );
};

export { ContactForm };
