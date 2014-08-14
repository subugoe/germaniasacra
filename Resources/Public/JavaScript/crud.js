// Generated by CoffeeScript 1.7.1
$.fn.populate_list = function() {
  var $this;
  $this = $(this);
  return $.getJSON("klosterListAll", function(response) {
    var $inputBearbeitungsstatus, $table, $trTemplate, bearbeitungsstatusArray, dataTable, klosters;
    bearbeitungsstatusArray = response[1];
    $inputBearbeitungsstatus = $("select[name='bearbeitungsstatus']");
    $inputBearbeitungsstatus.empty();
    $.each(bearbeitungsstatusArray, function(k, v) {
      return $.each(v, function(k1, v1) {
        return $inputBearbeitungsstatus.append($("<option>", {
          value: v1,
          html: k1
        }));
      });
    });
    klosters = response[0];
    $table = $this.find("table:eq(0)");
    $trTemplate = $table.find("tbody tr:first");
    $table.find("thead th").not(":first").not(":last").each(function() {
      return $(this).append("<div><input type=\"text\"></div>");
    });
    dataTable = $table.DataTable({
      autoWidth: false,
      columnDefs: [
        {
          bSortable: false,
          aTargets: ["no-sorting"]
        }, {
          width: "10%",
          targets: 1
        }
      ],
      dom: "lipt",
      language: {
        url: "/_Resources/Static/Packages/Subugoe.GermaniaSacra/JavaScript/DataTables/German.json"
      },
      order: [[3, "asc"]],
      fnDrawCallback: function() {
        $table.find("textarea").autosize();
        return $table.find(":input:not(:checkbox)").change(function() {
          return $(this).closest("td").addClass("dirty").closest("tr").find(":checkbox:eq(0)").prop("checked", true);
        });
      }
    });
    dataTable.columns().eq(0).each(function(colIdx) {
      return $("input", dataTable.column(colIdx).header()).click(function(e) {
        return e.stopPropagation();
      }).on("keyup change", function() {
        return dataTable.column(colIdx).search(this.value).draw();
      });
    });
    $("body").append('<input id="uuid_filter" type="hidden">');
    $("#uuid_filter").change(function() {
      return dataTable.column(0).search(this.value, true, false).draw();
    });
    $.each(klosters, function(index, kloster) {
      var $tr;
      $tr = $trTemplate.clone(true);
      $tr.find(":input").each(function() {
        var name, val;
        name = $(this).attr("name");
        if (typeof name === "undefined") {
          return;
        }
        val = kloster[name];
        if ($(this).is("[type=checkbox]")) {
          if (name === "auswahl") {
            $(this).val(kloster.uuid);
          }
        } else if ($(this).is("select")) {
          if (name === "bearbeitungsstatus") {
            $tr.find("select[name=bearbeitungsstatus] option").each(function(i, opt) {
              if (opt.value === val) {
                return $(opt).attr("selected", "selected");
              }
            });
          } else {
            $(this).append("<option>" + val + "</option>");
          }
        } else {
          if (name !== "__csrfToken") {
            $(this).val(val);
          }
        }
        if (name !== "__csrfToken" && name !== "auswahl") {
          $(this).attr("name", name + "[" + kloster.uuid + "]");
          return $("<span class=\"val\"/>").text(($(this).is("select") ? $(this).find(":selected").text() : $(this).val())).hide().insertBefore($(this));
        }
      });
      $tr.find(".edit").attr("href", "edit/" + kloster.uuid);
      $tr.find(".delete").attr("href", "delete/" + kloster.uuid);
      $tr.find("input.csrf").attr("id", "csrf" + index);
      return dataTable.row.add($tr);
    });
    return dataTable.row($trTemplate).remove().draw();
  });
};

$.fn.update_list = function() {
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

$.fn.populate_selects = function() {
  var url;
  url = "getOptions";
  return $.getJSON(url, function(response) {
    var options;
    options = {};
    options.bearbeitungsstatus = response[0];
    options.personallistenstatus = response[1];
    options.band = response[2];
    options.literatur = response[3];
    options.bistum = response[4];
    options.orden = response[5];
    options.klosterstatus = response[6];
    options.bearbeiter = response[7];
    return $.each(options, function(name, values) {
      var $select;
      $select = $("select[name=\"" + name + "\"], select[name=\"" + name + "[]\"]");
      $select.empty().append($("<option>", {
        value: "",
        text: ""
      }));
      return $.each(values, function(index, object) {
        return $.each(object, function(value, uuid) {
          return $select.append($("<option>", {
            value: uuid,
            text: value
          }));
        });
      });
    });
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
        var name, val;
        name = $(this).attr("name");
        if (typeof name === "undefined") {
          return;
        }
        name = name.replace("[]", "");
        val = value[name];
        if (name === "wuestung") {
          if (name === "wuestung") {
            return $(this).prop("checked", value[name] === 1);
          }
        } else if (name === "ort") {
          return $(this).html($("<option />", {
            value: value["uuid"],
            text: value["ort"]
          }).attr("selected", true));
        } else if (name === "bistum") {
          return $(this).val(value[name]).prop("disabled", typeof value[name] !== "undefined" && $(this).text !== "keine Angabe");
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
