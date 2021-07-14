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
})({"Sw79":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

/**
 * WordPress dependencies.
 */
var _wp = wp,
    InspectorControls = _wp.blockEditor.InspectorControls,
    _wp$components = _wp.components,
    Disabled = _wp$components.Disabled,
    Notice = _wp$components.Notice,
    PanelBody = _wp$components.PanelBody,
    ToggleControl = _wp$components.ToggleControl,
    _wp$element = _wp.element,
    Fragment = _wp$element.Fragment,
    createElement = _wp$element.createElement,
    __ = _wp.i18n.__;
/**
 * BuddyPress dependencies.
 */

var _bp = bp,
    ServerSideRender = _bp.blockComponents.ServerSideRender,
    getCurrentWidgetsSidebar = _bp.blockData.getCurrentWidgetsSidebar;

var editPrimaryNavBlock = function editPrimaryNavBlock(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      clientId = _ref.clientId;
  var displayTitle = attributes.displayTitle;
  var currentSidebar = getCurrentWidgetsSidebar(clientId);
  var disabledSidebars = ['sidebar-buddypress-members', 'sidebar-buddypress-groups'];

  if (currentSidebar && currentSidebar.id && -1 !== disabledSidebars.indexOf(currentSidebar.id)) {
    return createElement(Notice, {
      status: "error",
      isDismissible: false
    }, createElement("p", null, __('The BuddyPress Primary Navigation block shouldn\'t be used into this widget area. Please remove it.', 'buddypress')));
  }

  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Settings', 'buddypress'),
    initialOpen: true
  }, createElement(ToggleControl, {
    label: __('Include navigation title', 'buddypress'),
    checked: !!displayTitle,
    onChange: function onChange() {
      setAttributes({
        displayTitle: !displayTitle
      });
    }
  }))), createElement(Disabled, null, createElement(ServerSideRender, {
    block: "bp/primary-nav",
    attributes: attributes
  })));
};

var _default = editPrimaryNavBlock;
exports.default = _default;
},{}],"uKqo":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

/**
 * WordPress dependencies.
 */
var _wp = wp,
    createBlock = _wp.blocks.createBlock;
/**
 * Transforms Legacy Widget to Primary Nav Block.
 *
 * @type {Object}
 */

var transforms = {
  from: [{
    type: 'block',
    blocks: ['core/legacy-widget'],
    isMatch: function isMatch(_ref) {
      var idBase = _ref.idBase,
          instance = _ref.instance;

      if (!(instance !== null && instance !== void 0 && instance.raw)) {
        return false;
      }

      return idBase === 'bp_nouveau_sidebar_object_nav_widget';
    },
    transform: function transform(_ref2) {
      var instance = _ref2.instance;
      return createBlock('bp/primary-nav', {
        displayTitle: instance.raw.bp_nouveau_widget_title
      });
    }
  }]
};
var _default = transforms;
exports.default = _default;
},{}],"ygAa":[function(require,module,exports) {
"use strict";

var _edit = _interopRequireDefault(require("./primary-nav/edit"));

var _transforms = _interopRequireDefault(require("./primary-nav/transforms"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * WordPress dependencies.
 */
var _wp = wp,
    registerBlockType = _wp.blocks.registerBlockType,
    __ = _wp.i18n.__;
/**
 * Internal dependencies.
 */

registerBlockType('bp/primary-nav', {
  title: __('Primary navigation', 'buddypress'),
  description: __('Displays BuddyPress primary nav in the sidebar of your site. Make sure to use it as the first widget of the sidebar and only once.', 'buddypress'),
  icon: {
    background: '#fff',
    foreground: '#d84800',
    src: 'buddicons-community'
  },
  category: 'buddypress',
  attributes: {
    displayTitle: {
      type: 'boolean',
      default: true
    }
  },
  edit: _edit.default,
  transforms: _transforms.default
});
},{"./primary-nav/edit":"Sw79","./primary-nav/transforms":"uKqo"}]},{},["ygAa"], null)