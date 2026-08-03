import {
  ALLOW_EVENT_PREFIXES,
  DATA_LAYER_INSIGHT_ATTRIBUTES,
} from "@js/constants/dataLayerConstant";

/** スロットル間隔（ms）- 同一イベントはこの間隔に1回まで push */
const THROTTLE_MS = 100;
const lastPushTimes = {};

/**
 * window.dataLayer にイベントを push する
 * @param {Object} payload - dataLayer に載せる値
 * @param {boolean} [enableThrottle=true] - 同一イベントのスロットルを有効化するか
 * @returns {void}
 */
const pushToDataLayer = (payload, enableThrottle = true) => {
  window.dataLayer = window.dataLayer || [];

  if (payload.event && enableThrottle) {
    const now = Date.now();
    const last = lastPushTimes[payload.event];
    if (last != null && now - last < THROTTLE_MS) return;
    lastPushTimes[payload.event] = now;
  }

  window.dataLayer.push(payload);
};

/**
 * クリックイベントをトラッキングして dataLayer に push する
 * 対象要素: [data-click-insight] 属性を持つ要素
 * イベント名: data-click-insight 属性の値
 * パラメータ: data-param-insight 属性の値（任意）
 */
const trackingDataClick = () => {
  const pushDataLayer = (e) => {
    const el = e.target.closest(`[${DATA_LAYER_INSIGHT_ATTRIBUTES.CLICK}]`);
    if (!el) return;

    const eventName = el.getAttribute(DATA_LAYER_INSIGHT_ATTRIBUTES.CLICK);
    if (!eventName) return;

    const hasAllowedPrefix = ALLOW_EVENT_PREFIXES.some((prefix) =>
      eventName.startsWith(prefix)
    );
    if (!hasAllowedPrefix) return;

    pushToDataLayer({
      event: eventName,
      n_param: el.getAttribute(DATA_LAYER_INSIGHT_ATTRIBUTES.PARAM) || "",
      n_el_type: el.tagName.toLowerCase(),
      n_el_class: el.getAttribute("class") || "",
    });
  };

  document.addEventListener("click", pushDataLayer);
};

/**
 * 入力イベントをトラッキングして dataLayer に push する
 * 対象要素: [data-input-insight] 属性を持つ input, textarea, select 要素
 * イベント名: data-input-insight 属性の値
 * パラメータ: data-param-insight 属性の値（任意）
 * 注意点:
 * - テキスト入力は focusout 時、ラジオ/チェックボックス/セレクトは change 時にイベントを送信
 * - 入力内容が有効であること、テキスト入力の場合は空でないことを条件にイベントを送信
 */
const trackingDataInput = () => {
  const pushDataLayer = (e) => {
    const el = e.target.closest(`[${DATA_LAYER_INSIGHT_ATTRIBUTES.INPUT}]`);
    if (!el) return;

    const isInput =
      el.tagName === "INPUT" && el.type !== "radio" && el.type !== "checkbox";
    const isTextarea = el.tagName === "TEXTAREA";
    const isRadio = el.tagName === "INPUT" && el.type === "radio";
    const isCheckbox = el.tagName === "INPUT" && el.type === "checkbox";
    const isSelect = el.tagName === "SELECT";
    const isFocusout = e.type === "focusout";
    const isChange = e.type === "change";

    if ((isInput || isTextarea) && !isFocusout) return;
    if ((isRadio || isCheckbox || isSelect) && !isChange) return;

    const eventName = el.getAttribute(DATA_LAYER_INSIGHT_ATTRIBUTES.INPUT);
    if (!eventName) return;

    const isValid = el.checkValidity();
    const isTextInput =
      isTextarea ||
      ["text", "email", "number", "password", "search"].includes(el.type);
    const hasValue = isTextInput ? el.value?.trim() : true;

    const hasAllowedPrefix = ALLOW_EVENT_PREFIXES.some((prefix) =>
      eventName.startsWith(prefix)
    );
    if (!isValid || !hasValue || !hasAllowedPrefix) return;

    pushToDataLayer({
      event: eventName,
      n_param: el.getAttribute(DATA_LAYER_INSIGHT_ATTRIBUTES.PARAM) || "",
      n_el_type: el.type || "",
      n_el_name: el.name || "",
      n_el_class: el.getAttribute("class") || "",
    });
  };

  document.addEventListener("focusout", pushDataLayer);
  document.addEventListener("change", pushDataLayer);
};

/**
 * 表示イベントをトラッキングして dataLayer に push する
 * 対象要素: [data-show-insight] 属性を持つ要素
 * イベント名: data-show-insight 属性の値
 * パラメータ: data-param-insight 属性の値（任意）
 */
const trackingDataShow = () => {
  const config = {
    showObserver: { threshold: 0, rootMargin: "-10% -10%" },
    mutationObserver: { childList: true, subtree: true },
  };

  const pushDataLayer = (entries, observer) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;

      const el = entry.target;
      const eventName = el.getAttribute(DATA_LAYER_INSIGHT_ATTRIBUTES.SHOW);
      if (!eventName) return;

      const hasAllowedPrefix = ALLOW_EVENT_PREFIXES.some((prefix) =>
        eventName.startsWith(prefix)
      );
      if (!hasAllowedPrefix) return;

      pushToDataLayer(
        {
          event: eventName,
          n_param: el.getAttribute(DATA_LAYER_INSIGHT_ATTRIBUTES.PARAM) || "",
          n_el_type: el.tagName.toLowerCase(),
          n_el_class: el.getAttribute("class") || "",
        },
        false // 同一イベントの一括送信を許容
      );
      observer.unobserve(el);
    });
  };

  const showObserver = new IntersectionObserver(
    pushDataLayer,
    config.showObserver
  );

  const observeInsightIn = (root) => {
    if (
      root.hasAttribute &&
      root.hasAttribute(DATA_LAYER_INSIGHT_ATTRIBUTES.SHOW)
    )
      showObserver.observe(root);
    root
      .querySelectorAll(`[${DATA_LAYER_INSIGHT_ATTRIBUTES.SHOW}]`)
      .forEach((el) => showObserver.observe(el));
  };

  const addObserveTargets = (mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.addedNodes.length === 0) return;
      mutation.addedNodes.forEach((el) => {
        if (el.nodeType !== 1) return;
        observeInsightIn(el);
      });
    });
  };

  const mutationObserver = new MutationObserver(addObserveTargets);
  observeInsightIn(document.body);
  mutationObserver.observe(document.body, config.mutationObserver);
};

export { trackingDataClick, trackingDataInput, trackingDataShow };
