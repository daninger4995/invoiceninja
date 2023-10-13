var r=Object.defineProperty;var d=(n,t,e)=>t in n?r(n,t,{enumerable:!0,configurable:!0,writable:!0,value:e}):n[t]=e;var o=(n,t,e)=>(d(n,typeof t!="symbol"?t+"":t,e),e);/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license 
 */class i{constructor(t,e){o(this,"setupStripe",()=>{this.stripeConnect?this.stripe=Stripe(this.key,{stripeAccount:this.stripeConnect}):this.stripe=Stripe(this.key);const t=this.stripe.elements(),s={style:{base:{color:"#32325d",fontSize:"16px","::placeholder":{color:"#aab7c4"},":-webkit-autofill":{color:"#32325d"}},invalid:{color:"#fa755a",iconColor:"#fa755a",":-webkit-autofill":{color:"#fa755a"}}},disabled:!1,hideIcon:!1,iconStyle:"default"};return this.auBankAccount=t.create("auBankAccount",s),this.auBankAccount.mount("#becs-iban"),this});o(this,"handle",()=>{document.getElementById("pay-now").addEventListener("click",t=>{let e=document.getElementById("errors");if(document.getElementById("becs-name").value===""){document.getElementById("becs-name").focus(),e.textContent=document.querySelector("meta[name=translation-name-required]").content,e.hidden=!1;return}if(document.getElementById("becs-email-address").value===""){document.getElementById("becs-email-address").focus(),e.textContent=document.querySelector("meta[name=translation-email-required]").content,e.hidden=!1;return}if(!document.getElementById("becs-mandate-acceptance").checked){document.getElementById("becs-mandate-acceptance").focus(),e.textContent=document.querySelector("meta[name=translation-terms-required]").content,e.hidden=!1,console.log("Terms");return}document.getElementById("pay-now").disabled=!0,document.querySelector("#pay-now > svg").classList.remove("hidden"),document.querySelector("#pay-now > span").classList.add("hidden"),this.stripe.confirmAuBecsDebitPayment(document.querySelector("meta[name=pi-client-secret").content,{payment_method:{au_becs_debit:this.auBankAccount,billing_details:{name:document.getElementById("becs-name").value,email:document.getElementById("becs-email-address").value}}}).then(s=>s.error?this.handleFailure(s.error.message):this.handleSuccess(s))})});this.key=t,this.errors=document.getElementById("errors"),this.stripeConnect=e}handleSuccess(t){document.querySelector('input[name="gateway_response"]').value=JSON.stringify(t.paymentIntent),document.getElementById("server-response").submit()}handleFailure(t){let e=document.getElementById("errors");e.textContent="",e.textContent=t,e.hidden=!1,document.getElementById("pay-now").disabled=!1,document.querySelector("#pay-now > svg").classList.add("hidden"),document.querySelector("#pay-now > span").classList.remove("hidden")}}var a;const l=((a=document.querySelector('meta[name="stripe-publishable-key"]'))==null?void 0:a.content)??"";var c;const m=((c=document.querySelector('meta[name="stripe-account-id"]'))==null?void 0:c.content)??"";new i(l,m).setupStripe().handle();
