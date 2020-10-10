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
})({"dEOc":[function(require,module,exports) {
function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

module.exports = _arrayWithHoles;
},{}],"RonT":[function(require,module,exports) {
function _iterableToArrayLimit(arr, i) {
  if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return;
  var _arr = [];
  var _n = true;
  var _d = false;
  var _e = undefined;

  try {
    for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) {
      _arr.push(_s.value);

      if (i && _arr.length === i) break;
    }
  } catch (err) {
    _d = true;
    _e = err;
  } finally {
    try {
      if (!_n && _i["return"] != null) _i["return"]();
    } finally {
      if (_d) throw _e;
    }
  }

  return _arr;
}

module.exports = _iterableToArrayLimit;
},{}],"LGpM":[function(require,module,exports) {
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) {
    arr2[i] = arr[i];
  }

  return arr2;
}

module.exports = _arrayLikeToArray;
},{}],"Vzqv":[function(require,module,exports) {
var arrayLikeToArray = require("./arrayLikeToArray");

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(n);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return arrayLikeToArray(o, minLen);
}

module.exports = _unsupportedIterableToArray;
},{"./arrayLikeToArray":"LGpM"}],"sa4T":[function(require,module,exports) {
function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableRest;
},{}],"xkYc":[function(require,module,exports) {
var arrayWithHoles = require("./arrayWithHoles");

var iterableToArrayLimit = require("./iterableToArrayLimit");

var unsupportedIterableToArray = require("./unsupportedIterableToArray");

var nonIterableRest = require("./nonIterableRest");

function _slicedToArray(arr, i) {
  return arrayWithHoles(arr) || iterableToArrayLimit(arr, i) || unsupportedIterableToArray(arr, i) || nonIterableRest();
}

module.exports = _slicedToArray;
},{"./arrayWithHoles":"dEOc","./iterableToArrayLimit":"RonT","./unsupportedIterableToArray":"Vzqv","./nonIterableRest":"sa4T"}],"Sjre":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * WordPress dependencies.
 */
var _wp = wp,
    _wp$element = _wp.element,
    createElement = _wp$element.createElement,
    Fragment = _wp$element.Fragment,
    useState = _wp$element.useState,
    __ = _wp.i18n.__,
    _wp$components = _wp.components,
    Placeholder = _wp$components.Placeholder,
    SandBox = _wp$components.SandBox,
    Button = _wp$components.Button,
    ExternalLink = _wp$components.ExternalLink,
    Spinner = _wp$components.Spinner,
    Toolbar = _wp$components.Toolbar,
    ToolbarButton = _wp$components.ToolbarButton,
    compose = _wp.compose.compose,
    withSelect = _wp.data.withSelect,
    _wp$blockEditor = _wp.blockEditor,
    RichText = _wp$blockEditor.RichText,
    BlockControls = _wp$blockEditor.BlockControls;

