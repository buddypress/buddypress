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
})({"UOvc":[function(require,module,exports) {
/**
 * WordPress dependencies
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
 * Front-end Dynamic Groups Widget Block class.
 *
 * @since 9.0.0
 */

class bpGroupsWidgetBlock extends dynamicWidgetBlock {
  loop(groups = [], container = '', type = 'active') {
    const tmpl = super.useTemplate('bp-dynamic-groups-item');
    const selector = document.querySelector('#' + container);
    let output = '';

    if (groups && groups.length) {
      groups.forEach(group => {
        if ('newest' === type && group.created_since) {
          /* translators: %s is time elapsed since the group was created */
          group.extra = sprintf(__('Created %s', 'buddypress'), group.created_since);
        } else if ('popular' === type && group.total_member_count) {
          const membersCount = parseInt(group.total_member_count, 10);

          if (0 === membersCount) {
            group.extra = __('No members', 'buddypress');
          } else if (1 === membersCount) {
            group.extra = __('1 member', 'buddypress');
          } else {
            /* translators: %s is the number of Group members (more than 1). */
            group.extra = sprintf(__('%s members', 'buddypress'), group.total_member_count);
          }
        } else {
          /* translators: %s: a human time diff. */
          group.extra = sprintf(__('Active %s', 'buddypress'), group.last_activity_diff);
        }
        /* Translators: %s is the group's name. */


        group.avatar_alt = sprintf(__('Group Profile photo of %s', 'buddypress'), group.name);
        output += tmpl(group);
      });
    } else {
      output = '<div class="widget-error">' + __('There are no groups to display.', 'buddypress') + '</div>';
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
      const list = document.querySelector('#' + selector).closest('.bp-dynamic-block-container'); // Get default Block's type groups.

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

const settings = window.bpDynamicGroupsSettings || {};
const blocks = window.bpDynamicGroupsBlocks || [];
const bpDynamicGroups = new bpGroupsWidgetBlock(settings, blocks);

if ('loading' === document.readyState) {
  document.addEventListener('DOMContentLoaded', bpDynamicGroups.start());
} else {
  bpDynamicGroups.start();
}
},{}]},{},["UOvc"], null)