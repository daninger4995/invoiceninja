window.appendToElement=function(e,t){var n=document.getElementById(e),a=n.querySelector('input[value="'.concat(t,'"]'));if(a)return a.remove();var r=document.createElement("INPUT");r.setAttribute("name","file_hash[]"),r.setAttribute("value",t),r.hidden=!0,n.append(r)};