// Generated by CoffeeScript 1.7.1
$(function() {
  $("#list_form").populate_list();
  $("#edit").hide().populate_selects();
  $("#edit textarea").autosize();
  $("fieldset .multiple").append("<div class=\"add-remove-buttons\"><button class=\"remove\">-</button><button class=\"add\">+</button></div>");
  $("fieldset .multiple button").click(function(e) {
    var div;
    e.preventDefault();
    div = $(this).closest(".multiple");
    if ($(this).hasClass("remove")) {
      return div.removeInputs(250);
    } else if ($(this).hasClass("add")) {
      return div.addInputs(250);
    }
  });
  $("input[type=url]").keyup(function() {
    return $(this).parent().next(".link").html($(this).val() ? '<a class="icon-link" href="' + $(this).val() + '" target="_blank"></a>' : '');
  });
  $("fieldset .multiple .remove").click();
  $(".togglable + .togglable").hide();
  $(".toggle").click(function(e) {
    e.preventDefault();
    return $(this).closest(".togglable").siblings(".togglable").addBack().slideToggle();
  });
  $(".new").click(function(e) {
    e.preventDefault();
    return $("#edit_form").new_kloster();
  });
  $(".edit").click(function(e) {
    e.preventDefault();
    return $("#edit_form").read_kloster($(this).attr("href"));
  });
  $(".delete").click(function(e) {
    e.preventDefault();
    return $("#delete").delete_kloster($(this).attr("href"));
  });
  $(".close").click(function(e) {
    e.preventDefault();
    $(this).parent().closest("div[id]").slideUp();
    return $("#browse").slideDown();
  });
  $("#list_form").submit(function(e) {
    e.preventDefault();
    if ($("input[name='auswahl']:checked").length === 0) {
      alert("Wählen Sie bitte mindestens einen Eintrag aus.");
      return false;
    }
    return $(this).update_list();
  });
  $("#edit_form").submit(function(e) {
    e.preventDefault();
    $("select:disabled").prop("disabled", false).addClass("disabled");
    if (!$(this).find("[name=kloster_id]").val().length) {
      $(this).create_kloster();
    } else {
      $(this).update_kloster();
    }
    return $("select.disabled").prop("disabled", true);
  });
  $(window).bind("keydown", function(e) {
    if (e.ctrlKey || e.metaKey) {
      switch (String.fromCharCode(e.which).toLowerCase()) {
        case "s":
          e.preventDefault();
          return $(":submit:visible:last").click();
      }
    }
  });
});

$.fn.addInputs = function(slideTime) {
  if (typeof slideTime === "undefined") {
    slideTime = 0;
  }
  return this.each(function() {
    var $clone, $fieldset;
    $fieldset = $(this).closest("fieldset");
    $clone = $(this).clone(true);
    $clone.clear_form();
    $clone.find("select.autocomplete").autocomplete();
    $clone.insertAfter($(this)).hide().slideDown(slideTime);
    return $fieldset.find("button.remove").prop("disabled", $fieldset.find(".multiple:not(.dying)").length === 1);
  });
};

$.fn.removeInputs = function(slideTime) {
  if (typeof slideTime === "undefined") {
    slideTime = 0;
  }
  return this.each(function() {
    var $fieldset;
    $fieldset = $(this).closest("fieldset");
    $fieldset.find(".multiple").length > 1 && $(this).addClass("dying").slideUp(slideTime, this.remove);
    return $fieldset.find("button.remove").prop("disabled", $fieldset.find(".multiple:not(.dying)").length === 1);
  });
};

$.fn.clear_form = function() {
  $(this).find(":input").prop("disabled", false);
  $(this).find(":input:not(:checkbox):not([type=hidden]):not(:submit)").val("");
  $(this).find(":checkbox, :radio").prop("checked", false);
  $(this).find(".multiple:gt(0)").removeInputs(0);
  return $(this).find(".autofill").text("?");
};

$.fn.message = function(text) {
  var $message, date;
  $('#message').remove();
  date = new Date();
  $message = $('<div id="message"><span class="timestamp">' + date.toLocaleString() + '</span>' + text + '</div>');
  return $message.insertAfter($(this)).hide().slideDown();
};