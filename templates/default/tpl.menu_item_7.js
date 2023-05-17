const nljMenuItem = document.createElement("li");
nljMenuItem.setAttribute("role", "none");

const nljMenuItemLink = document.createElement("a");
nljMenuItemLink.className = "il-link link-bulky disengaged";
nljMenuItemLink.href = "{nlj_LINK}";
nljMenuItemLink.id = "nlj-menu-item";
nljMenuItemLink.setAttribute("aria-pressed", false);

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

nljMenuItemLink.appendChild(nljIcon);
nljMenuItemLink.appendChild(nljLabel);
nljMenuItem.appendChild(nljMenuItemLink);

let mainBar = document.querySelector(".il-mainbar-entries");

let childs = document.querySelectorAll(".il-mainbar-entries li");
let lastChild = childs[childs.length - 1];

// Append to menu
mainBar.insertBefore(nljMenuItem, lastChild);
il.UI.maincontrols.mainbar.addPartIdAndEntry("0:" + childs.length, "triggerer", "nlj-menu-item");
