const nljMenuItem = document.createElement("a");
nljMenuItem.className = "il-link link-bulky disengaged";
nljMenuItem.href = "{nlj_LINK}";
nljMenuItem.id = "nlj-menu-item";
nljMenuItem.setAttribute("aria-pressed", false);

const nljIcon = document.createElement("div");
nljIcon.className = "icon custom small";
nljIcon.setAttribute("aria-label", "{nlj_LABEL}");
nljIcon.style.filter = "none";

const nljIconImage = document.createElement("img");
nljIconImage.src = "{nlj_ICON}";
nljIcon.appendChild(nljIconImage);

const nljLabel = document.createElement("span");
nljLabel.className = "bulky-label";
nljLabel.innerText = "{nlj_LABEL}";

nljMenuItem.appendChild(nljIcon);
nljMenuItem.appendChild(nljLabel);

let mainBar = document.querySelector(".il-mainbar-entries");

let childs = document.querySelectorAll(".il-mainbar-entries a, .il-mainbar-entries button");
let lastChild = childs[childs.length - 1];

// Append to menu
mainBar.insertBefore(nljMenuItem, lastChild);

il.UI.maincontrols.mainbar.addPartIdAndEntry("0:" + childs.length, "triggerer", "nlj-menu-item");
