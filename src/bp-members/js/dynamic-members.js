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
})({"k5We":[function(require,module,exports) {
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * WordPress dependencies.
 */
var _wp = wp,
    _wp$i18n = _wp.i18n,
    __ = _wp$i18n.__,
    sprintf = _wp$i18n.sprintf;
/**
 * BuddyPress dependencies.
 */

var _bp = bp,
    dynamicWidgetBlock = _bp.dynamicWidgetBlock;
/**
 * Front-end Dynamic Members Widget Block class.
 *
 * @since 9.0.0
 */

var bpMembersWidgetBlock = /*#__PURE__*/function (_dynamicWidgetBlock) {
  _inherits(bpMembersWidgetBlock, _dynamicWidgetBlock);

  var _super = _createSuper(bpMembersWidgetBlock);

  function bpMembersWidgetBlock() {
    _classCallCheck(this, bpMembersWidgetBlock);

    return _super.apply(this, arguments);
  }

  _createClass(bpMembersWidgetBlock, [{
    key: "loop",
    value: function loop() {
      var members = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
      var container = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
      var type = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'active';

      var tmpl = _get(_getPrototypeOf(bpMembersWidgetBlock.prototype), "useTemplate", this).call(this, 'bp-dynamic-members-item');

      var selector = document.querySelector('#' + container);
      var output = '';

      if (members && members.length) {
        members.forEach(function (member) {
          if ('active' === type && member.last_activity) {
            /* translators: %s: a human time diff. */
            member.extra = sprintf(__('Active %s', 'buddypress'), member.last_activity.timediff);
          } else if ('popular' === type && member.total_friend_count) {
            var friendsCount = parseInt(member.total_friend_count, 10);

            if (0 === friendsCount) {
              member.extra = __('No friends', 'buddypress');
            } else if (1 === friendsCount) {
              member.extra = __('1 friend', 'buddypress');
            } else {
              /* translators: %s: total friend count (more than 1). */
              member.extra = sprintf(__('%s friends', 'buddypress'), member.total_friend_count);
            }
          } else if ('newest' === type && member.registered_since) {
            /* translators: %s is time elapsed since the registration date happened */
            member.extra = sprintf(__('Registered %s', 'buddypress'), member.registered_since);
          }
          /* translators: %s: member name */


          member.avatar_alt = sprintf(__('Profile picture of %s', 'buddypress'), member.name);
          output += tmpl(member);
        });
      } else {
        output = '<div class="widget-error">' + __('No members found.', 'buddypress') + '</div>';
      }

      selector.innerHTML = output;
    }
  }, {
    key: "start",
    value: function start() {
      var _this = this;

      this.blocks.forEach(function (block, i) {
        var selector = block.selector;
        var type = block.query_args.type;
        var list = document.querySelector('#' + selector).closest('.bp-dynamic-block-container'); // Get default Block's type members.

        _get(_getPrototypeOf(bpMembersWidgetBlock.prototype), "getItems", _this).call(_this, type, i); // Listen to Block's Nav item clics


        list.querySelectorAll('.item-options a').forEach(function (navItem) {
          navItem.addEventListener('click', function (event) {
            event.preventDefault(); // Changes the displayed filter.

            event.target.closest('.item-options').querySelector('.selected').classList.remove('selected');
            event.target.classList.add('selected');
            var newType = event.target.getAttribute('data-bp-sort');

            if (newType !== _this.blocks[i].query_args.type) {
              _get(_getPrototypeOf(bpMembersWidgetBlock.prototype), "getItems", _this).call(_this, newType, i);
            }
          });
        });
      });
    }
  }]);

  return bpMembersWidgetBlock;
}(dynamicWidgetBlock);

var settings = window.bpDynamicMembersSettings || {};
var blocks = window.bpDynamicMembersBlocks || {};
var bpDynamicMembers = new bpMembersWidgetBlock(settings, blocks);

if ('loading' === document.readyState) {
  document.addEventListener('DOMContentLoaded', bpDynamicMembers.start());
} else {
  bpDynamicMembers.start();
}
},{}]},{},["k5We"], null)