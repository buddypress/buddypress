(()=>{"use strict";const e=window.wp.i18n,t=window.wp.escapeHtml,s=window.bp.dynamicWidgetBlock;class r extends s.dynamicWidgetBlock{loop(s=[],r="",a="active"){const o=super.useTemplate("bp-dynamic-groups-item"),c=document.querySelector("#"+r);let n="";s&&s.length?s.forEach((s=>{if("newest"===a&&s.created_since)
/* translators: %s is time elapsed since the group was created */
s.extra=(0,t.escapeHTML)((0,e.sprintf)((0,e.__)("Created %s","buddypress"),s.created_since));else if("popular"===a&&s.total_member_count){const r=parseInt(s.total_member_count,10);s.extra=0===r?(0,t.escapeHTML)((0,e.__)("No members","buddypress")):1===r?(0,t.escapeHTML)((0,e.__)("1 member","buddypress")):(0,t.escapeHTML)((0,e.sprintf)(/* translators: %s is the number of Group members (more than 1). */
(0,e.__)("%s members","buddypress"),s.total_member_count))}else
/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
s.extra=(0,t.escapeHTML)((0,e.sprintf)((0,e.__)("Active %s","buddypress"),s.last_activity_diff));s.name=(0,t.escapeHTML)(s.name),
/* Translators: %s is the group's name. */
s.avatar_alt=(0,t.escapeAttribute)((0,e.sprintf)((0,e.__)("Group Profile photo of %s","buddypress"),s.name)),n+=o(s)})):n='<div class="widget-error">'+(0,e.__)("There are no groups to display.","buddypress")+"</div>",c.innerHTML=n}start(){this.blocks.forEach(((e,t)=>{const{selector:s}=e,{type:r}=e.query_args,a=document.querySelector("#"+s).closest(".bp-dynamic-block-container");super.getItems(r,t),a.querySelectorAll(".item-options a").forEach((e=>{e.addEventListener("click",(e=>{e.preventDefault(),e.target.closest(".item-options").querySelector(".selected").classList.remove("selected"),e.target.classList.add("selected");const s=e.target.getAttribute("data-bp-sort");s!==this.blocks[t].query_args.type&&super.getItems(s,t)}))}))}))}}const a=new r(window.bpDynamicGroupsSettings||{},window.bpDynamicGroupsBlocks||[]);"loading"===document.readyState?document.addEventListener("DOMContentLoaded",a.start()):a.start()})();