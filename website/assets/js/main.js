
document.addEventListener("DOMContentLoaded",()=>{
 const b=document.querySelector(".menu-btn"),n=document.querySelector(".nav-links");
 if(b&&n)b.addEventListener("click",()=>n.classList.toggle("open"));
 const y=document.querySelector("#year");if(y)y.textContent=new Date().getFullYear();
});
