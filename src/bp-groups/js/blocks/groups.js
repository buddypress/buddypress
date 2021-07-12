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
})({"LGpM":[function(require,module,exports) {
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) {
    arr2[i] = arr[i];
  }

  return arr2;
}

module.exports = _arrayLikeToArray;
},{}],"o3SL":[function(require,module,exports) {
var arrayLikeToArray = require("./arrayLikeToArray");

function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) return arrayLikeToArray(arr);
}

module.exports = _arrayWithoutHoles;
},{"./arrayLikeToArray":"LGpM"}],"lZpU":[function(require,module,exports) {
function _iterableToArray(iter) {
  if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter);
}

module.exports = _iterableToArray;
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
},{"./arrayLikeToArray":"LGpM"}],"NCaH":[function(require,module,exports) {
function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableSpread;
},{}],"I9dH":[function(require,module,exports) {
var arrayWithoutHoles = require("./arrayWithoutHoles");

var iterableToArray = require("./iterableToArray");

var unsupportedIterableToArray = require("./unsupportedIterableToArray");

var nonIterableSpread = require("./nonIterableSpread");

function _toConsumableArray(arr) {
  return arrayWithoutHoles(arr) || iterableToArray(arr) || unsupportedIterableToArray(arr) || nonIterableSpread();
}

module.exports = _toConsumableArray;
},{"./arrayWithoutHoles":"o3SL","./iterableToArray":"lZpU","./unsupportedIterableToArray":"Vzqv","./nonIterableSpread":"NCaH"}],"dEOc":[function(require,module,exports) {
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
},{}],"sa4T":[function(require,module,exports) {
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
},{"./arrayWithHoles":"dEOc","./iterableToArrayLimit":"RonT","./unsupportedIterableToArray":"Vzqv","./nonIterableRest":"sa4T"}],"jS06":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.EXTRA_INFO = exports.GROUP_STATI = exports.AVATAR_SIZES = void 0;

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
/**
 * Group Extra data.
 *
 * @type {Array}
 */

exports.GROUP_STATI = GROUP_STATI;
var EXTRA_INFO = [{
  label: __('None', 'buddypress'),
  value: 'none'
}, {
  label: __('Group\'s description', 'buddypress'),
  value: 'description'
}, {
  label: __('Last time the group was active', 'buddypress'),
  value: 'active'
}, {
  label: __('Amount of group members', 'buddypress'),
  value: 'popular'
}];
exports.EXTRA_INFO = EXTRA_INFO;
},{}],"Ccmh":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _constants = require("./constants");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * WordPress dependencies.
 */
var _wp = wp,
    _wp$blockEditor = _wp.blockEditor,
    InspectorControls = _wp$blockEditor.InspectorControls,
    BlockControls = _wp$blockEditor.BlockControls,
    _wp$components = _wp.components,
    Placeholder = _wp$components.Placeholder,
    PanelBody = _wp$components.PanelBody,
    SelectControl = _wp$components.SelectControl,
    ToggleControl = _wp$components.ToggleControl,
    Button = _wp$components.Button,
    Dashicon = _wp$components.Dashicon,
    Tooltip = _wp$components.Tooltip,
    ToolbarGroup = _wp$components.ToolbarGroup,
    RangeControl = _wp$components.RangeControl,
    _wp$element = _wp.element,
    createElement = _wp$element.createElement,
    Fragment = _wp$element.Fragment,
    useState = _wp$element.useState,
    _wp$i18n = _wp.i18n,
    __ = _wp$i18n.__,
    sprintf = _wp$i18n.sprintf,
    _n = _wp$i18n._n,
    apiFetch = _wp.apiFetch,
    addQueryArgs = _wp.url.addQueryArgs;
/**
 * BuddyPress dependencies.
 */

var _bp = bp,
    AutoCompleter = _bp.blockComponents.AutoCompleter,
    isActive = _bp.blockData.isActive;
/**
 * Internal dependencies.
 */

/**
 * External dependencies.
 */
