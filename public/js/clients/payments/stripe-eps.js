/*! For license information please see stripe-eps.js.LICENSE.txt */
(()=>{var e,t,n,r;function o(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}function i(e,t,n){return t&&o(e.prototype,t),n&&o(e,n),Object.defineProperty(e,"prototype",{writable:!1}),e}function a(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}var c=i((function e(t,n){var r=this;!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),a(this,"setupStripe",(function(){r.stripeConnect?r.stripe=Stripe(r.key,{stripeAccount:r.stripeConnect}):r.stripe=Stripe(r.key);var e=r.stripe.elements();return r.eps=e.create("epsBank",{style:{base:{padding:"10px 12px",color:"#32325d",fontSize:"16px","::placeholder":{color:"#aab7c4"}}}}),r.eps.mount("#eps-bank-element"),r})),a(this,"handle",(function(){document.getElementById("pay-now").addEventListener("click",(function(e){var t=document.getElementById("errors");if(!document.getElementById("eps-name").value)return t.textContent=document.querySelector("meta[name=translation-name-required]").content,void(t.hidden=!1);document.getElementById("pay-now").disabled=!0,document.querySelector("#pay-now > svg").classList.remove("hidden"),document.querySelector("#pay-now > span").classList.add("hidden"),r.stripe.confirmEpsPayment(document.querySelector("meta[name=pi-client-secret").content,{payment_method:{eps:r.eps,billing_details:{name:document.getElementById("eps-name").value}},return_url:document.querySelector('meta[name="return-url"]').content})}))})),this.key=t,this.errors=document.getElementById("errors"),this.stripeConnect=n}));new c(null!==(e=null===(t=document.querySelector('meta[name="stripe-publishable-key"]'))||void 0===t?void 0:t.content)&&void 0!==e?e:"",null!==(n=null===(r=document.querySelector('meta[name="stripe-account-id"]'))||void 0===r?void 0:r.content)&&void 0!==n?n:"").setupStripe().handle()})();