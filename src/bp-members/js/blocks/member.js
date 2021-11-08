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
})({"AE3e":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.AVATAR_SIZES = void 0;

/**
 * WordPress dependencies.
 */
const {
  i18n: {
    __
  }
} = wp;
/**
 * Avatar sizes.
 *
 * @type {Array}
 */

const AVATAR_SIZES = [{
  label: __('None', 'buddypress'),
  value: 'none'
}, {
  label: __('Thumb', 'buddypress'),
  value: 'thumb'
}, {
  label: __('Full', 'buddypress'),
  value: 'full'
}];
exports.AVATAR_SIZES = AVATAR_SIZES;
},{}],"YNTp":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _constants = require("./constants");

/**
 * WordPress dependencies.
 */
const {
  blockEditor: {
    InspectorControls,
    BlockControls
  },
  components: {
    Placeholder,
    Disabled,
    PanelBody,
    SelectControl,
    ToggleControl,
    Toolbar,
    ToolbarButton
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
/**
 * BuddyPress dependencies.
 */

const {
  blockComponents: {
    AutoCompleter
  },
  blockData: {
    isActive
  }
} = bp;
/**
 * Internal dependencies.
 */

const getSlugValue = item => {
  if (item && item.mention_name) {
    return item.mention_name;
  }

  return null;
};

const editMemberBlock = ({
  attributes,
  setAttributes
}) => {
  const isAvatarEnabled = isActive('members', 'avatar');
  const isMentionEnabled = isActive('activity', 'mentions');
  const isCoverImageEnabled = isActive('members', 'cover');
  const {
    avatarSize,
    displayMentionSlug,
    displayActionButton,
    displayCoverImage
  } = attributes;

  if (!attributes.itemID) {
    return createElement(Placeholder, {
      icon: "admin-users",
      label: __('BuddyPress Member', 'buddypress'),
      instructions: __('Start typing the name of the member you want to feature into this post.', 'buddypress')
    }, createElement(AutoCompleter, {
      component: "members",
      slugValue: getSlugValue,
      ariaLabel: __('Member\'s username', 'buddypress'),
      placeholder: __('Enter Member\'s username here…', 'buddypress'),
      onSelectItem: setAttributes,
      useAvatar: isAvatarEnabled
    }));
  }

  return createElement(Fragment, null, createElement(BlockControls, null, createElement(Toolbar, {
    label: __('Block toolbar', 'buddypress')
  }, createElement(ToolbarButton, {
    icon: "edit",
    title: __('Select another member', 'buddypress'),
    onClick: () => {
      setAttributes({
        itemID: 0
      });
    }
  }))), createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Settings', 'buddypress'),
    initialOpen: true
  }, createElement(ToggleControl, {
    label: __('Display Profile button', 'buddypress'),
    checked: !!displayActionButton,
    onChange: () => {
      setAttributes({
        displayActionButton: !displayActionButton
      });
    },
    help: displayActionButton ? __('Include a link to the user\'s profile page under their display name.', 'buddypress') : __('Toggle to display a link to the user\'s profile page under their display name.', 'buddypress')
  }), isAvatarEnabled && createElement(SelectControl, {
    label: __('Avatar size', 'buddypress'),
    value: avatarSize,
    options: _constants.AVATAR_SIZES,
    help: __('Select "None" to disable the avatar.', 'buddypress'),
    onChange: option => {
      setAttributes({
        avatarSize: option
      });
    }
  }), isCoverImageEnabled && createElement(ToggleControl, {
    label: __('Display Cover Image', 'buddypress'),
    checked: !!displayCoverImage,
    onChange: () => {
      setAttributes({
        displayCoverImage: !displayCoverImage
      });
    },
    help: displayCoverImage ? __('Include the user\'s cover image over their display name.', 'buddypress') : __('Toggle to display the user\'s cover image over their display name.', 'buddypress')
  }), isMentionEnabled && createElement(ToggleControl, {
    label: __('Display Mention slug', 'buddypress'),
    checked: !!displayMentionSlug,
    onChange: () => {
      setAttributes({
        displayMentionSlug: !displayMentionSlug
      });
    },
    help: displayMentionSlug ? __('Include the user\'s mention name under their display name.', 'buddypress') : __('Toggle to display the user\'s mention name under their display name.', 'buddypress')
  }))), createElement(Disabled, null, createElement(ServerSideRender, {
    block: "bp/member",
    attributes: attributes
  })));
};

var _default = editMemberBlock;
exports.default = _default;
},{"./constants":"AE3e"}],"TmUL":[function(require,module,exports) {
"use strict";

var _edit = _interopRequireDefault(require("./member/edit"));

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

registerBlockType('bp/member', {
  title: __('Member', 'buddypress'),
  description: __('BuddyPress Member.', 'buddypress'),
  icon: {
    background: '#fff',
    foreground: '#d84800',
    src: 'admin-users'
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
    displayMentionSlug: {
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
},{"./member/edit":"YNTp"}]},{},["TmUL"], null)