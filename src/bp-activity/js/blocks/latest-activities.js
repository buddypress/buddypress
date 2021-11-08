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
})({"DIzr":[function(require,module,exports) {
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
    RangeControl,
    SelectControl,
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
/**
 * BuddyPress dependencies.
 */

const {
  blockData: {
    currentPostId,
    activityTypes
  }
} = bp;

const editDynamicActivitiesBlock = ({
  attributes,
  setAttributes
}) => {
  const {
    postId,
    maxActivities,
    type,
    title
  } = attributes;
  const post = currentPostId();
  const types = activityTypes();

  if (!postId && post) {
    setAttributes({
      postId: post
    });

    if (!attributes.postId) {
      attributes.postId = post;
    }
  }

  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Settings', 'buddypress'),
    initialOpen: true,
    className: "bp-latest-activities"
  }, createElement(TextControl, {
    label: __('Title', 'buddypress'),
    value: title,
    onChange: text => {
      setAttributes({
        title: text
      });
    }
  }), createElement(RangeControl, {
    label: __('Maximum amount to display', 'buddypress'),
    value: maxActivities,
    onChange: value => setAttributes({
      maxActivities: value
    }),
    min: 1,
    max: 10,
    required: true
  }), createElement(SelectControl, {
    multiple: true,
    label: __('Type', 'buddypress'),
    value: type,
    options: types,
    onChange: option => {
      setAttributes({
        type: option
      });
    }
  }))), createElement(Disabled, null, createElement(ServerSideRender, {
    block: "bp/latest-activities",
    attributes: attributes
  })));
};

var _default = editDynamicActivitiesBlock;
exports.default = _default;
},{}],"yqpU":[function(require,module,exports) {
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
 * Transforms Nouveau Activity Widget to Activity Block.
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

      return idBase === 'bp_latest_activities';
    },
    transform: ({
      instance
    }) => {
      const regex = /i:\d*;s:\d*:"(.*?)";/gmi;
      let types = [];
      let matches;

      while ((matches = regex.exec(instance.raw.type)) !== null) {
        if (matches.index === regex.lastIndex) {
          regex.lastIndex++;
        }

        matches.forEach((match, groupIndex) => {
          if (1 === groupIndex) {
            types.push(match);
          }
        });
      }

      return createBlock('bp/latest-activities', {
        title: instance.raw.title,
        maxActivities: parseInt(instance.raw.max, 10),
        type: types
      });
    }
  }]
};
var _default = transforms;
exports.default = _default;
},{}],"q3eE":[function(require,module,exports) {
"use strict";

var _edit = _interopRequireDefault(require("./latest-activities/edit"));

var _transforms = _interopRequireDefault(require("./latest-activities/transforms"));

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

registerBlockType('bp/latest-activities', {
  title: __('Latest Activities', 'buddypress'),
  description: __('Display the latest updates of the post author (when used into a page or post), of the displayed user (when viewing their profile) or of your community.', 'buddypress'),
  icon: {
    background: '#fff',
    foreground: '#d84800',
    src: 'buddicons-activity'
  },
  category: 'buddypress',
  attributes: {
    title: {
      type: 'string',
      default: __('Latest updates', 'buddypress')
    },
    maxActivities: {
      type: 'number',
      default: 5
    },
    type: {
      type: 'array',
      default: ['activity_update']
    },
    postId: {
      type: 'number',
      default: 0
    }
  },
  edit: _edit.default,
  transforms: _transforms.default
});
},{"./latest-activities/edit":"DIzr","./latest-activities/transforms":"yqpU"}]},{},["q3eE"], null)