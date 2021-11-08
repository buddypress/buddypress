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
})({"CSIX":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.TYPES = void 0;

/**
 * WordPress dependencies.
 */
const {
  i18n: {
    __
  }
} = wp;
/**
 * Friends ordering types.
 *
 * @type {Array}
 */

const TYPES = [{
  label: __('Newest', 'buddypress'),
  value: 'newest'
}, {
  label: __('Active', 'buddypress'),
  value: 'active'
}, {
  label: __('Popular', 'buddypress'),
  value: 'popular'
}];
exports.TYPES = TYPES;
},{}],"qXsY":[function(require,module,exports) {
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
    InspectorControls
  },
  components: {
    Disabled,
    PanelBody,
    RangeControl,
    SelectControl,
    ToggleControl
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
  blockData: {
    currentPostId
  }
} = bp;
/**
 * Internal dependencies.
 */

const editDynamicFriendsBlock = ({
  attributes,
  setAttributes
}) => {
  const {
    postId,
    maxFriends,
    friendDefault,
    linkTitle
  } = attributes;
  const post = currentPostId();

  if (!postId && post) {
    setAttributes({
      postId: post
    });
  }

  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Settings', 'buddypress'),
    initialOpen: true
  }, createElement(RangeControl, {
    label: __('Max friends to show', 'buddypress'),
    value: maxFriends,
    onChange: value => setAttributes({
      maxFriends: value
    }),
    min: 1,
    max: 10,
    required: true
  }), createElement(SelectControl, {
    label: __('Default members to show', 'buddypress'),
    value: friendDefault,
    options: _constants.TYPES,
    onChange: option => {
      setAttributes({
        friendDefault: option
      });
    }
  }), createElement(ToggleControl, {
    label: __('Link block title to Member\'s profile friends page', 'buddypress'),
    checked: !!linkTitle,
    onChange: () => {
      setAttributes({
        linkTitle: !linkTitle
      });
    }
  }))), createElement(Disabled, null, createElement(ServerSideRender, {
    block: "bp/friends",
    attributes: attributes
  })));
};

var _default = editDynamicFriendsBlock;
exports.default = _default;
},{"./constants":"CSIX"}],"fch3":[function(require,module,exports) {
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

      return idBase === 'bp_core_friends_widget';
    },
    transform: ({
      instance
    }) => {
      return createBlock('bp/friends', {
        maxFriends: instance.raw.max_friends,
        friendDefault: instance.raw.friend_default,
        linkTitle: instance.raw.link_title
      });
    }
  }]
};
var _default = transforms;
exports.default = _default;
},{}],"Z2R5":[function(require,module,exports) {
"use strict";

var _edit = _interopRequireDefault(require("./friends/edit"));

var _transforms = _interopRequireDefault(require("./friends/transforms"));

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

registerBlockType('bp/friends', {
  title: __('Friends List', 'buddypress'),
  description: __('A dynamic list of recently active, popular, and newest friends of the post author (when used into a page or post) or of the displayed member (when used in a widgetized area). If author/member data is not available the block is not displayed.', 'buddypress'),
  icon: {
    background: '#fff',
    foreground: '#d84800',
    src: 'buddicons-friends'
  },
  category: 'buddypress',
  attributes: {
    maxFriends: {
      type: 'number',
      default: 5
    },
    friendDefault: {
      type: 'string',
      default: 'active'
    },
    linkTitle: {
      type: 'boolean',
      default: false
    },
    postId: {
      type: 'number',
      default: 0
    }
  },
  edit: _edit.default,
  transforms: _transforms.default
});
},{"./friends/edit":"qXsY","./friends/transforms":"fch3"}]},{},["Z2R5"], null)