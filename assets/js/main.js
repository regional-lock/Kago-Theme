/* Kago Fansub: global search, download buttons. */
(function () {
  var base = (window.kagoData && window.kagoData.homeUrl) || "/";

  document.querySelectorAll(".topbar .search input").forEach(function (input) {
    input.addEventListener("keydown", function (event) {
      if (event.key !== "Enter") return;
      event.preventDefault();
      var query = input.value.trim();
      if (query) location.href = base + (base.indexOf("?") === -1 ? "?" : "&") + "s=" + encodeURIComponent(query);
    });
  });

  document.addEventListener("click", function (event) {
    var button = event.target.closest("button[data-download-url]");
    if (!button || button.disabled) return;
    var url = button.getAttribute("data-download-url");
    if (url && url !== "#") window.open(url, "_blank", "noopener");
  });
})();
