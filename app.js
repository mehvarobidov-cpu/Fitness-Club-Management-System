/* Compatibility bridge: current FCMS config.php loads assets/app.js. */
(function () {
  'use strict';
  var current = document.currentScript;
  if (!current) return;
  var loader = document.createElement('script');
  loader.src = new URL('js/app.js', current.src).toString();
  loader.defer = true;
  current.insertAdjacentElement('afterend', loader);
})();
