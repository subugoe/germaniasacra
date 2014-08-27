// Generated by CoffeeScript 1.7.1
$.fn.update_list = function() {
  var $rows, $this, formData, url;
  $this = $(this);
  url = $this.attr("action");
  $rows = dataTable.$('tr').has('input:checked');
  formData = {};
  $rows.each(function() {
    var uuid;
    uuid = $(this).find(':input[name=uuid]').val();
    formData['klosters[' + uuid + ']'] = {};
    return $(this).find(':input:not([name=uuid])').each(function(i, input) {
      if (input.name && input.name != 'ort') {
        formData['klosters[' + uuid + ']'][input.name] = input.value;
      }
    });
  });
  formData.__csrfToken = $(this).find('input[name=__csrfToken]').val();
  console.dir(formData);
  return $.post(url, formData).done(function(respond, status, jqXHR) {
	  $.get("updateSolrAfterListUpdate", {uuids: respond}).done(function(respond, status, jqXHR) {
	    if (status === "success") {
          return $this.message('Ihre Änderungen wurden gespeichert.');
        }
	  }).fail(function(jqXHR, textStatus) {
	    $this.message('Error');
	    return console.dir(jqXHR.responseText);
	  });
  }).fail(function(jqXHR, textStatus) {
    $this.message('Error');
    return console.dir(jqXHR.responseText);
  });
};

$.fn.new_kloster = function() {
  $("#browse").slideUp();
  $("#edit").slideDown();
  $(this).clear_form();
  $(this).find(".autocomplete").autocomplete();
  $(this).find("textarea").trigger("autosize.resize");
  return $(this).find("input[type=url]").keyup();
};

$.fn.create_kloster = function() {
  var $this;
  $this = $(this);
  return $.post("create", $this.serialize()).done(function(respond, status, jqXHR) {
    var dataArray, uuid;
    dataArray = $.parseJSON(respond);
    uuid = dataArray[0];
    $.get("addKlosterId", {
      uuid: uuid
    });
    return $this.message('Ein neuer Eintrag wurde angelegt.');
  }).fail(function(jqXHR, textStatus) {
    $this.message('Error');
    return console.dir(jqXHR.responseText);
  });
};

$.fn.read_kloster = function(url) {
  var $this;
  $this = $(this);
  $this.clear_form();
  return $.getJSON(url, function(kloster) {
    var $fieldset, update_url, uuid;
    uuid = kloster.uuid;
    update_url = "update/" + uuid;
    $this.attr("action", update_url);
    $fieldset = $("#kloster");
    $fieldset.find("label :input").each(function() {
      var name, val;
      name = $(this).attr("name");
      if (typeof name === "undefined") {
        return name = name.replace("[]", "");
      }
      val = kloster[name];
      return $(this).val(val);
    });
    $fieldset.find("[name=changeddate]").val(kloster.changeddate ? kloster.changeddate.date.substr(0, kloster.changeddate.date.indexOf(".")) : '');
    $fieldset = $("#klosterorden");
    $.each(kloster.klosterorden, function(index, value) {
      if (index > 0) {
        $fieldset.find(".multiple:last()").addInputs(0);
      }
      return $fieldset.find(".multiple:last() label :input").each(function() {
        var name;
        name = $(this).attr("name");
        if (typeof name === "undefined") {
          return;
        }
        name = name.replace("[]", "");
        return $(this).val(value[name]);
      });
    });
    $fieldset = $("#klosterstandorte");
    $.each(kloster.klosterstandorte, function(index, value) {
      if (index > 0) {
        $fieldset.find(".multiple:last()").addInputs(0);
      }
      return $fieldset.find(".multiple:last() label :input").each(function() {
        var checkedCondition, disabledCondition, name, text, val;
        name = $(this).attr("name");
        if (typeof name === "undefined") {
          return;
        }
        name = name.replace("[]", "");
        val = value[name];
        if (name === "wuestung") {
          if (name === "wuestung") {
            checkedCondition = value[name] === 1;
            return $(this).prop("checked", checkedCondition);
          }
        } else if (name === "ort") {
          return $(this).html($("<option />", {
            value: value["uuid"],
            text: value["ort"]
          }).attr("selected", true));
        } else if (name === "bistum") {
          $(this).val(value[name]);
          text = $(this).find(':selected');
          disabledCondition = text !== "keine Angabe" && text !== "";
          return $(this).prop("disabled", disabledCondition);
        } else {
          return $(this).val(value[name]);
        }
      });
    });
    $fieldset = $("#links");
    $.each(kloster.url, function(index, value) {
      if (value.url_typ === "GND") {
        return $("#gnd").val(value.url);
      } else if (value.url_typ === "Wikipedia") {
        return $("#wikipedia").val(value.url);
      } else {
        $fieldset.find(".multiple:last()").addInputs(0);
        return $fieldset.find(".multiple:last() label :input").each(function() {
          var name;
          name = $(this).attr("name");
          if (typeof name === "undefined") {
            return;
          }
          name = name.replace("[]", "");
          return $(this).val(value[name]);
        });
      }
    });
    $fieldset.find(".multiple:eq(0)").removeInputs(0);
    $fieldset = $("#literatur");
    $.each(kloster.literatur, function(index, value) {
      if (index > 0) {
        $fieldset.addInputs(0);
      }
      return $fieldset.find(".multiple:last() label :input").each(function() {
        var name;
        name = $(this).attr("name");
        if (typeof name === "undefined") {
          return;
        }
        name = name.replace("[]", "");
        return $(this).val(value);
      });
    });
    $("#browse").slideUp();
    $("#edit").slideDown();
    $this.find(".autocomplete").autocomplete();
    $this.find("textarea").trigger("autosize.resize");
    return $this.find("input[type=url]").keyup();
  });
};

$.fn.update_kloster = function() {
  var $this, url;
  $this = $(this);
  url = $this.attr("action");
  return $.post(url, $this.serialize()).done(function(respond, status, jqXHR) {
    if (status === "success") {
      return $this.message('Ihre Änderungen wurden gespeichert.');
    }
  }).fail(function(jqXHR, textStatus) {
    $this.message('Error');
    return console.dir(jqXHR.responseText);
  });
};

$.fn.delete_kloster = function(url, csrf) {
  var $this, check;
  $this = $(this);
  check = confirm('Wollen Sie diesen Eintrag wirklich löschen?');
  if (check === true) {
    csrf = $('#csrf').val();
    return $.post(url, {
      __csrfToken: csrf
    }).done(function(respond, status, jqXHR) {
      if (status === "success") {
        return $this.message('Der Eintrag wurde gelöscht.');
      }
    }).fail(function(jqXHR, textStatus) {
      $this.message('Error');
      return console.dir(jqXHR.responseText);
    });
  }
};
