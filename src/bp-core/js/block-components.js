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
})({"W80x":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

/**
 * WordPress dependencies.
 */
const {
  apiFetch,
  components: {
    Popover
  },
  element: {
    Component,
    Fragment,
    createElement
  },
  i18n: {
    __
  },
  url: {
    addQueryArgs
  }
} = wp;

class AutoCompleter extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      search: '',
      items: [],
      error: ''
    };
    this.searchItemName = this.searchItemName.bind(this);
    this.selectItemName = this.selectItemName.bind(this);
  }

  searchItemName(value) {
    const {
      search
    } = this.state;
    const {
      component,
      objectQueryArgs
    } = this.props;
    this.setState({
      search: value
    });

    if (value.length < search.length) {
      this.setState({
        items: []
      });
    }

    let path = '/buddypress/v1/' + component;
    let queryArgs = {};

    if (value) {
      queryArgs.search = encodeURIComponent(value);
    }

    if (objectQueryArgs) {
      queryArgs = Object.assign(queryArgs, objectQueryArgs);
    }

    apiFetch({
      path: addQueryArgs(path, queryArgs)
    }).then(items => {
      this.setState({
        items: items
      });
    }, error => {
      this.setState({
        error: error.message
      });
    });
  }

  selectItemName(event, itemID) {
    const {
      onSelectItem
    } = this.props;
    event.preventDefault();
    this.setState({
      search: '',
      items: [],
      error: ''
    });
    return onSelectItem({
      itemID: itemID
    });
  }

  render() {
    const {
      search,
      items
    } = this.state;
    let {
      ariaLabel,
      placeholder,
      useAvatar,
      slugValue
    } = this.props;
    let itemsList;

    if (!ariaLabel) {
      ariaLabel = __('Item\'s name', 'buddypress');
    }

    if (!placeholder) {
      placeholder = __('Enter Item\'s name here…', 'buddypress');
    }

    if (items.length) {
      itemsList = items.map(item => {
        return createElement("button", {
          type: "button",
          key: 'editor-autocompleters__item-item-' + item.id,
          role: "option",
          "aria-selected": "true",
          className: "components-button components-autocomplete__result editor-autocompleters__user",
          onClick: event => this.selectItemName(event, item.id)
        }, useAvatar && createElement("img", {
          key: "avatar",
          className: "editor-autocompleters__user-avatar",
          alt: "",
          src: item.avatar_urls.thumb.replaceAll('&#038;', '&')
        }), createElement("span", {
          key: "name",
          className: "editor-autocompleters__user-name"
        }, item.name), slugValue && null !== slugValue(item) && createElement("span", {
          key: "slug",
          className: "editor-autocompleters__user-slug"
        }, slugValue(item)));
      });
    }

    return createElement(Fragment, null, createElement("input", {
      type: "text",
      value: search,
      className: "components-placeholder__input",
      "aria-label": ariaLabel,
      placeholder: placeholder,
      onChange: event => this.searchItemName(event.target.value)
    }), 0 !== items.length && createElement(Popover, {
      className: "components-autocomplete__popover",
      focusOnMount: false,
      position: "bottom left"
    }, createElement("div", {
      className: "components-autocomplete__results"
    }, itemsList)));
  }

}

var _default = AutoCompleter;
exports.default = _default;
},{}],"iA92":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _autocompleter = _interopRequireDefault(require("./autocompleter"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * BuddyPress components.
 */
var _default = {
  AutoCompleter: _autocompleter.default
};
exports.default = _default;
},{"./autocompleter":"W80x"}],"Ee8M":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
Object.defineProperty(exports, "blockComponents", {
  enumerable: true,
  get: function () {
    return _components.default;
  }
});

var _components = _interopRequireDefault(require("./components"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
},{"./components":"iA92"}]},{},["Ee8M"], "bpBlock")