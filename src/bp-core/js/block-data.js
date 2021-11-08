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
})({"Ih1j":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.STORE_KEY = void 0;

/**
 * Identifier key for BP Core store reducer.
 *
 * @type {string}
 */
const STORE_KEY = 'bp/core';
exports.STORE_KEY = STORE_KEY;
},{}],"DDtj":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getActiveComponents = void 0;

/**
 * Returns the list of Active BP Components.
 *
 * @param {Object} state The current state.
 * @return {array} The list of Active BP Components.
 */
const getActiveComponents = state => {
  return state.components || [];
};

exports.getActiveComponents = getActiveComponents;
},{}],"gg2v":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.TYPES = void 0;

/**
 * Action types.
 *
 * @type {Object}
 */
const TYPES = {
  GET_ACTIVE_COMPONENTS: 'GET_ACTIVE_COMPONENTS',
  FETCH_FROM_API: 'FETCH_FROM_API'
};
exports.TYPES = TYPES;
},{}],"NTbX":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getActiveComponents = getActiveComponents;
exports.fetchFromAPI = fetchFromAPI;

var _actionTypes = require("./action-types");

/**
 * Internal dependencies.
 */

/**
 * Returns the list of active components.
 *
 * @return {Object} Object for action.
 */
function getActiveComponents(list) {
  return {
    type: _actionTypes.TYPES.GET_ACTIVE_COMPONENTS,
    list
  };
}
/**
 * Returns an action object used to fetch something from the API.
 *
 * @param {string} path Endpoint path.
 * @param {boolean} parse Should we parse the request.
 * @return {Object} Object for action.
 */


function fetchFromAPI(path, parse) {
  return {
    type: _actionTypes.TYPES.FETCH_FROM_API,
    path,
    parse
  };
}
},{"./action-types":"gg2v"}],"SaI5":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getActiveComponents = getActiveComponents;

var _actions = require("./actions");

/**
 * Internal dependencies.
 */

/**
 * Resolver for retrieving active BP Components.
 */
function* getActiveComponents() {
  const list = yield (0, _actions.fetchFromAPI)('/buddypress/v1/components?status=active', true);
  yield (0, _actions.getActiveComponents)(list);
}
},{"./actions":"NTbX"}],"yrui":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _actionTypes = require("./action-types");

/**
 * Internal dependencies
 */

/**
 * Default state.
 */
const DEFAULT_STATE = {
  components: []
};
/**
 * Reducer for the BuddyPress data store.
 *
 * @param   {Object}  state   The current state in the store.
 * @param   {Object}  action  Action object.
 *
 * @return  {Object}          New or existing state.
 */

const reducer = (state = DEFAULT_STATE, action) => {
  switch (action.type) {
    case _actionTypes.TYPES.GET_ACTIVE_COMPONENTS:
      return { ...state,
        components: action.list
      };
  }

  return state;
};

var _default = reducer;
exports.default = _default;
},{"./action-types":"gg2v"}],"KdPQ":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.controls = void 0;

/**
 * WordPress dependencies.
 */
const {
  apiFetch
} = wp;
/**
 * Default export for registering the controls with the store.
 *
 * @return {Object} An object with the controls to register with the store on
 *                  the controls property of the registration object.
 */

const controls = {
  FETCH_FROM_API({
    path,
    parse
  }) {
    return apiFetch({
      path,
      parse
    });
  }

};
exports.controls = controls;
},{}],"QFc2":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.BP_CORE_STORE_KEY = void 0;

var _constants = require("./constants");

var selectors = _interopRequireWildcard(require("./selectors"));

var actions = _interopRequireWildcard(require("./actions"));

var resolvers = _interopRequireWildcard(require("./resolvers"));

var _reducers = _interopRequireDefault(require("./reducers"));

