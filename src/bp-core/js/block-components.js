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
})({"IC7x":[function(require,module,exports) {
function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck;
},{}],"WiqS":[function(require,module,exports) {
function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

module.exports = _createClass;
},{}],"NS7G":[function(require,module,exports) {
function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

module.exports = _assertThisInitialized;
},{}],"zqo5":[function(require,module,exports) {
function _setPrototypeOf(o, p) {
  module.exports = _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  return _setPrototypeOf(o, p);
}

module.exports = _setPrototypeOf;
},{}],"RISo":[function(require,module,exports) {
var setPrototypeOf = require("./setPrototypeOf");

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }

  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  if (superClass) setPrototypeOf(subClass, superClass);
}

module.exports = _inherits;
},{"./setPrototypeOf":"zqo5"}],"xOn8":[function(require,module,exports) {
function _typeof(obj) {
  "@babel/helpers - typeof";

  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    module.exports = _typeof = function _typeof(obj) {
      return typeof obj;
    };
  } else {
    module.exports = _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };
  }

  return _typeof(obj);
}

module.exports = _typeof;
},{}],"oXYo":[function(require,module,exports) {
var _typeof = require("../helpers/typeof");

var assertThisInitialized = require("./assertThisInitialized");

function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  }

  return assertThisInitialized(self);
}

module.exports = _possibleConstructorReturn;
},{"../helpers/typeof":"xOn8","./assertThisInitialized":"NS7G"}],"goD2":[function(require,module,exports) {
function _getPrototypeOf(o) {
  module.exports = _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  return _getPrototypeOf(o);
}

module.exports = _getPrototypeOf;
},{}],"W80x":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies.
 */
var _wp = wp,
    apiFetch = _wp.apiFetch,
    Popover = _wp.components.Popover,
    _wp$element = _wp.element,
    Component = _wp$element.Component,
    Fragment = _wp$element.Fragment,
    createElement = _wp$element.createElement,
    __ = _wp.i18n.__,
    addQueryArgs = _wp.url.addQueryArgs;

var AutoCompleter = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(AutoCompleter, _Component);

  var _super = _createSuper(AutoCompleter);

  function AutoCompleter() {
    var _this;

    (0, _classCallCheck2.default)(this, AutoCompleter);
    _this = _super.apply(this, arguments);
    _this.state = {
      search: '',
      items: [],
      error: ''
    };
    _this.searchItemName = _this.searchItemName.bind((0, _assertThisInitialized2.default)(_this));
    _this.selectItemName = _this.selectItemName.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(AutoCompleter, [{
    key: "searchItemName",
    value: function searchItemName(value) {
      var _this2 = this;

      var search = this.state.search;
      var _this$props = this.props,
          component = _this$props.component,
          objectQueryArgs = _this$props.objectQueryArgs;
      this.setState({
        search: value
      });

      if (value.length < search.length) {
        this.setState({
          items: []
        });
      }

      var path = '/buddypress/v1/' + component;
      var queryArgs = {};

      if (value) {
        queryArgs.search = encodeURIComponent(value);
      }

      if (objectQueryArgs) {
        queryArgs = Object.assign(queryArgs, objectQueryArgs);
      }

      apiFetch({
        path: addQueryArgs(path, queryArgs)
      }).then(function (items) {
        _this2.setState({
          items: items
        });
      }, function (error) {
        _this2.setState({
          error: error.message
        });
      });
    }
  }, {
    key: "selectItemName",
    value: function selectItemName(event, itemID) {
      var onSelectItem = this.props.onSelectItem;
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
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;

      var _this$state = this.state,
          search = _this$state.search,
          items = _this$state.items;
      var _this$props2 = this.props,
          ariaLabel = _this$props2.ariaLabel,
          placeholder = _this$props2.placeholder,
          useAvatar = _this$props2.useAvatar,
          slugValue = _this$props2.slugValue;
      var itemsList;

      if (!ariaLabel) {
        ariaLabel = __('Item\'s name', 'buddypress');
      }

      if (!placeholder) {
        placeholder = __('Enter Item\'s name here…', 'buddypress');
      }

      if (items.length) {
        itemsList = items.map(function (item) {
          return createElement("button", {
            type: "button",
            key: 'editor-autocompleters__item-item-' + item.id,
            role: "option",
            "aria-selected": "true",
            className: "components-button components-autocomplete__result editor-autocompleters__user",
            onClick: function onClick(event) {
              return _this3.selectItemName(event, item.id);
            }
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
        onChange: function onChange(event) {
          return _this3.searchItemName(event.target.value);
        }
      }), 0 !== items.length && createElement(Popover, {
        className: "components-autocomplete__popover",
        focusOnMount: false,
        position: "bottom left"
      }, createElement("div", {
        className: "components-autocomplete__results"
      }, itemsList)));
    }
  }]);
  return AutoCompleter;
}(Component);

var _default = AutoCompleter;
exports.default = _default;
},{"@babel/runtime/helpers/classCallCheck":"IC7x","@babel/runtime/helpers/createClass":"WiqS","@babel/runtime/helpers/assertThisInitialized":"NS7G","@babel/runtime/helpers/inherits":"RISo","@babel/runtime/helpers/possibleConstructorReturn":"oXYo","@babel/runtime/helpers/getPrototypeOf":"goD2"}],"fOJU":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ServerSideRender;

/**
 * WordPress dependencies.
 */
var _wp = wp,
    createElement = _wp.element.createElement;
/**
 * Compatibility Server Side Render.
 *
 * @since 9.0.0
 */

function ServerSideRender(props) {
  var CompatibiltyServerSideRender = wp.serverSideRender ? wp.serverSideRender : wp.editor.ServerSideRender;
  return createElement(CompatibiltyServerSideRender, props);
}
},{}],"iA92":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _autocompleter = _interopRequireDefault(require("./autocompleter"));

var _serverSideRender = _interopRequireDefault(require("./server-side-render"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * BuddyPress components.
 */
var _default = {
  AutoCompleter: _autocompleter.default,
  ServerSideRender: _serverSideRender.default
};
exports.default = _default;
},{"./autocompleter":"W80x","./server-side-render":"fOJU"}],"Ee8M":[function(require,module,exports) {
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