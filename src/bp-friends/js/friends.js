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
})({"pMhP":[function(require,module,exports) {
/**
 * WordPress dependencies.
 */
const {
  i18n: {
    __,
    sprintf
  }
} = wp;
/**
 * BuddyPress dependencies.
 */

const {
  dynamicWidgetBlock
} = bp;
/**
 * Front-end Friends block class.
 */

class bpFriendsWidgetBlock extends dynamicWidgetBlock {
  loop(friends = [], container = '', type = 'active') {
    const tmpl = super.useTemplate('bp-friends-item');
    const selector = document.querySelector('#' + container);
    let output = '';

    if (friends && friends.length) {
      friends.forEach(friend => {
        if ('active' === type && friend.last_activity) {
          /* translators: %s: a human time diff. */
          friend.extra = sprintf(__('Active %s', 'buddypress'), friend.last_activity.timediff);
        } else if ('popular' === type && friend.total_friend_count) {
          const friendsCount = parseInt(friend.total_friend_count, 10);

          if (0 === friendsCount) {
            friend.extra = __('No friends', 'buddypress');
          } else if (1 === friendsCount) {
            friend.extra = __('1 friend', 'buddypress');
          } else {
            /* translators: %s: total friend count (more than 1). */
            friend.extra = sprintf(__('%s friends', 'buddypress'), friend.total_friend_count);
          }
        } else if ('newest' === type && friend.registered_since) {
          /* translators: %s is time elapsed since the registration date happened */
          friend.extra = sprintf(__('Registered %s', 'buddypress'), friend.registered_since);
        }
        /* translators: %s: member name */


        friend.avatar_alt = sprintf(__('Profile picture of %s', 'buddypress'), friend.name);
        output += tmpl(friend);
      });
    } else {
      output = '<div class="widget-error">' + __('Sorry, no members were found.', 'buddypress') + '</div>';
    }

    selector.innerHTML = output;
  }

  start() {
    this.blocks.forEach((block, i) => {
      const {
        selector
      } = block;
      const {
        type
      } = block.query_args;
      const list = document.querySelector('#' + selector).closest('.bp-dynamic-block-container'); // Get default Block's type friends.

      super.getItems(type, i); // Listen to Block's Nav item clics

      list.querySelectorAll('.item-options a').forEach(navItem => {
        navItem.addEventListener('click', event => {
          event.preventDefault(); // Changes the displayed filter.

          event.target.closest('.item-options').querySelector('.selected').classList.remove('selected');
          event.target.classList.add('selected');
          const newType = event.target.getAttribute('data-bp-sort');

          if (newType !== this.blocks[i].query_args.type) {
            super.getItems(newType, i);
          }
        });
      });
    });
  }

}

const settings = window.bpFriendsSettings || {};
const blocks = window.bpFriendsBlocks || {};
const bpFriends = new bpFriendsWidgetBlock(settings, blocks);

if ('loading' === document.readyState) {
  document.addEventListener('DOMContentLoaded', bpFriends.start());
} else {
  bpFriends.start();
}
},{}]},{},["pMhP"], null)