var _lodash = lodash,
    reject = _lodash.reject,
    remove = _lodash.remove,
    sortBy = _lodash.sortBy;

var getSlugValue = function getSlugValue(item) {
  if (item && item.status && _constants.GROUP_STATI[item.status]) {
    return _constants.GROUP_STATI[item.status];
  }

  return null;
};

var editGroupsBlock = function editGroupsBlock(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      isSelected = _ref.isSelected;
  var isAvatarEnabled = isActive('groups', 'avatar');
  var itemIDs = attributes.itemIDs,
      avatarSize = attributes.avatarSize,
      displayGroupName = attributes.displayGroupName,
      extraInfo = attributes.extraInfo,
      layoutPreference = attributes.layoutPreference,
      columns = attributes.columns;
  var hasGroups = 0 !== itemIDs.length;

  var _useState = useState([]),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      groups = _useState2[0],
      setGroups = _useState2[1];

  var layoutControls = [{
    icon: 'text',
    title: __('List view', 'buddypress'),
    onClick: function onClick() {
      return setAttributes({
        layoutPreference: 'list'
      });
    },
    isActive: layoutPreference === 'list'
  }, {
    icon: 'screenoptions',
    title: __('Grid view', 'buddypress'),
    onClick: function onClick() {
      return setAttributes({
        layoutPreference: 'grid'
      });
    },
    isActive: layoutPreference === 'grid'
  }];
  var groupsList;
  var containerClasses = 'bp-block-groups avatar-' + avatarSize;
  var extraInfoOptions = _constants.EXTRA_INFO;

  if (layoutPreference === 'grid') {
    containerClasses += ' is-grid columns-' + columns;
    extraInfoOptions = _constants.EXTRA_INFO.filter(function (extra) {
      return 'description' !== extra.value;
    });
  }

  var onSelectedGroup = function onSelectedGroup(_ref2) {
    var itemID = _ref2.itemID;

    if (itemID && -1 === itemIDs.indexOf(itemID)) {
      setAttributes({
        itemIDs: [].concat((0, _toConsumableArray2.default)(itemIDs), [parseInt(itemID, 10)])
      });
    }
  };

  var onRemoveGroup = function onRemoveGroup(itemID) {
    if (itemID && -1 !== itemIDs.indexOf(itemID)) {
      setGroups(reject(groups, ['id', itemID]));
      setAttributes({
        itemIDs: remove(itemIDs, function (value) {
          return value !== itemID;
        })
      });
    }
  };

  if (hasGroups && itemIDs.length !== groups.length) {
    apiFetch({
      path: addQueryArgs("/buddypress/v1/groups", {
        populate_extras: true,
        include: itemIDs
      })
    }).then(function (items) {
      setGroups(sortBy(items, [function (item) {
        return itemIDs.indexOf(item.id);
      }]));
    });
  }

  if (groups.length) {
    groupsList = groups.map(function (group) {
      var hasDescription = false;
      var groupItemClasses = 'group-content';

      if (layoutPreference === 'list' && 'description' === extraInfo && group.description && group.description.rendered) {
        hasDescription = true;
        groupItemClasses = 'group-content has-description';
      }

      return createElement("div", {
        key: 'bp-group-' + group.id,
        className: groupItemClasses
      }, isSelected && createElement(Tooltip, {
        text: __('Remove group', 'buddypress')
      }, createElement(Button, {
        className: "is-right",
        onClick: function onClick() {
          return onRemoveGroup(group.id);
        },
        label: __('Remove group', 'buddypress')
      }, createElement(Dashicon, {
        icon: "no"
      }))), isAvatarEnabled && 'none' !== avatarSize && createElement("div", {
        className: "item-header-avatar"
      }, createElement("a", {
        href: group.link,
        target: "_blank"
      }, createElement("img", {
        key: 'avatar-' + group.id,
        className: "avatar",
        alt: sprintf(__('Profile photo of %s', 'buddypress'), group.name),
        src: group.avatar_urls[avatarSize]
      }))), createElement("div", {
        className: "group-description"
      }, displayGroupName && createElement("strong", null, createElement("a", {
        href: group.link,
        target: "_blank"
      }, group.name)), hasDescription && createElement("div", {
        className: "group-description-content",
        dangerouslySetInnerHTML: {
          __html: group.description.rendered
        }
      }), 'active' === extraInfo && group.last_activity && group.last_activity_diff && createElement("time", {
        dateTime: group.last_activity
      }, sprintf(__('Active %s', 'buddypress'), group.last_activity_diff)), 'popular' === extraInfo && group.total_member_count && createElement("div", {
        className: "group-meta"
      }, sprintf(
      /* translators: 1: number of group memberss. */
      _n('%1$d member', '%1$d members', group.total_member_count, 'buddypress'), group.total_member_count))));
    });
  }

  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Settings', 'buddypress'),
    initialOpen: true
  }, createElement(ToggleControl, {
    label: __('Display the group\'s name', 'buddypress'),
    checked: !!displayGroupName,
    onChange: function onChange() {
      setAttributes({
        displayGroupName: !displayGroupName
      });
    },
    help: displayGroupName ? __('Include the group\'s name.', 'buddypress') : __('Toggle to include group\'s name.', 'buddypress')
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
  }), createElement(SelectControl, {
    label: __('Group extra information', 'buddypress'),
    value: extraInfo,
    options: extraInfoOptions,
    help: __('Select "None" to show no extra information.', 'buddypress'),
    onChange: function onChange(option) {
      setAttributes({
        extraInfo: option
      });
    }
  }), layoutPreference === 'grid' && createElement(RangeControl, {
    label: __('Columns', 'buddypress'),
    value: columns,
    onChange: function onChange(value) {
      return setAttributes({
        columns: value
      });
    },
    min: 2,
    max: 4,
    required: true
  }))), createElement(BlockControls, null, createElement(ToolbarGroup, {
    controls: layoutControls
  })), hasGroups && createElement("div", {
    className: containerClasses
  }, groupsList), (isSelected || 0 === itemIDs.length) && createElement(Placeholder, {
    icon: hasGroups ? '' : 'groups',
    label: hasGroups ? '' : __('BuddyPress Groups', 'buddypress'),
    instructions: __('Start typing the name of the group you want to add to the groups list.', 'buddypress'),
    className: 0 !== itemIDs.length ? 'is-appender' : 'is-large'
  }, createElement(AutoCompleter, {
    component: "groups",
    objectQueryArgs: {
      'show_hidden': false,
      exclude: itemIDs
    },
    slugValue: getSlugValue,
    ariaLabel: __('Group\'s name', 'buddypress'),
    placeholder: __('Enter Group\'s name here…', 'buddypress'),
    onSelectItem: onSelectedGroup,
    useAvatar: isAvatarEnabled
  })));
};

var _default = editGroupsBlock;
exports.default = _default;
},{"@babel/runtime/helpers/toConsumableArray":"I9dH","@babel/runtime/helpers/slicedToArray":"xkYc","./constants":"jS06"}],"jcTh":[function(require,module,exports) {
"use strict";

var _edit = _interopRequireDefault(require("./groups/edit"));

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

registerBlockType('bp/groups', {
  title: __('Groups', 'buddypress'),
  description: __('BuddyPress Groups.', 'buddypress'),
  icon: {
    background: '#fff',
    foreground: '#d84800',
    src: 'buddicons-groups'
  },
  category: 'buddypress',
  attributes: {
    itemIDs: {
      type: 'array',
      items: {
        type: 'integer'
      },
      default: []
    },
    avatarSize: {
      type: 'string',
      default: 'full'
    },
    displayGroupName: {
      type: 'boolean',
      default: true
    },
    extraInfo: {
      type: 'string',
      default: 'none'
    },
    layoutPreference: {
      type: 'string',
      default: 'list'
    },
    columns: {
      type: 'number',
      default: 2
    }
  },
  edit: _edit.default
});
},{"./groups/edit":"Ccmh"}]},{},["jcTh"], null)