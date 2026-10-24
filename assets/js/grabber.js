/* Kago MAL Grabber admin UI. */
jQuery(function ($) {
  var $res = $("#kago-gr-results"), $log = $("#kago-gr-log");

  function say(t) { $log.text(t); }
  function post(action, data) {
    return $.post(kagoGrabber.ajax, $.extend({ action: action, nonce: kagoGrabber.nonce }, data));
  }
  function esc(s) { return $("<div>").text(s == null ? "" : s).html(); }

  function card(r) {
    var badge = r.exists ? '<br><em>Already on site</em>' : "";
    return '<div class="kago-gr-card"><img src="' + esc(r.image) + '" alt=""><strong>' + esc(r.title) + '</strong>' +
      esc(r.type) + " | " + esc(r.year || "-") + " | Score: " + esc(r.score || "-") + badge +
      '<p><button type="button" class="button button-primary kago-gr-import" data-id="' + r.mal_id + '">' + (r.exists ? "Update" : "Import") + "</button></p></div>";
  }

  function search() {
    var q = $("#kago-gr-q").val();
    say("Searching..."); $res.empty();
    post("kago_gr_search", { q: q }).done(function (r) {
      if (!r.success) { say(r.data.message); return; }
      if (r.data.direct) {
        say("MAL ID " + r.data.direct + (r.data.exists ? " is already on the site (will be updated)." : "") + " Importing...");
        doImport(r.data.direct); return;
      }
      var list = r.data.results || [];
      say(list.length + " result(s).");
      $res.html(list.map(card).join(""));
    }).fail(function () { say("Request failed."); });
  }

  function doImport(id, $btn) {
    if ($btn) $btn.prop("disabled", true).text("Importing...");
    say("Importing MAL " + id + " (this can take a few seconds)...");
    post("kago_gr_import", {
      mal_id: id,
      cast: $("#kago-gr-cast").is(":checked") ? 1 : 0,
      episodes: $("#kago-gr-eps").is(":checked") ? 1 : 0
    }).done(function (r) {
      if (!r.success) { say("Error: " + r.data.message); if ($btn) $btn.prop("disabled", false).text("Retry"); return; }
      var d = r.data;
      $log.html(esc((d.created ? "Imported: " : "Updated: ") + d.title + (d.notes.length ? " - " + d.notes.join(", ") : "")) + ' - <a href="' + esc(d.edit) + '" style="color:#7fb2ff">Edit post</a>');
      if ($btn) $btn.text("Done");
    }).fail(function () {
      say("Request failed or timed out.");
      if ($btn) $btn.prop("disabled", false).text("Retry");
    });
  }

  $("#kago-gr-search").on("click", search);
  $("#kago-gr-q").on("keydown", function (e) { if (e.key === "Enter") { e.preventDefault(); search(); } });
  $(document).on("click", ".kago-gr-import", function () { doImport($(this).data("id"), $(this)); });

  $("#kago-gr-test").on("click", function () {
    var $out = $("#kago-gr-test-out").text("Testing...");
    post("kago_gr_test", {}).done(function (r) {
      if (!r.success) { $out.text(r.data.message); return; }
      var h = "<table class='widefat striped' style='max-width:900px'><tr><th>Provider</th><th>Status</th><th>Time</th><th>Message</th></tr>";
      r.data.rows.forEach(function (x) {
        h += "<tr><td>" + esc(x.provider) + "</td><td>" + (x.ok ? "OK" : "Failed") + "</td><td>" + x.ms + " ms</td><td>" + esc(x.message) + "</td></tr>";
      });
      $out.html(h + "</table><p class='description'>PHP " + esc(r.data.php) + " | cURL " + esc(r.data.curl) + "</p>");
    });
  });
});
