const sidebar=document.getElementById("sidebar");
const menuBtn=document.getElementById("menuBtn");
const overlay=document.getElementById("overlay");
if(menuBtn&&sidebar&&overlay){
menuBtn.addEventListener("click",()=>{sidebar.classList.add("open");overlay.classList.add("show");});
overlay.addEventListener("click",()=>{sidebar.classList.remove("open");overlay.classList.remove("show");});
}

document.querySelectorAll("[data-scroll-target]").forEach((button)=>{
button.addEventListener("click",()=>{
const targetId=button.getAttribute("data-scroll-target");
const direction=button.getAttribute("data-scroll-dir")==="up"?-1:1;
const container=targetId?document.getElementById(targetId):null;
if(!container)return;
container.scrollBy({top:direction*180,behavior:"smooth"});
});
});
