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
})({"atl5":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.GROUP_STATI = exports.AVATAR_SIZES = void 0;

/**
 * WordPress dependencies.
 */
var _wp = wp,
    __ = _wp.i18n.__;
/**
 * Avatar sizes.
 *
 * @type {Array}
 */

var AVATAR_SIZES = [{
  label: __('None', 'buddypress'),
  value: 'none'
}, {
  label: __('Thumb', 'buddypress'),
  value: 'thumb'
}, {
  label: __('Full', 'buddypress'),
  value: 'full'
}];
/**
 * Group stati.
 *
 * @type {Object}
 */

exports.AVATAR_SIZES = AVATAR_SIZES;
var GROUP_STATI = {
  public: __('Public', 'buddypress'),
  private: __('Private', 'buddypress'),
  hidden: __('Hidden', 'buddypress')
};
exports.GROUP_STATI = GROUP_STATI;
},{}],"cCC3":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _constants = require("./constants");

/**
 * WordPress dependencies.
 */
var _wp = wp,
    _wp$blockEditor = _wp.blockEditor,
    InspectorControls = _wp$blockEditor.InspectorControls,
    BlockControls = _wp$blockEditor.BlockControls,
    _wp$components = _wp.components,
    Placeholder = _wp$components.Placeholder,
    Disabled = _wp$components.Disabled,
    PanelBody = _wp$components.PanelBody,
    SelectControl = _wp$components.SelectControl,
    ToggleControl = _wp$components.ToggleControl,
    Toolbar = _wp$components.Toolbar,
    ToolbarButton = _wp$components.ToolbarButton,
    _wp$element = _wp.element,
    Fragment = _wp$element.Fragment,
    createElement = _wp$element.createElement,
    __ = _wp.i18n.__;
/**
 * BuddyPress dependencies.
 */

var _bp = bp,
    _bp$blockComponents = _bp.blockComponents,
    AutoCompleter = _bp$blockComponents.AutoCompleter,
    ServerSideRender = _bp$blockComponents.ServerSideRender,
    isActive = _bp.blockData.isActive;
/**
 * Internal dependencies.
 */

var getSlugValue = function getSlugValue(item) {
  if (item && item.status && _constants.GROUP_STATI[item.status]) {
    return _constants.GROUP_STATI[item.status];
  }

  return null;
};

var editGroupBlock = function editGroupBlock(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var isAvatarEnabled = isActive('groups', 'avatar');
  var isCoverImageEnabled = isActive('groups', 'cover');
  var avatarSize = attributes.avatarSize,
      displayDescription = attributes.displayDescription,
      displayActionButton = attributes.displayActionButton,
      displayCoverImage = attributes.displayCoverImage;

  if (!attributes.itemID) {
    return createElement(Placeholder, {
      icon: "buddicons-groups",
      label: __('BuddyPress Group', 'buddypress'),
      instructions: __('Start typing the name of the group you want to feature into this post.', 'buddypress')
    }, createElement(AutoCompleter, {
      component: "groups",
      objectQueryArgs: {
        'show_hidden': false
      },
      slugValue: getSlugValue,
      ariaLabel: __('Group\'s name', 'buddypress'),
      placeholder: __('Enter Group\'s name here…', 'buddypress'),
      onSelectItem: setAttributes,
      useAvatar: isAvatarEnabled
    }));
  }

  return createElement(Fragment, null, createElement(BlockControls, null, createElement(Toolbar, {
    label: __('Block toolbar', 'buddypress')
  }, createElement(ToolbarButton, {
    icon: "edit",
    title: __('Select another group', 'buddypress'),
    onClick: function onClick() {
      setAttributes({
        itemID: 0
      });
    }
  }))), createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Settings', 'buddypress'),
    initialOpen: true
  }, createElement(ToggleControl, {
    label: __('Display Group\'s home button', 'buddypress'),
    checked: !!displayActionButton,
    onChange: function onChange() {
      setAttributes({
        displayActionButton: !displayActionButton
      });
    },
    help: displayActionButton ? __('Include a link to the group\'s home page under their name.', 'buddypress') : __('Toggle to display a link to the group\'s home page under their name.', 'buddypress')
  }), createElement(ToggleControl, {
    label: __('Display group\'s description', 'buddypress'),
    checked: !!displayDescription,
    onChange: function onChange() {
      setAttributes({
        displayDescription: !displayDescription
      });
    },
    help: displayDescription ? __('Include the group\'s description under their name.', 'buddypress') : __('Toggle to display the group\'s description under their name.', 'buddypress')
  }), isAvatarEnabled && createElement(SelectControl, {
    label: __('Avatar size', 'buddypress'),
    value: avatarSize,
    options: _constants.AVATAR_SIZES,
    help: __('Select "None" to disable the avatar.', 'buddypress'),
    onChange: function onChange(option) {
      setAttributes({
        avatarSize: option
      });
    }
  }), isCoverImageEnabled && createElement(ToggleControl, {
    label: __('Display Cover Image', 'buddypress'),
    checked: !!displayCoverImage,
    onChange: function onChange() {
      setAttributes({
        displayCoverImage: !displayCoverImage
      });
    },
    help: displayCoverImage ? __('Include the group\'s cover image over their name.', 'buddypress') : __('Toggle to display the group\'s cover image over their name.', 'buddypress')
  }))), createElement(Disabled, null, createElement(ServerSideRender, {
    block: "bp/group",
    attributes: attributes
  })));
};

var _default = editGroupBlock;
exports.default = _default;
},{"./constants":"atl5"}],"pvse":[function(require,module,exports) {
"use strict";

var _edit = _interopRequireDefault(require("./group/edit"));

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

registerBlockType('bp/group', {
  title: __('Group', 'buddypress'),
  description: __('BuddyPress Group.', 'buddypress'),
  icon: {
    background: '#fff',
    foreground: '#d84800',
    src: 'buddicons-groups'
  },
  category: 'buddypress',
  attributes: {
    itemID: {
      type: 'integer',
      default: 0
    },
    avatarSize: {
      type: 'string',
      default: 'full'
    },
    displayDescription: {
      type: 'boolean',
      default: true
    },
    displayActionButton: {
      type: 'boolean',
      default: true
    },
    displayCoverImage: {
      type: 'boolean',
      default: true
    }
  },
  edit: _edit.default
});
},{"./group/edit":"cCC3"}]},{},["pvse"], null)