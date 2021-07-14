// modules are defined as an array
// [ module function, map of requires ]
//
// map of requires is short require name -> numeric require
//
// anything defined in a previous bundle is accessed via the
// orig method which is the require for previous bundles
parcelRequire = (function (modules, cache, entry, globalName) {
  // Save the require from previous bundle to this closure if any
  var previousRequire = typeof parcelRequire === 'function' && parcelRequire;
  var nodeRequire = typeof require === 'function' && require;

  function newRequire(name, jumped) {
    if (!cache[name]) {
      if (!modules[name]) {
        // if we cannot find the module within our internal map or
        // cache jump to the current global require ie. the last bundle
        // that was added to the page.
        var currentRequire = typeof parcelRequire === 'function' && parcelRequire;
        if (!jumped && currentRequire) {
          return currentRequire(name, true);
        }

        // If there are other bundles on this page the require from the
        // previous one is saved to 'previousRequire'. Repeat this as
        // many times as there are bundles until the module is found or
        // we exhaust the require chain.
        if (previousRequire) {
          return previousRequire(name, true);
        }

        // Try the node require function if it exists.
        if (nodeRequire && typeof name === 'string') {
          return nodeRequire(name);
        }

        var err = new Error('Cannot find module \'' + name + '\'');
        err.code = 'MODULE_NOT_FOUND';
        throw err;
      }

      localRequire.resolve = resolve;
      localRequire.cache = {};

      var module = cache[name] = new newRequire.Module(name);

      modules[name][0].call(module.exports, localRequire, module, module.exports, this);
    }

    return cache[name].exports;

    function localRequire(x){
      return newRequire(localRequire.resolve(x));
    }

    function resolve(x){
      return modules[name][1][x] || x;
    }
  }

  function Module(moduleName) {
    this.id = moduleName;
    this.bundle = newRequire;
    this.exports = {};
  }

  newRequire.isParcelRequire = true;
  newRequire.Module = Module;
  newRequire.modules = modules;
  newRequire.cache = cache;
  newRequire.parent = previousRequire;
  newRequire.register = function (id, exports) {
    modules[id] = [function (require, module) {
      module.exports = exports;
    }, {}];
  };

  var error;
  for (var i = 0; i < entry.length; i++) {
    try {
      newRequire(entry[i]);
    } catch (e) {
      // Save first error but execute all entries
      if (!error) {
        error = e;
      }
    }
  }

  if (entry.length) {
    // Expose entry point to Node, AMD or browser globals
    // Based on https://github.com/ForbesLindesay/umd/blob/master/template.js
    var mainExports = newRequire(entry[entry.length - 1]);

    // CommonJS
    if (typeof exports === "object" && typeof module !== "undefined") {
      module.exports = mainExports;

    // RequireJS
    } else if (typeof define === "function" && define.amd) {
     define(function () {
       return mainExports;
     });

    // <script>
    } else if (globalName) {
      this[globalName] = mainExports;
    }
  }

  // Override the current require with this new one
  parcelRequire = newRequire;

  if (error) {
    // throw error from earlier, _after updating parcelRequire_
    throw error;
  }

  return newRequire;
})({"nIsC":[function(require,module,exports) {
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * Front-end Sitewide notices block class.
 *
 * @since 9.0.0
 */
var bpSitewideNoticeBlock = /*#__PURE__*/function () {
  function bpSitewideNoticeBlock(settings) {
    _classCallCheck(this, bpSitewideNoticeBlock);

    var path = settings.path,
        dismissPath = settings.dismissPath,
        root = settings.root,
        nonce = settings.nonce;
    this.path = path;
    this.dismissPath = dismissPath;
    this.root = root;
    this.nonce = nonce;
  }

  _createClass(bpSitewideNoticeBlock, [{
    key: "start",
    value: function start() {
      var _this = this;

      // Listen to each Block's dismiss button clicks
      document.querySelectorAll('.bp-sitewide-notice-block a.dismiss-notice').forEach(function (dismissButton) {
        dismissButton.addEventListener('click', function (event) {
          event.preventDefault();
          fetch(_this.root + _this.dismissPath, {
            method: 'POST',
            headers: {
              'X-WP-Nonce': _this.nonce
            }
          }).then(function (response) {
            return response.json();
          }).then(function (data) {
            if ('undefined' !== typeof data && 'undefined' !== typeof data.dismissed && data.dismissed) {
              document.querySelectorAll('.bp-sitewide-notice-block').forEach(function (elem) {
                elem.remove();
              });
            }
          });
        });
      });
    }
  }]);

  return bpSitewideNoticeBlock;
}(); // widget_bp_core_sitewide_messages buddypress widget wp-block-bp-sitewide-notices > bp-sitewide-notice > a.dismiss-notice


var settings = window.bpSitewideNoticeBlockSettings || {};
var bpSitewideNotice = new bpSitewideNoticeBlock(settings);

if ('loading' === document.readyState) {
  document.addEventListener('DOMContentLoaded', bpSitewideNotice.start());
} else {
  bpSitewideNotice.start();
}
},{}]},{},["nIsC"], null)