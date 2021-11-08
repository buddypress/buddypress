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
})({"Sjre":[function(require,module,exports) {
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

/**
 * WordPress dependencies.
 */
const {
  element: {
    createElement,
    Fragment,
    useState
  },
  i18n: {
    __
  },
  components: {
    Placeholder,
    Disabled,
    SandBox,
    Button,
    ExternalLink,
    Spinner,
    ToolbarGroup,
    ToolbarButton
  },
  compose: {
    compose
  },
  data: {
    withSelect
  },
  blockEditor: {
    RichText,
    BlockControls
  }
} = wp;
/**
 * BuddyPress dependencies.
 */

const {
  blockData: {
    embedScriptURL
  }
} = bp;

const EditEmbedActivity = ({
  attributes,
  setAttributes,
  isSelected,
  preview,
  fetching
}) => {
  const {
    url,
    caption
  } = attributes;

  const label = __('BuddyPress Activity URL', 'buddypress');

  const [value, setURL] = useState(url);
  const [isEditingURL, setIsEditingURL] = useState(!url);

  const onSubmit = event => {
    if (event) {
      event.preventDefault();
    }

    setIsEditingURL(false);
    setAttributes({
      url: value
    });
  };

  const switchBackToURLInput = event => {
    if (event) {
      event.preventDefault();
    }

    setIsEditingURL(true);
  };

  const editToolbar = createElement(BlockControls, null, createElement(ToolbarGroup, null, createElement(ToolbarButton, {
    icon: "edit",
    title: __('Edit URL', 'buddypress'),
    onClick: switchBackToURLInput
  })));

  if (isEditingURL) {
    return createElement(Placeholder, {
      icon: "buddicons-activity",
      label: label,
      className: "wp-block-embed",
      instructions: __('Paste the link to the activity content you want to display on your site.', 'buddypress')
    }, createElement("form", {
      onSubmit: onSubmit
    }, createElement("input", {
      type: "url",
      value: value || '',
      className: "components-placeholder__input",
      "aria-label": label,
      placeholder: __('Enter URL to embed here…', 'buddypress'),
      onChange: event => setURL(event.target.value)
    }), createElement(Button, {
      isPrimary: true,
      type: "submit"
    }, __('Embed', 'buddypress'))), createElement("div", {
      className: "components-placeholder__learn-more"
    }, createElement(ExternalLink, {
      href: __('https://codex.buddypress.org/activity-embeds/')
    }, __('Learn more about activity embeds', 'buddypress'))));
  }

  if (fetching) {
    return createElement("div", {
      className: "wp-block-embed is-loading"
    }, createElement(Spinner, null), createElement("p", null, __('Embedding…', 'buddypress')));
  }

  if (!preview || !preview['x_buddypress'] || 'activity' !== preview['x_buddypress']) {
    return createElement(Fragment, null, editToolbar, createElement(Placeholder, {
      icon: "buddicons-activity",
      label: label
    }, createElement("p", {
      className: "components-placeholder__error"
    }, __('The URL you provided is not a permalink to a public BuddyPress Activity. Please use another URL.', 'buddypress'))));
  }

  return createElement(Fragment, null, !isEditingURL && editToolbar, createElement("figure", {
    className: "wp-block-embed is-type-bp-activity"
  }, createElement("div", {
    className: "wp-block-embed__wrapper"
  }, createElement(Disabled, null, createElement(SandBox, {
    html: preview && preview.html ? preview.html : '',
    scripts: [embedScriptURL]
  }))), (!RichText.isEmpty(caption) || isSelected) && createElement(RichText, {
    tagName: "figcaption",
    placeholder: __('Write caption…', 'buddypress'),
    value: caption,
    onChange: value => setAttributes({
      caption: value
    }),
    inlineToolbar: true
  })));
};

const editEmbedActivityBlock = compose([withSelect((select, ownProps) => {
  const {
    url
  } = ownProps.attributes;
  const {
    getEmbedPreview,
    isRequestingEmbedPreview
  } = select('core');
  const preview = !!url && getEmbedPreview(url);
  const fetching = !!url && isRequestingEmbedPreview(url);
  return {
    preview: preview,
    fetching: fetching
  };
})])(EditEmbedActivity);
var _default = editEmbedActivityBlock;
exports.default = _default;
},{}],"zmBI":[function(require,module,exports) {
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
    RichText
  },
  element: {
    createElement
  }
} = wp;

const saveEmbedActivityBlock = ({
  attributes
}) => {
  const {
    url,
    caption
  } = attributes;

  if (!url) {
    return null;
  }

  return createElement("figure", {
    className: "wp-block-embed is-type-bp-activity"
  }, createElement("div", {
    className: "wp-block-embed__wrapper"
  }, `\n${url}\n`
  /* URL needs to be on its own line. */
  ), !RichText.isEmpty(caption) && createElement(RichText.Content, {
    tagName: "figcaption",
    value: caption
  }));
};

var _default = saveEmbedActivityBlock;
exports.default = _default;
},{}],"hBDw":[function(require,module,exports) {
"use strict";

var _edit = _interopRequireDefault(require("./embed-activity/edit"));

var _save = _interopRequireDefault(require("./embed-activity/save"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * WordPress dependencies.
 */
const {
  i18n: {
    __
  },
  blocks: {
    registerBlockType
  }
} = wp;
/**
 * Internal dependencies.
 */

registerBlockType('bp/embed-activity', {
  title: __('Embed an activity', 'buddypress'),
  description: __('Add a block that displays the activity content pulled from this or other community sites.', 'buddypress'),
  icon: {
    background: '#fff',
    foreground: '#d84800',
    src: 'buddicons-activity'
  },
  category: 'buddypress',
  attributes: {
    url: {
      type: 'string'
    },
    caption: {
      type: 'string',
      source: 'html',
      selector: 'figcaption'
    }
  },
  supports: {
    align: true
  },
  edit: _edit.default,
  save: _save.default
});
},{"./embed-activity/edit":"Sjre","./embed-activity/save":"zmBI"}]},{},["hBDw"], null)