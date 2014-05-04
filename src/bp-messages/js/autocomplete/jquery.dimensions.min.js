/*!
 * Copyright (c) 2007 Paul Bakaus (paul.bakaus@googlemail.com) and Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * $LastChangedDate: 2007-09-11 05:38:31 +0300 (Вт, 11 сен 2007) $
 * $Rev: 3238 $
 *
 * Version: @VERSION
 *
 * Requires: jQuery 1.2+
 */
(function(b){b.dimensions={version:"@VERSION"};b.each(["Height","Width"],function(d,c){b.fn["inner"+c]=function(){if(!this[0]){return}var f=c=="Height"?"Top":"Left",e=c=="Height"?"Bottom":"Right";return this[c.toLowerCase()]()+a(this,"padding"+f)+a(this,"padding"+e)};b.fn["outer"+c]=function(f){if(!this[0]){return}var g=c=="Height"?"Top":"Left",e=c=="Height"?"Bottom":"Right";f=b.extend({margin:false},f||{});return this[c.toLowerCase()]()+a(this,"border"+g+"Width")+a(this,"border"+e+"Width")+a(this,"padding"+g)+a(this,"padding"+e)+(f.margin?(a(this,"margin"+g)+a(this,"margin"+e)):0)}});b.each(["Left","Top"],function(d,c){b.fn["scroll"+c]=function(e){if(!this[0]){return}return e!=undefined?this.each(function(){this==window||this==document?window.scrollTo(c=="Left"?e:b(window)["scrollLeft"](),c=="Top"?e:b(window)["scrollTop"]()):this["scroll"+c]=e}):this[0]==window||this[0]==document?self[(c=="Left"?"pageXOffset":"pageYOffset")]||b.boxModel&&document.documentElement["scroll"+c]||document.body["scroll"+c]:this[0]["scroll"+c]}});b.fn.extend({position:function(){var h=0,g=0,f=this[0],i,c,e,d;if(f){e=this.offsetParent();i=this.offset();c=e.offset();i.top-=a(f,"marginTop");i.left-=a(f,"marginLeft");c.top+=a(e,"borderTopWidth");c.left+=a(e,"borderLeftWidth");d={top:i.top-c.top,left:i.left-c.left}}return d},offsetParent:function(){var c=this[0].offsetParent;while(c&&(!/^body|html$/i.test(c.tagName)&&b.css(c,"position")=="static")){c=c.offsetParent}return b(c)}});var a=function(c,d){return parseInt(b.css(c.jquery?c[0]:c,d))||0}})(jQuery);