import{A as s}from"./index-08e160a7.js";import"./_commonjsHelpers-725317a4.js";/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license 
 */class a{constructor(){this.checkDbButton=document.getElementById("test-db-connection"),this.checkDbAlert=document.getElementById("database-response"),this.checkSmtpButton=document.getElementById("test-smtp-connection"),this.checkSmtpAlert=document.getElementById("smtp-response"),this.checkPdfButton=document.getElementById("test-pdf"),this.checkPdfAlert=document.getElementById("test-pdf-response")}handleDatabaseCheck(){let t=document.querySelector("meta[name=setup-db-check]").content,e={};document.querySelector('input[name="db_host"]')&&(e={db_host:document.querySelector('input[name="db_host"]').value,db_port:document.querySelector('input[name="db_port"]').value,db_database:document.querySelector('input[name="db_database"]').value,db_username:document.querySelector('input[name="db_username"]').value,db_password:document.querySelector('input[name="db_password"]').value}),this.checkDbButton.disabled=!0,s.post(t,e).then(c=>this.handleSuccess(this.checkDbAlert,"mail-wrapper")).catch(c=>this.handleFailure(this.checkDbAlert,c.response.data.message)).finally(()=>this.checkDbButton.disabled=!1)}handleSmtpCheck(){let t=document.querySelector("meta[name=setup-email-check]").content,e={mail_driver:document.querySelector('select[name="mail_driver"]').value,mail_name:document.querySelector('input[name="mail_name"]').value,mail_address:document.querySelector('input[name="mail_address"]').value,mail_username:document.querySelector('input[name="mail_username"]').value,mail_host:document.querySelector('input[name="mail_host"]').value,mail_port:document.querySelector('input[name="mail_port"]').value,encryption:document.querySelector('select[name="encryption"]').value,mail_password:document.querySelector('input[name="mail_password"]').value};if(this.checkSmtpButton.disabled=!0,e.mail_driver==="log")return this.handleSuccess(this.checkSmtpAlert,"account-wrapper"),this.handleSuccess(this.checkSmtpAlert,"submit-wrapper"),this.checkSmtpButton.disabled=!1;s.post(t,e).then(c=>{this.handleSuccess(this.checkSmtpAlert,"account-wrapper"),this.handleSuccess(this.checkSmtpAlert,"submit-wrapper")}).catch(c=>this.handleFailure(this.checkSmtpAlert,c.response.data.message)).finally(()=>this.checkSmtpButton.disabled=!1)}handleTestPdfCheck(){let t=document.querySelector("meta[name=setup-pdf-check]").content;this.checkPdfButton.disabled=!0,s.post(t,{}).then(e=>{try{return this.handleSuccess(this.checkPdfAlert,"database-wrapper")}catch{this.handleSuccess(this.checkPdfAlert,"database-wrapper"),this.checkPdfAlert.textContent="Success! PDF was generated succesfully."}}).catch(e=>{console.log(e),this.handleFailure(this.checkPdfAlert)}).finally(()=>this.checkPdfButton.disabled=!1)}handleSuccess(t,e=null){t.classList.remove("alert-failure"),t.innerText="Success!",t.classList.add("alert-success"),e&&(document.getElementById(e).classList.remove("hidden"),document.getElementById(e).scrollIntoView({behavior:"smooth",block:"center"}))}handleFailure(t,e=null){t.classList.remove("alert-success"),t.innerText=e||"Oops, looks like something isn't correct!",t.classList.add("alert-failure")}handle(){this.checkDbButton.addEventListener("click",()=>this.handleDatabaseCheck()),this.checkSmtpButton.addEventListener("click",()=>this.handleSmtpCheck()),this.checkPdfButton.addEventListener("click",()=>this.handleTestPdfCheck())}}new a().handle();
