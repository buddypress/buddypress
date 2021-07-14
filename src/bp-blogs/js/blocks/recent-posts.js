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
})({"Pfcj":[function(require,module,exports) {
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
    PanelBody = _wp$components.PanelBody,
    RangeControl = _wp$components.RangeControl,
    TextControl = _wp$components.TextControl,
    ToggleControl = _wp$components.ToggleControl,
    _wp$element = _wp.element,
    Fragment = _wp$element.Fragment,
    createElement = _wp$element.createElement,
    __ = _wp.i18n.__;
/**
 * BuddyPress dependencies.
 */

var _bp = bp,
    ServerSideRender = _bp.blockComponents.ServerSideRender;

var editRecentPostsBlock = function editRecentPostsBlock(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var title = attributes.title,
      maxPosts = attributes.maxPosts,
      linkTitle = attributes.linkTitle;
  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Settings', 'buddypress'),
    initialOpen: true
  }, createElement(TextControl, {
    label: __('Title', 'buddypress'),
    value: title,
    onChange: function onChange(text) {
      setAttributes({
        title: text
      });
    }
  }), createElement(RangeControl, {
    label: __('Max posts to show', 'buddypress'),
    value: maxPosts,
    onChange: function onChange(value) {
      return setAttributes({
        maxPosts: value
      });
    },
    min: 1,
    max: 10,
    required: true
  }), createElement(ToggleControl, {
    label: __('Link block title to Blogs directory', 'buddypress'),
    checked: !!linkTitle,
    onChange: function onChange() {
      setAttributes({
        linkTitle: !linkTitle
      });
    }
  }))), createElement(Disabled, null, createElement(ServerSideRender, {
    block: "bp/recent-posts",
    attributes: attributes
  })));
};

var _default = editRecentPostsBlock;
exports.default = _default;
},{}],"D8sC":[function(require,module,exports) {
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
 * Transforms Legacy Widget to Recent Posts Block.
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

      return idBase === 'bp_blogs_recent_posts_widget';
    },
    transform: function transform(_ref2) {
      var instance = _ref2.instance;
      return createBlock('bp/recent-posts', {
        title: instance.raw.title,
        maxPosts: instance.raw.max_posts,
        linkTitle: instance.raw.link_title
      });
    }
  }]
};
var _default = transforms;
exports.default = _default;
},{}],"PMBS":[function(require,module,exports) {
"use strict";

var _edit = _interopRequireDefault(require("./recent-posts/edit"));

var _transforms = _interopRequireDefault(require("./recent-posts/transforms"));

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

registerBlockType('bp/recent-posts', {
  title: __('Recent Networkwide Posts', 'buddypress'),
  description: __('A list of recently published posts from across your network.', 'buddypress'),
  icon: {
    background: '#fff',
    foreground: '#d84800',
    src: 'wordpress'
  },
  category: 'buddypress',
  attributes: {
    title: {
      type: 'string',
      default: __('Recent Networkwide Posts', 'buddypress')
    },
    maxPosts: {
      type: 'number',
      default: 10
    },
    linkTitle: {
      type: 'boolean',
      default: false
    }
  },
  edit: _edit.default,
  transforms: _transforms.default
});
},{"./recent-posts/edit":"Pfcj","./recent-posts/transforms":"D8sC"}]},{},["PMBS"], null)