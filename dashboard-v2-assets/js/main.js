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

const chartAreas=document.querySelectorAll(".chart-area");
const hideChartTooltips=()=>{
document.querySelectorAll(".chart-tooltip").forEach((tooltip)=>{
tooltip.hidden=true;
tooltip.classList.remove("show","below");
tooltip.setAttribute("aria-hidden","true");
});
document.querySelectorAll(".chart-dot.is-active").forEach((dot)=>{
dot.classList.remove("is-active");
});
};

chartAreas.forEach((area)=>{
const tooltip=area.querySelector(".chart-tooltip");
const dots=area.querySelectorAll(".chart-dot");
if(!tooltip||dots.length===0)return;

const showTooltip=(dot)=>{
const svg=area.querySelector("svg");
if(!svg)return;

const dotRect=dot.getBoundingClientRect();
const areaRect=area.getBoundingClientRect();
const day=dot.dataset.day||"--";
const valueLabel=dot.dataset.valueLabel||"0";
const isSameDot=dot.classList.contains("is-active")&&!tooltip.hidden;

hideChartTooltips();
if(isSameDot)return;

dot.classList.add("is-active");
tooltip.innerHTML=`<strong>${day}</strong><span>Value: ${valueLabel}</span>`;
tooltip.hidden=false;
tooltip.setAttribute("aria-hidden","false");
tooltip.classList.add("show");

const tooltipWidth=tooltip.offsetWidth;
const tooltipHeight=tooltip.offsetHeight;
const minLeft=(tooltipWidth/2)+10;
const maxLeft=areaRect.width-(tooltipWidth/2)-10;
const rawLeft=(dotRect.left-areaRect.left)+(dotRect.width/2);
const left=Math.min(maxLeft,Math.max(minLeft,rawLeft));
const aboveTop=dotRect.top-areaRect.top-12;
const shouldShowBelow=aboveTop<tooltipHeight+10;

tooltip.classList.toggle("below",shouldShowBelow);
tooltip.style.left=`${left}px`;
tooltip.style.top=shouldShowBelow
?`${(dotRect.top-areaRect.top)+dotRect.height+8}px`
:`${dotRect.top-areaRect.top-8}px`;
};

dots.forEach((dot)=>{
dot.addEventListener("click",(event)=>{
event.stopPropagation();
showTooltip(dot);
});

dot.addEventListener("keydown",(event)=>{
if(event.key==="Enter"||event.key===" "){
event.preventDefault();
showTooltip(dot);
}
if(event.key==="Escape"){
hideChartTooltips();
}
});
});
});

document.addEventListener("click",(event)=>{
if(event.target.closest(".chart-area"))return;
hideChartTooltips();
});

window.addEventListener("resize",hideChartTooltips);
