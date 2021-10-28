/*! For license information please see stripe-sofort.js.LICENSE.txt */
(()=>{var e,t,n,r;function o(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}var c=null!==(e=null===(t=document.querySelector('meta[name="stripe-publishable-key"]'))||void 0===t?void 0:t.content)&&void 0!==e?e:"",i=null!==(n=null===(r=document.querySelector('meta[name="stripe-account-id"]'))||void 0===r?void 0:r.content)&&void 0!==n?n:"";new function e(t,n){var r=this;!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),o(this,"setupStripe",(function(){return r.stripe=Stripe(r.key),r.stripeConnect&&(r.stripe.stripeAccount=i),r})),o(this,"handle",(function(){document.getElementById("pay-now").addEventListener("click",(function(e){document.getElementById("pay-now").disabled=!0,document.querySelector("#pay-now > svg").classList.remove("hidden"),document.querySelector("#pay-now > span").classList.add("hidden"),r.stripe.confirmSofortPayment(document.querySelector("meta[name=pi-client-secret").content,{payment_method:{sofort:{country:document.querySelector('meta[name="country"]').content}},return_url:document.querySelector('meta[name="return-url"]').content})}))})),this.key=t,this.errors=document.getElementById("errors"),this.stripeConnect=n}(c,i).setupStripe().handle()})();