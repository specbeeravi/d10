!function(t,e){"object"==typeof exports&&"object"==typeof module?module.exports=e():"function"==typeof define&&define.amd?define([],e):"object"==typeof exports?exports.CKEditor5=e():(t.CKEditor5=t.CKEditor5||{},t.CKEditor5.bootstrapGrid=e())}(self,(()=>(()=>{var t={"ckeditor5/src/core.js":(t,e,o)=>{t.exports=o("dll-reference CKEditor5.dll")("./src/core.js")},"ckeditor5/src/ui.js":(t,e,o)=>{t.exports=o("dll-reference CKEditor5.dll")("./src/ui.js")},"ckeditor5/src/widget.js":(t,e,o)=>{t.exports=o("dll-reference CKEditor5.dll")("./src/widget.js")},"dll-reference CKEditor5.dll":t=>{"use strict";t.exports=CKEditor5.dll}},e={};function o(r){var i=e[r];if(void 0!==i)return i.exports;var s=e[r]={exports:{}};return t[r](s,s.exports,o),s.exports}o.d=(t,e)=>{for(var r in e)o.o(e,r)&&!o.o(t,r)&&Object.defineProperty(t,r,{enumerable:!0,get:e[r]})},o.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e);var r={};return(()=>{"use strict";o.d(r,{default:()=>g});var t=o("ckeditor5/src/core.js"),e=o("ckeditor5/src/widget.js");function i(t){return(0,e.isWidget)(t)&&!!t.getCustomProperty("bsGrid")}function s(t){const e=t.getSelectedElement();if(e&&i(e))return e;if(null===t.getFirstPosition())return null;let{parent:o}=t.getFirstPosition();for(;o;){if(o.is("element")&&i(o))return o;o=o.parent}return null}function n(t,e,o){o=o||!1;let r="";if("function"==typeof t.getAttribute?r=t.getAttribute("class"):"string"==typeof t.className&&(r=t.className),!r)return"";const i=r.split(" ").filter((t=>0!==t.lastIndexOf("ck-widget",0)&&0!==t.lastIndexOf("ck-edit",0)&&0!==t.lastIndexOf("bsg-",0)&&(o?0===t.lastIndexOf(e,0):0!==t.lastIndexOf(e,0))));return i.length?i.join(" ").trim():""}function a(t){const o={};let r=!1;o.container_wrapper_class=n(t,"bs_grid");const i=t.getChild(0);if(s=i,(0,e.isWidget)(s)&&s.getCustomProperty("bsGridContainer")){o.add_container=1,o.container_class=n(i,"container");const t=n(i,"container",!0);t.length&&(-1!==t.indexOf("container-fluid")?o.container_type="fluid":o.container_type="default"),r=i.getChild(0)}else r=i;var s;const a=n(r,"row");return o.no_gutter=-1!==a.indexOf("no-gutters")?1:0,o.row_class=a.replace("no-gutters","").replace("g-0",""),o.breakpoints={none:{layout:r.getAttribute("data-row-none")},sm:{layout:r.getAttribute("data-row-sm")},md:{layout:r.getAttribute("data-row-md")},lg:{layout:r.getAttribute("data-row-lg")},xl:{layout:r.getAttribute("data-row-xl")},xxl:{layout:r.getAttribute("data-row-xxl")}},o.num_columns=0,Array.from(r.getChildren()).forEach(((t,e)=>{if(function(t){return!!t.getCustomProperty("bsGridCol")}(t)){o.num_columns+=1;const r=n(t,"col");o[`col_${e+1}_classes`]=r}})),o}class l extends t.Command{execute(t){const{model:e}=this.editor,o=function(t){const e=t.getSelectedElement();return(o=e)&&o.is("element","bsGrid")?e:t.getFirstPosition().findAncestor("bsGrid");var o}(e.document.selection);e.change((r=>{o?function(t,e,o){let r=!1,i=!1;const s={class:"bs_grid"};void 0!==o.container_wrapper_class&&(s.class+=` ${o.container_wrapper_class}`),s.class=s.class.trim(),t.setAttributes(s,e);const n=e.getChild(0);var a;(a=n)&&a.is("element","bsGridContainer")?(r=n,i=n.getChild(0)):i=n;const l={class:o.row_class.trim(),"data-row-none":o.breakpoints.none?o.breakpoints.none.layout:"","data-row-sm":o.breakpoints.sm?o.breakpoints.sm.layout:"","data-row-md":o.breakpoints.md?o.breakpoints.md.layout:"","data-row-lg":o.breakpoints.lg?o.breakpoints.lg.layout:"","data-row-xl":o.breakpoints.xl?o.breakpoints.xl.layout:"","data-row-xxl":o.breakpoints.xxl?o.breakpoints.xxl.layout:""};t.setAttributes(l,i);for(let e=1;e<=o.num_columns;e++){const r=`col_${e}_classes`;t.setAttributes({class:o[r]},i.getChild(e-1))}if(o.add_container){const s={class:o.container_class.trim()};r?t.setAttributes(s,r):(r=t.createElement("bsGridContainer",s),t.append(r,e),t.append(i,r))}else r&&t.unwrap(r)}(r,o,t):e.insertContent(function(t,e){const o={class:"bs_grid"};void 0!==e.container_wrapper_class&&(o.class+=` ${e.container_wrapper_class}`);const r=t.createElement("bsGrid",o),i={class:e.row_class.trim(),"data-row-none":e.breakpoints.none?e.breakpoints.none.layout:"","data-row-sm":e.breakpoints.sm?e.breakpoints.sm.layout:"","data-row-md":e.breakpoints.md?e.breakpoints.md.layout:"","data-row-lg":e.breakpoints.lg?e.breakpoints.lg.layout:"","data-row-xl":e.breakpoints.xl?e.breakpoints.xl.layout:"","data-row-xxl":e.breakpoints.xxl?e.breakpoints.xxl.layout:""},s=t.createElement("bsGridRow",i);for(let o=1;o<=e.num_columns;o++){const r=`col_${o}_classes`,i=t.createElement("bsGridCol",{class:e[r]}),n=t.createElement("paragraph");t.insertText(`Column ${o} content`,n),t.append(n,i),t.append(i,s)}if(e.add_container){const o={class:e.container_class.trim()},i=t.createElement("bsGridContainer",o);t.append(i,r),t.append(s,i)}else t.append(s,r);return r}(r,t))}))}refresh(){const{model:t}=this.editor,{selection:e}=t.document,o=t.schema.findAllowedParent(e.getFirstPosition(),"bsGrid");this.isEnabled=null!==o}}class d extends t.Plugin{static get requires(){return[e.Widget]}static get pluginName(){return"BootstrapGridEditing"}constructor(t){super(t),this.attrs={class:"class","data-row-none":"data-row-none","data-row-sm":"data-row-sm","data-row-md":"data-row-md","data-row-lg":"data-row-lg","data-row-xl":"data-row-xl","data-row-xxl":"data-row-xxl"}}init(){this.editor.config.get("bootstrapGrid")&&(this._defineSchema(),this._defineConverters(),this._defineCommands())}_defineSchema(){const{schema:t}=this.editor.model;t.register("bsGrid",{allowWhere:"$block",isLimit:!0,isObject:!0,allowAttributes:["class"]}),t.register("bsGridContainer",{isLimit:!0,allowIn:"bsGrid",isInline:!0,allowAttributes:["class"]}),t.register("bsGridRow",{isLimit:!0,allowIn:["bsGrid","bsGridContainer"],isInline:!0,allowAttributes:Object.keys(this.attrs)}),t.register("bsGridCol",{allowIn:"bsGridRow",isInline:!0,allowContentOf:"$root",allowAttributes:["class"]})}_defineConverters(){const{conversion:t}=this.editor;t.for("upcast").elementToElement({model:"bsGrid",view:{name:"div",classes:"bs_grid"}}),t.for("downcast").elementToElement({model:"bsGrid",view:(t,{writer:o})=>{const r=o.createContainerElement("div");return o.setCustomProperty("bsGrid",!0,r),(0,e.toWidget)(r,o,{label:"BS Grid"})}}),t.for("upcast").elementToElement({model:"bsGridContainer",view:{name:"div"}}),t.for("downcast").elementToElement({model:"bsGridContainer",view:(t,{writer:o})=>{const r=o.createContainerElement("div");return o.setCustomProperty("bsGridContainer",!0,r),(0,e.toWidget)(r,o,{label:"BS Grid Container"})}}),t.for("upcast").elementToElement({model:"bsGridRow",view:{name:"div",classes:["row"]},converterPriority:"high"}),t.for("downcast").elementToElement({model:"bsGridRow",view:(t,{writer:o})=>{const r={"data-row-none":t.getAttribute("data-row-none"),"data-row-sm":t.getAttribute("data-row-sm"),"data-row-md":t.getAttribute("data-row-md"),"data-row-lg":t.getAttribute("data-row-lg"),"data-row-xl":t.getAttribute("data-row-xl"),"data-row-xxl":t.getAttribute("data-row-xxl")},i=o.createContainerElement("div",r);return o.setCustomProperty("bsGridRow",!0,i),(0,e.toWidget)(i,o,{label:"BS Grid Row"})}}),t.for("upcast").elementToElement({model:"bsGridCol",view:{name:"div"}}),t.for("editingDowncast").elementToElement({model:"bsGridCol",view:(t,{writer:o})=>{const r=o.createEditableElement("div");return o.setCustomProperty("bsGridCol",!0,r),(0,e.toWidgetEditable)(r,o)}}),t.for("dataDowncast").elementToElement({model:"bsGridCol",view:{name:"div",classes:""}}),Object.keys(this.attrs).forEach((e=>{const o={model:{key:e,name:"bsGridRow"},view:{name:"div",key:this.attrs[e]}};t.for("downcast").attributeToAttribute(o),t.for("upcast").attributeToAttribute(o)})),t.attributeToAttribute({model:"class",view:"class"})}_defineCommands(){this.editor.commands.add("insertBootstrapGrid",new l(this.editor))}}var c=o("ckeditor5/src/ui.js");class u extends t.Plugin{init(){const{editor:t}=this,e=t.config.get("bootstrapGrid");if(!e)return;const{dialogURL:o,openDialog:r,dialogSettings:i={}}=e;o&&"function"==typeof r&&t.ui.componentFactory.add("bootstrapGrid",(e=>{const s=t.commands.get("insertBootstrapGrid"),n=new c.ButtonView(e);return n.set({label:t.t("Bootstrap Grid"),icon:'<?xml version="1.0" standalone="yes"?>\n<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16">\n<path style="fill:#ffffff; stroke:none;" d="M0 0L0 16L16 16L16 0L0 0z"/>\n<path style="fill:#010101; stroke:none;" d="M1 3L1 6L10 6L10 3L1 3M11 3L11 6L15 6L15 3L11 3M1 7L1 10L5 10L5 7L1 7M6 7L6 10L10 10L10 7L6 7M11 7L11 10L15 10L15 7L11 7M1 11L1 14L15 14L15 11L1 11z"/>\n</svg>\n',tooltip:!0}),n.bind("isOn","isEnabled").to(s,"value","isEnabled"),this.listenTo(n,"execute",(()=>{r(o,(({settings:e})=>{t.execute("insertBootstrapGrid",e)}),i)})),n}))}}class m extends t.Plugin{static get requires(){return[e.WidgetToolbarRepository]}static get pluginName(){return"BootstrapGridToolbar"}init(){const{editor:e}=this,o=e.config.get("bootstrapGrid");if(!o)return;const{dialogURL:r,openDialog:i,dialogSettings:n={}}=o;r&&"function"==typeof i&&e.ui.componentFactory.add("bootstrapGridEdit",(o=>{const i=new c.ButtonView(o);return i.set({label:e.t("Edit Grid"),icon:t.icons.pencil,tooltip:!0,withText:!0}),this.listenTo(i,"execute",(()=>{let t={};const{selection:o}=e.editing.view.document,i=s(o);i&&(t=a(i),t.saved=1),this._openDialog(r,t,(({settings:t})=>{e.execute("insertBootstrapGrid",t),e.editing.view.focus()}),n)})),i}))}afterInit(){const{editor:t}=this;t.plugins.get("WidgetToolbarRepository").register("bootstrapGrid",{items:["bootstrapGridEdit"],getRelatedElement:t=>s(t)})}_openDialog(t,e,o,r){const i=r.dialogClass?r.dialogClass.split(" "):[];i.push("ui-dialog--narrow"),r.dialogClass=i.join(" "),r.autoResize=window.matchMedia("(min-width: 600px)").matches,r.width="auto";Drupal.ajax({dialog:r,dialogType:"modal",selector:".ckeditor5-dialog-loading-link",url:t,progress:{type:"fullscreen"},submit:{editor_object:e}}).execute(),Drupal.ckeditor5.saveCallback=o}}class p extends t.Plugin{static get requires(){return[d,u,m]}static get pluginName(){return"BootstrapGrid"}}const g={BootstrapGrid:p}})(),r=r.default})()));