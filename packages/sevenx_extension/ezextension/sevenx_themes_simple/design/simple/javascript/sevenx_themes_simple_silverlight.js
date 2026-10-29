/*
 * The Silverlight player of content/datatype/view/ezmedia.tpl names these two
 * global handlers in its onError and onResize parameters. They do nothing, as
 * before, when they stood in an inline script.
 */
window.onErrorHandler = window.onErrorHandler || function (sender, args) {};
window.onResizeHandler = window.onResizeHandler || function (sender, args) {};
