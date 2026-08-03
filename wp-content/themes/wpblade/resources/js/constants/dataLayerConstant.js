/**
 * dataLayer トラッキング用の data-* 属性名
 */
const DATA_LAYER_INSIGHT_ATTRIBUTES = Object.freeze({
  CLICK: "data-click-insight",
  INPUT: "data-input-insight",
  SHOW: "data-show-insight",
  PARAM: "data-param-insight",
});

/**
 * 送信を許可するイベント名のプレフィックス
 * ここに含まれるプレフィックスで始まるイベントのみ base/dataLayerInsight.js の中で pushToDataLayer によって送信する
 */
const ALLOW_EVENT_PREFIXES = Object.freeze(["click_", "input_", "show_"]);

export { DATA_LAYER_INSIGHT_ATTRIBUTES, ALLOW_EVENT_PREFIXES };
