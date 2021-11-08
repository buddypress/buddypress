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
})({"gr8I":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.EXTRA_DATA = exports.AVATAR_SIZES = void 0;

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
/**
 * BuddyPress Extra data.
 *
 * @type {Array}
 */

exports.AVATAR_SIZES = AVATAR_SIZES;
const EXTRA_DATA = [{
  label: __('None', 'buddypress'),
  value: 'none'
}, {
  label: __('Last time the user was active', 'buddypress'),
  value: 'last_activity'
}, {
  label: __('Latest activity the user posted', 'buddypress'),
  value: 'latest_update'
}];
exports.EXTRA_DATA = EXTRA_DATA;
},{}],"PZSE":[function(require,module,exports) {
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
    PanelBody,
    SelectControl,
    ToggleControl,
    Button,
    Dashicon,
    Tooltip,
    ToolbarGroup,
    RangeControl
  },
  element: {
    createElement,
    Fragment,
    useState
  },
  i18n: {
    __,
    sprintf
  },
  apiFetch,
  url: {
    addQueryArgs
  }
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

/**
 * External dependencies.
 */
const {
  reject,
  remove,
  sortBy
} = lodash;

const getSlugValue = item => {
  if (item && item.mention_name) {
    return item.mention_name;
  }

  return null;
};

const editMembersBlock = ({
  attributes,
  setAttributes,
  isSelected
}) => {
  const isAvatarEnabled = isActive('members', 'avatar');
  const isMentionEnabled = isActive('activity', 'mentions');
  const {
    itemIDs,
    avatarSize,
    displayMentionSlug,
    displayUserName,
    extraData,
    layoutPreference,
    columns
  } = attributes;
  const hasMembers = 0 !== itemIDs.length;
  const [members, setMembers] = useState([]);
  const layoutControls = [{
    icon: 'text',
    title: __('List view', 'buddypress'),
    onClick: () => setAttributes({
      layoutPreference: 'list'
    }),
    isActive: layoutPreference === 'list'
  }, {
    icon: 'screenoptions',
    title: __('Grid view', 'buddypress'),
    onClick: () => setAttributes({
      layoutPreference: 'grid'
    }),
    isActive: layoutPreference === 'grid'
  }];
  let membersList;
  let containerClasses = 'bp-block-members avatar-' + avatarSize;
  let extraDataOptions = _constants.EXTRA_DATA;

  if (layoutPreference === 'grid') {
    containerClasses += ' is-grid columns-' + columns;
    extraDataOptions = _constants.EXTRA_DATA.filter(extra => {
      return 'latest_update' !== extra.value;
    });
  }

  const onSelectedMember = ({
    itemID
  }) => {
    if (itemID && -1 === itemIDs.indexOf(itemID)) {
      setAttributes({
        itemIDs: [...itemIDs, parseInt(itemID, 10)]
      });
    }
  };

  const onRemoveMember = itemID => {
    if (itemID && -1 !== itemIDs.indexOf(itemID)) {
      setMembers(reject(members, ['id', itemID]));
      setAttributes({
        itemIDs: remove(itemIDs, value => {
          return value !== itemID;
        })
      });
    }
  };

  if (hasMembers && itemIDs.length !== members.length) {
    apiFetch({
      path: addQueryArgs(`/buddypress/v1/members`, {
        populate_extras: true,
        include: itemIDs
      })
    }).then(items => {
      setMembers(sortBy(items, [item => {
        return itemIDs.indexOf(item.id);
      }]));
    });
  }

  if (members.length) {
    membersList = members.map(member => {
      let hasActivity = false;
      let memberItemClasses = 'member-content';

      if (layoutPreference === 'list' && 'latest_update' === extraData && member.latest_update && member.latest_update.rendered) {
        hasActivity = true;
        memberItemClasses = 'member-content has-activity';
      }

      return createElement("div", {
        key: 'bp-member-' + member.id,
        className: memberItemClasses
      }, isSelected && createElement(Tooltip, {
        text: __('Remove member', 'buddypress')
      }, createElement(Button, {
        className: "is-right",
        onClick: () => onRemoveMember(member.id),
        label: __('Remove member', 'buddypress')
      }, createElement(Dashicon, {
        icon: "no"
      }))), isAvatarEnabled && 'none' !== avatarSize && createElement("div", {
        className: "item-header-avatar"
      }, createElement("a", {
        href: member.link,
        target: "_blank"
      }, createElement("img", {
        key: 'avatar-' + member.id,
        className: "avatar",
        alt: sprintf(__('Profile photo of %s', 'buddypress'), member.name),
        src: member.avatar_urls[avatarSize]
      }))), createElement("div", {
        className: "member-description"
      }, hasActivity && createElement("blockquote", {
        className: "wp-block-quote"
      }, createElement("div", {
        dangerouslySetInnerHTML: {
          __html: member.latest_update.rendered
        }
      }), createElement("cite", null, displayUserName && createElement("span", null, member.name), "\xA0", isMentionEnabled && displayMentionSlug && createElement("a", {
        href: member.link,
        target: "_blank"
      }, "(@", member.mention_name, ")"))), !hasActivity && displayUserName && createElement("strong", null, createElement("a", {
        href: member.link,
        target: "_blank"
      }, member.name)), !hasActivity && isMentionEnabled && displayMentionSlug && createElement("span", {
        className: "user-nicename"
      }, "@", member.mention_name), 'last_activity' === extraData && member.last_activity && member.last_activity.date && createElement("time", {
        dateTime: member.last_activity.date
      }, sprintf(__('Active %s', 'buddypress'), member.last_activity.timediff))));
    });
  }

  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Settings', 'buddypress'),
    initialOpen: true
  }, createElement(ToggleControl, {
    label: __('Display the user name', 'buddypress'),
    checked: !!displayUserName,
    onChange: () => {
      setAttributes({
        displayUserName: !displayUserName
      });
    },
    help: displayUserName ? __('Include the user\'s display name.', 'buddypress') : __('Toggle to include user\'s display name.', 'buddypress')
  }), isMentionEnabled && createElement(ToggleControl, {
    label: __('Display Mention slug', 'buddypress'),
    checked: !!displayMentionSlug,
    onChange: () => {
      setAttributes({
        displayMentionSlug: !displayMentionSlug
      });
    },
    help: displayMentionSlug ? __('Include the user\'s mention name under their display name.', 'buddypress') : __('Toggle to display the user\'s mention name under their display name.', 'buddypress')
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
  }), createElement(SelectControl, {
    label: __('BuddyPress extra information', 'buddypress'),
    value: extraData,
    options: extraDataOptions,
    help: __('Select "None" to show no extra information.', 'buddypress'),
    onChange: option => {
      setAttributes({
        extraData: option
      });
    }
  }), layoutPreference === 'grid' && createElement(RangeControl, {
    label: __('Columns', 'buddypress'),
    value: columns,
    onChange: value => setAttributes({
      columns: value
    }),
    min: 2,
    max: 4,
    required: true
  }))), createElement(BlockControls, null, createElement(ToolbarGroup, {
    controls: layoutControls
  })), hasMembers && createElement("div", {
    className: containerClasses
  }, membersList), (isSelected || 0 === itemIDs.length) && createElement(Placeholder, {
    icon: hasMembers ? '' : 'groups',
    label: hasMembers ? '' : __('BuddyPress Members', 'buddypress'),
    instructions: __('Start typing the name of the member you want to add to the members list.', 'buddypress'),
    className: 0 !== itemIDs.length ? 'is-appender' : 'is-large'
  }, createElement(AutoCompleter, {
    component: "members",
    objectQueryArgs: {
      exclude: itemIDs
    },
    slugValue: getSlugValue,
    ariaLabel: __('Member\'s username', 'buddypress'),
    placeholder: __('Enter Member\'s username here…', 'buddypress'),
    onSelectItem: onSelectedMember,
    useAvatar: isAvatarEnabled
  })));
};

var _default = editMembersBlock;
exports.default = _default;
},{"./constants":"gr8I"}],"XEHU":[function(require,module,exports) {
"use strict";

var _edit = _interopRequireDefault(require("./members/edit"));

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

registerBlockType('bp/members', {
  title: __('Members', 'buddypress'),
  description: __('BuddyPress Members.', 'buddypress'),
  icon: {
    background: '#fff',
    foreground: '#d84800',
    src: 'groups'
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
    displayMentionSlug: {
      type: 'boolean',
      default: true
    },
    displayUserName: {
      type: 'boolean',
      default: true
    },
    extraData: {
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
},{"./members/edit":"PZSE"}]},{},["XEHU"], null)