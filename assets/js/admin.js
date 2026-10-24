/* Kago admin: media pickers and cast repeater. */
jQuery(function ($) {
  var frame;
  $(document).on("click", ".kago-media-pick", function (e) {
    e.preventDefault();
    var box = $(this).closest(".kago-media");
    frame = wp.media({ title: "Choose image", button: { text: "Use image" }, multiple: false, library: { type: "image" } });
    frame.on("select", function () {
      var att = frame.state().get("selection").first().toJSON();
      var url = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
      box.find(".kago-media-id").val(att.id);
      box.find(".kago-media-preview").html('<img src="' + url + '" alt="">');
    });
    frame.open();
  });
  $(document).on("click", ".kago-media-clear", function (e) {
    e.preventDefault();
    var box = $(this).closest(".kago-media");
    box.find(".kago-media-id").val("");
    box.find(".kago-media-preview").empty();
  });
  $("#kago-cast-add").on("click", function () {
    var i = parseInt($(this).data("next"), 10) || 0;
    var tpl = document.getElementById("kago-cast-template").innerHTML.replace(/__i__/g, i);
    $("#kago-cast-rows").append(tpl);
    $(this).data("next", i + 1);
  });
  $(document).on("click", ".kago-cast-remove", function (e) {
    e.preventDefault();
    $(this).closest(".kago-cast-row").remove();
  });

  $("#kago-dl-add-quality").on("click", function () {
    var button = $(this);
    var i = parseInt(button.attr("data-next-group"), 10) || 0;
    var template = document.getElementById("kago-dl-quality-template");
    if (!template) return;
    $("#kago-download-groups").append(template.innerHTML.replace(/__group__/g, i));
    button.attr("data-next-group", i + 1);
  });

  $(document).on("click", ".kago-dl-add-host", function () {
    var button = $(this);
    var group = button.attr("data-group");
    var i = parseInt(button.attr("data-next-host"), 10) || 0;
    var template = document.getElementById("kago-dl-host-template");
    if (!template) return;
    button.siblings(".kago-dl-hosts").append(
      template.innerHTML.replace(/__group__/g, group).replace(/__host__/g, i)
    );
    button.attr("data-next-host", i + 1);
  });

  $(document).on("click", ".kago-dl-remove-host", function (e) {
    e.preventDefault();
    $(this).closest(".kago-dl-host-row").remove();
  });

  $(document).on("click", ".kago-dl-remove-quality", function (e) {
    e.preventDefault();
    $(this).closest(".kago-dl-group").remove();
  });
});