var EditEmbedActivity = function EditEmbedActivity(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      isSelected = _ref.isSelected,
      bpSettings = _ref.bpSettings,
      preview = _ref.preview,
      fetching = _ref.fetching;
  var url = attributes.url,
      caption = attributes.caption;
  var embedScriptURL = bpSettings.embedScriptURL;

  var label = __('BuddyPress Activity URL', 'buddypress');

  var _useState = useState(url),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      value = _useState2[0],
      setURL = _useState2[1];

  var _useState3 = useState(!url),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      isEditingURL = _useState4[0],
      setIsEditingURL = _useState4[1];

  var onSubmit = function onSubmit(event) {
    if (event) {
      event.preventDefault();
    }

    setIsEditingURL(false);
    setAttributes({
      url: value
    });
  };

  var switchBackToURLInput = function switchBackToURLInput(event) {
    if (event) {
      event.preventDefault();
    }

    setIsEditingURL(true);
  };

  var editToolbar = createElement(BlockControls, null, createElement(Toolbar, null, createElement(ToolbarButton, {
    icon: "edit",
    title: __('Edit URL', 'buddypress'),
    onClick: switchBackToURLInput
  })));

  if (isEditingURL) {
    return createElement(Placeholder, {
      icon: "buddicons-activity",
      label: label,
      className: "wp-block-embed",
      instructions: __('Paste the link to the activity content you want to display on your site.', 'buddypress')
    }, createElement("form", {
      onSubmit: onSubmit
    }, createElement("input", {
      type: "url",
      value: value || '',
      className: "components-placeholder__input",
      "aria-label": label,
      placeholder: __('Enter URL to embed here…', 'buddypress'),
      onChange: function onChange(event) {
        return setURL(event.target.value);
      }
    }), createElement(Button, {
      isPrimary: true,
      type: "submit"
    }, __('Embed', 'buddypress'))), createElement("div", {
      className: "components-placeholder__learn-more"
    }, createElement(ExternalLink, {
      href: __('https://codex.buddypress.org/activity-embeds/')
    }, __('Learn more about activity embeds', 'buddypress'))));
  }

  if (fetching) {
    return createElement("div", {
      className: "wp-block-embed is-loading"
    }, createElement(Spinner, null), createElement("p", null, __('Embedding…', 'buddypress')));
  }

  if (!preview || !preview['x_buddypress'] || 'activity' !== preview['x_buddypress']) {
    // Reset the URL.
    setAttributes({
      url: ''
    });
    return createElement(Fragment, null, editToolbar, createElement(Placeholder, {
      icon: "buddicons-activity",
      label: label
    }, createElement("p", {
      className: "components-placeholder__error"
    }, __('The URL you provided is not a permalink to a BuddyPress Activity. Please use another URL.', 'buddypress'))));
  }

  return createElement(Fragment, null, !isEditingURL && editToolbar, createElement("figure", {
    className: "wp-block-embed is-type-bp-activity"
  }, createElement("div", {
    className: "wp-block-embed__wrapper"
  }, createElement(SandBox, {
    html: preview && preview.html ? preview.html : '',
    scripts: [embedScriptURL]
  })), (!RichText.isEmpty(caption) || isSelected) && createElement(RichText, {
    tagName: "figcaption",
    placeholder: __('Write caption…', 'buddypress'),
    value: caption,
    onChange: function onChange(value) {
      return setAttributes({
        caption: value
      });
    },
    inlineToolbar: true
  })));
};

var editEmbedActivityBlock = compose([withSelect(function (select, ownProps) {
  var url = ownProps.attributes.url;
  var editorSettings = select('core/editor').getEditorSettings();

  var _select = select('core'),
      getEmbedPreview = _select.getEmbedPreview,
      isRequestingEmbedPreview = _select.isRequestingEmbedPreview;

  var preview = undefined !== url && getEmbedPreview(url);
  var fetching = undefined !== url && isRequestingEmbedPreview(url);
  return {
    bpSettings: editorSettings.bp.activity || {},
    preview: preview,
    fetching: fetching
  };
})])(EditEmbedActivity);
var _default = editEmbedActivityBlock;
exports.default = _default;
},{"@babel/runtime/helpers/slicedToArray":"xkYc"}],"zmBI":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

/**
 * WordPress dependencies.
 */
var _wp = wp,
    RichText = _wp.blockEditor.RichText,
    createElement = _wp.element.createElement;

var saveEmbedActivityBlock = function saveEmbedActivityBlock(_ref) {
  var attributes = _ref.attributes;
  var url = attributes.url,
      caption = attributes.caption;

  if (!url) {
    return null;
  }

  return createElement("figure", {
    className: "wp-block-embed is-type-bp-activity"
  }, createElement("div", {
    className: "wp-block-embed__wrapper"
  }, "\n".concat(url, "\n")
  /* URL needs to be on its own line. */
  ), !RichText.isEmpty(caption) && createElement(RichText.Content, {
    tagName: "figcaption",
    value: caption
  }));
};

var _default = saveEmbedActivityBlock;
exports.default = _default;
},{}],"hBDw":[function(require,module,exports) {
"use strict";

var _edit = _interopRequireDefault(require("./embed-activity/edit"));

var _save = _interopRequireDefault(require("./embed-activity/save"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * WordPress dependencies.
 */
var _wp = wp,
    __ = _wp.i18n.__,
    registerBlockType = _wp.blocks.registerBlockType;
/**
 * Internal dependencies.
 */

registerBlockType('bp/embed-activity', {
  title: __('Embed an activity', 'buddypress'),
  description: __('Add a block that displays the activity content pulled from this or other community sites.', 'buddypress'),
  icon: 'buddicons-activity',
  category: 'buddypress',
  attributes: {
    url: {
      type: 'string'
    },
    caption: {
      type: 'string',
      source: 'html',
      selector: 'figcaption'
    }
  },
  supports: {
    align: true
  },
  edit: _edit.default,
  save: _save.default
});
},{"./embed-activity/edit":"Sjre","./embed-activity/save":"zmBI"}]},{},["hBDw"], null)