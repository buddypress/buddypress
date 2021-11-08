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
})({"HPdN":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

/**
 * WordPress dependencies.
 */
const {
  blockEditor: {
    InspectorControls
  },
  components: {
    Disabled,
    PanelBody,
    TextControl
  },
  element: {
    Fragment,
    createElement
  },
  i18n: {
    __
  },
  serverSideRender: ServerSideRender
} = wp;

const editSitewideNoticesBlock = ({
  attributes,
  setAttributes
}) => {
  const {
    title
  } = attributes;
  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Settings', 'buddypress'),
    initialOpen: true
  }, createElement(TextControl, {
    label: __('Title', 'buddypress'),
    value: title,
    onChange: text => {
      setAttributes({
        title: text
      });
    }
  }))), createElement(Disabled, null, createElement(ServerSideRender, {
    block: "bp/sitewide-notices",
    attributes: attributes
  })));
};

var _default = editSitewideNoticesBlock;
exports.default = _default;
},{}],"lnNZ":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

/**
 * WordPress dependencies.
 */
const {
  blocks: {
    createBlock
  }
} = wp;
/**
 * Transforms Legacy Widget to Friends Block.
 *
 * @type {Object}
 */

const transforms = {
  from: [{
    type: 'block',
    blocks: ['core/legacy-widget'],
    isMatch: ({
      idBase,
      instance
    }) => {
      if (!(instance !== null && instance !== void 0 && instance.raw)) {
        return false;
      }

      return idBase === 'bp_messages_sitewide_notices_widget';
    },
    transform: ({
      instance
    }) => {
      return createBlock('bp/sitewide-notices', {
        title: instance.raw.title
      });
    }
  }]
};
var _default = transforms;
exports.default = _default;
},{}],"kd3Z":[function(require,module,exports) {
"use strict";

var _edit = _interopRequireDefault(require("./sitewide-notices/edit"));

var _transforms = _interopRequireDefault(require("./sitewide-notices/transforms"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * WordPress dependencies.
 */
const {
  blocks: {
    registerBlockType
  },
  i18n: {
    __
  }
} = wp;
/**
 * Internal dependencies.
 */

registerBlockType('bp/sitewide-notices', {
  title: __('Sitewide Notices', 'buddypress'),
  description: __('Display Sitewide Notices posted by the site administrator', 'buddypress'),
  icon: {
    background: '#fff',
    foreground: '#d84800',
    src: 'megaphone'
  },
  category: 'buddypress',
  attributes: {
    title: {
      type: 'string',
      default: ''
    }
  },
  edit: _edit.default,
  transforms: _transforms.default
});
},{"./sitewide-notices/edit":"HPdN","./sitewide-notices/transforms":"lnNZ"}]},{},["kd3Z"], null)