(function () {
  "use strict";
  function bindMobileNav() {
    var toggle = document.querySelector(".nav-toggle");
    var links = document.querySelector(".nav-links");
    if (!toggle || !links) return;
    toggle.addEventListener("click", function () {
      var open = links.classList.toggle("open");
      toggle.classList.toggle("open", open);
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });
    links.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        links.classList.remove("open");
        toggle.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
      });
    });
  }

  
  function bindSizeChips() {
    document.querySelectorAll(".size-chip input[type=radio]").forEach(function (input) {
      input.addEventListener("change", function () {
        var form = input.closest("form");
        if (form) form.submit();
      });
    });
    
    document.querySelectorAll(".size-chip input[type=checkbox]").forEach(function (input) {
      input.addEventListener("change", function () {
        input.closest(".size-chip").classList.toggle("is-active", input.checked);
      });
    });
    
    document.querySelectorAll(".color-chip input").forEach(function (input) {
      input.addEventListener("change", function () {
        document.querySelectorAll(".color-chip").forEach(function (c) { c.classList.remove("is-active"); });
        if (input.checked) input.closest(".color-chip").classList.add("is-active");
      });
      if (input.checked) input.closest(".color-chip").classList.add("is-active");
    });
  }

  
  function bindAuthTabs() {
    var tabs = document.querySelectorAll(".auth-tab");
    var panels = document.querySelectorAll(".auth-panel");
    if (!tabs.length) return;
    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        tabs.forEach(function (t) { t.classList.remove("active"); });
        panels.forEach(function (p) { p.classList.add("is-hidden"); });
        tab.classList.add("active");
        var target = document.getElementById(tab.dataset.target);
        if (target) target.classList.remove("is-hidden");
      });
    });
  }

  function misc() {
    document.querySelectorAll(".js-year").forEach(function (el) {
      el.textContent = new Date().getFullYear();
    });
    var alert = document.querySelector(".site-alert");
    if (alert) setTimeout(function () { alert.style.display = "none"; }, 4000);
  }

  document.addEventListener("DOMContentLoaded", function () {
    bindMobileNav();
    bindSizeChips();
    bindAuthTabs();
    misc();
  });
})();
