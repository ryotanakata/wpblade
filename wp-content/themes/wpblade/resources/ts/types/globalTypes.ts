declare global {
  interface Window {
    wpblade?: {
      restUrl: string;
      nonce: string;
    };
  }
}

export {};
