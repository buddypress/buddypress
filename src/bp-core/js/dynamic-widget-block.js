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
})({"eNhW":[function(require,module,exports) {
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * WordPress dependencies.
 */
var _wp = wp,
    addQueryArgs = _wp.url.addQueryArgs;
/**
 * External dependencies.
 */

var _lodash = lodash,
    template = _lodash.template; // Use the bp global.

window.bp = window.bp || {};
/**
 * Generic class to be used by Dynamic Widget Blocks.
 *
 * @since 9.0.0
 */

bp.dynamicWidgetBlock = /*#__PURE__*/function () {
  function bpDynamicWidgetBlock(settings, blocks) {
    var _this = this;

    _classCallCheck(this, bpDynamicWidgetBlock);

    var path = settings.path,
        root = settings.root,
        nonce = settings.nonce;
    this.path = path;
    this.root = root;
    this.nonce = nonce, this.blocks = blocks;
    this.blocks.forEach(function (block, i) {
      var _ref = block.query_args || 'active',
          type = _ref.type;

      var _ref2 = block.preloaded || [],
          body = _ref2.body;

      _this.blocks[i].items = {
        'active': [],
        'newest': [],
        'popular': [],
        'alphabetical': []
      };

      if (!_this.blocks[i].items[type].length && body && body.length) {
        _this.blocks[i].items[type] = body;
      }
    });
  }

  _createClass(bpDynamicWidgetBlock, [{
    key: "useTemplate",
    value: function useTemplate(tmpl) {
      var options = {
        evaluate: /<#([\s\S]+?)#>/g,
        interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
        escape: /\{\{([^\}]+?)\}\}(?!\})/g,
        variable: 'data'
      };
      return template(document.querySelector('#tmpl-' + tmpl).innerHTML, options);
    }
  }, {
    key: "loop",
    value: function loop() {// This method needs to be overriden.
    }
  }, {
    key: "getItems",
    value: function getItems() {
      var _this2 = this;

      var type = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'active';
      var blockIndex = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
      this.blocks[blockIndex].query_args.type = type;

      if (this.blocks[blockIndex].items[type].length) {
        this.loop(this.blocks[blockIndex].items[type], this.blocks[blockIndex].selector, type);
      } else {
        fetch(addQueryArgs(this.root + this.path, this.blocks[blockIndex].query_args), {
          method: 'GET',
          headers: {
            'X-WP-Nonce': this.nonce
          }
        }).then(function (response) {
          return response.json();
        }).then(function (data) {
          _this2.blocks[blockIndex].items[type] = data;

          _this2.loop(_this2.blocks[blockIndex].items[type], _this2.blocks[blockIndex].selector, type);
        });
      }
    }
  }]);

  return bpDynamicWidgetBlock;
}();
},{}]},{},["eNhW"], null)