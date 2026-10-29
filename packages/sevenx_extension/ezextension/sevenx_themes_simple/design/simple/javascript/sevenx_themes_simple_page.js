/*
 * Page script of the simple design, in place of inline code (so the pages run
 * under a script-src 'self' policy). Printed by pagelayout.tpl after the data
 * block #sevenx-themes-simple-page; it sets itself up once.
 *
 *   - the globals ezpRoot, ezpFilesRoot, ezpCurrentURL and ezpReturnURL, as the
 *     inline script set them, for scripts that read them;
 *   - a[data-exp-no-follow]: the menu links do not navigate while a page is
 *     being edited (they had onclick="return false;").
 */
(function () {
	'use strict';

	if (window.sevenxThemesSimplePageSetUp) return;
	window.sevenxThemesSimplePageSetUp = true;

	try {
		var block = document.getElementById('sevenx-themes-simple-page');
		var conf = block ? JSON.parse(block.textContent) : {};
		['ezpRoot', 'ezpFilesRoot', 'ezpCurrentURL', 'ezpReturnURL'].forEach(function (name) {
			if (Object.prototype.hasOwnProperty.call(conf, name)) window[name] = conf[name];
		});
	} catch (e) { /* the globals stay unset, the page works without them */ }

	document.addEventListener('click', function (e) {
		var el = e.target;
		while (el && el.nodeType === 1) {
			if (el.hasAttribute('data-exp-no-follow')) {
				e.preventDefault();
				return;
			}
			el = el.parentNode;
		}
	});
}());