var _controls = require("./controls");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _getRequireWildcardCache(nodeInterop) { if (typeof WeakMap !== "function") return null; var cacheBabelInterop = new WeakMap(); var cacheNodeInterop = new WeakMap(); return (_getRequireWildcardCache = function (nodeInterop) { return nodeInterop ? cacheNodeInterop : cacheBabelInterop; })(nodeInterop); }

function _interopRequireWildcard(obj, nodeInterop) { if (!nodeInterop && obj && obj.__esModule) { return obj; } if (obj === null || typeof obj !== "object" && typeof obj !== "function") { return { default: obj }; } var cache = _getRequireWildcardCache(nodeInterop); if (cache && cache.has(obj)) { return cache.get(obj); } var newObj = {}; var hasPropertyDescriptor = Object.defineProperty && Object.getOwnPropertyDescriptor; for (var key in obj) { if (key !== "default" && Object.prototype.hasOwnProperty.call(obj, key)) { var desc = hasPropertyDescriptor ? Object.getOwnPropertyDescriptor(obj, key) : null; if (desc && (desc.get || desc.set)) { Object.defineProperty(newObj, key, desc); } else { newObj[key] = obj[key]; } } } newObj.default = obj; if (cache) { cache.set(obj, newObj); } return newObj; }

/**
 * WordPress dependencies.
 */
const {
  data: {
    registerStore
  }
} = wp;
/**
 * Internal dependencies.
 */

registerStore(_constants.STORE_KEY, {
  reducer: _reducers.default,
  actions,
  selectors,
  controls: _controls.controls,
  resolvers
});
const BP_CORE_STORE_KEY = _constants.STORE_KEY;
exports.BP_CORE_STORE_KEY = BP_CORE_STORE_KEY;
var _default = BP_CORE_STORE_KEY;
exports.default = _default;
},{"./constants":"Ih1j","./selectors":"DDtj","./actions":"NTbX","./resolvers":"SaI5","./reducers":"yrui","./controls":"KdPQ"}],"qcHp":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.isActive = isActive;
exports.activityTypes = activityTypes;
exports.loggedInUser = loggedInUser;
exports.postAuhor = postAuhor;
exports.currentPostId = currentPostId;
exports.getCurrentWidgetsSidebar = getCurrentWidgetsSidebar;
exports.default = void 0;

