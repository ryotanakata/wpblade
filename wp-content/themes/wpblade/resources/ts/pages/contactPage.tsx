import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { ContactForm } from "@ts/components/ContactForm";

const contactPage = () => {
  const el = document.querySelector('[data-element="contact-form"]');
  if (!el) return;

  createRoot(el).render(
    <StrictMode>
      <ContactForm />
    </StrictMode>
  );
};

export { contactPage };