var _register = _interopRequireDefault(require("./register"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * WordPress dependencies.
 */
const {
  data: {
    useSelect
  }
} = wp;
/**
 * External dependencies.
 */

const {
  find,
  get
} = lodash;
/**
 * Internal dependencies.
 */

/**
 * Checks whether a component or the feature of an active component is enabled.
 *
 * @since 9.0.0
 *
 * @param {string} component (required) The component to check.
 * @param {string} feature (optional) The feature to check.
 * @return {boolean} Whether a component or the feature of an active component is enabled.
 */
function isActive(component, feature = '') {
  const components = useSelect(select => {
    return select(_register.default).getActiveComponents();
  }, []);
  const activeComponent = find(components, ['name', component]);

  if (!feature) {
    return !!activeComponent;
  }

  return get(activeComponent, ['features', feature]);
}

var _default = isActive;
/**
 * Checks whether a component or the feature of an active component is enabled.
 *
 * @since 9.0.0
 *
 * @return {array} An array of objects keyed by activity types.
 */

exports.default = _default;

function activityTypes() {
  const components = useSelect(select => {
    return select(_register.default).getActiveComponents();
  }, []);
  const activityComponent = find(components, ['name', 'activity']);

  if (!activityComponent) {
    return [];
  }

  const activityTypes = get(activityComponent, ['features', 'types']);
  let activityTypesList = [];
  Object.entries(activityTypes).forEach(([type, label]) => {
    activityTypesList.push({
      label: label,
      value: type
    });
  });
  return activityTypesList;
}
/**
 * Returns the logged in user object.
 *
 * @since 9.0.0
 *
 * @return {Object} The logged in user object.
 */


function loggedInUser() {
  const loggedInUser = useSelect(select => {
    const store = select('core');

    if (store) {
      return select('core').getCurrentUser();
    }

    return {};
  }, []);
  return loggedInUser;
}
/**
 * Returns the post author user object.
 *
 * @since 9.0.0
 *
 * @return {Object} The post author user object.
 */


function postAuhor() {
  const postAuhor = useSelect(select => {
    const editorStore = select('core/editor');
    const coreStore = select('core');

    if (editorStore && coreStore) {
      const postAuthorId = editorStore.getCurrentPostAttribute('author');
      const authorsList = coreStore.getAuthors();
      return find(authorsList, ['id', postAuthorId]);
    }

    return {};
  }, []);
  return postAuhor;
}
/**
 * Returns the current post ID.
 *
 * @since 9.0.0
 *
 * @return {integer} The current post ID.
 */


function currentPostId() {
  const currentPostId = useSelect(select => {
    const store = select('core/editor');

    if (store) {
      return store.getCurrentPostId();
    }

    return 0;
  }, []);
  return currentPostId;
}
/**
 * Get the current sidebar of a Widget Block.
 *
 * @since 9.0.0
 *
 * @param {string} widgetClientId clientId of the sidebar widget.
 * @return {object} An object containing the sidebar Id.
 */


function getCurrentWidgetsSidebar(widgetClientId = '') {
  const currentWidgetsSidebar = useSelect(select => {
    const blockEditorStore = select('core/block-editor');
    const widgetsStore = select('core/edit-widgets');

    if (widgetClientId && widgetsStore && blockEditorStore) {
      const areas = blockEditorStore.getBlocks();
      const parents = blockEditorStore.getBlockParents(widgetClientId);
      let sidebars = [];
      areas.forEach(({
        clientId,
        attributes
      }) => {
        sidebars.push({
          id: attributes.id,
          isCurrent: -1 !== parents.indexOf(clientId)
        });
      });
      return find(sidebars, ['isCurrent', true]);
    }

    return {};
  }, []);
  return currentWidgetsSidebar;
}
},{"./register":"QFc2"}],"xHVY":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _utils = _interopRequireWildcard(require("./utils"));

function _getRequireWildcardCache(nodeInterop) { if (typeof WeakMap !== "function") return null; var cacheBabelInterop = new WeakMap(); var cacheNodeInterop = new WeakMap(); return (_getRequireWildcardCache = function (nodeInterop) { return nodeInterop ? cacheNodeInterop : cacheBabelInterop; })(nodeInterop); }

function _interopRequireWildcard(obj, nodeInterop) { if (!nodeInterop && obj && obj.__esModule) { return obj; } if (obj === null || typeof obj !== "object" && typeof obj !== "function") { return { default: obj }; } var cache = _getRequireWildcardCache(nodeInterop); if (cache && cache.has(obj)) { return cache.get(obj); } var newObj = {}; var hasPropertyDescriptor = Object.defineProperty && Object.getOwnPropertyDescriptor; for (var key in obj) { if (key !== "default" && Object.prototype.hasOwnProperty.call(obj, key)) { var desc = hasPropertyDescriptor ? Object.getOwnPropertyDescriptor(obj, key) : null; if (desc && (desc.get || desc.set)) { Object.defineProperty(newObj, key, desc); } else { newObj[key] = obj[key]; } } } newObj.default = obj; if (cache) { cache.set(obj, newObj); } return newObj; }

// Data.
var _default = {
  isActive: _utils.default,
  activityTypes: _utils.activityTypes,
  loggedInUser: _utils.loggedInUser,
  postAuhor: _utils.postAuhor,
  currentPostId: _utils.currentPostId,
  getCurrentWidgetsSidebar: _utils.getCurrentWidgetsSidebar
};
exports.default = _default;
},{"./utils":"qcHp"}],"tYf0":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
Object.defineProperty(exports, "blockData", {
  enumerable: true,
  get: function () {
    return _data.default;
  }
});

var _data = _interopRequireDefault(require("./data"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
},{"./data":"xHVY"}]},{},["tYf0"], "bpBlock")