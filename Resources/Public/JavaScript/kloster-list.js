// Generated by CoffeeScript 1.7.1
var dataTable;

dataTable = null;

$(function() {
  if ($("#list_form").length) {
    $("#list_form").populate_list();
  }
  $(".edit").click(function(e) {
    e.preventDefault();
    return $("#edit_form").read_kloster($(this).attr("href"));
  });
  $(".delete").click(function(e) {
    e.preventDefault();
    return $("#delete").delete_kloster($(this).attr("href"));
  });
  $("#list_form").submit(function(e) {
    e.preventDefault();
    if ($("input[name^='uuid']:checked").length === 0) {
      $(this).message("Wählen Sie bitte mindestens einen Eintrag aus.");
      return false;
    }
    return $(this).update_list();
  });
});

$.fn.populate_list = function() {
  var $this;
  $this = $(this);
  $.getJSON("klosterListAll", function(response) {
    var $inputBearbeitungsstatus, $table, $trTemplate, bearbeitungsstatusArray, klosters;
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
      return $(this).append('<div><input type="text"></div>');
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
        if ($(this).is("select")) {
          if (name === "bearbeitungsstatus") {
            $tr.find("select[name=bearbeitungsstatus] option").each(function(i, opt) {
              if (opt.value === val) {
                return $(opt).attr("selected", "selected");
              }
            });
          } else {
            $(this).append("<option>" + val + "</option>");
          }
        } else if (name !== "__csrfToken") {
          $(this).val(val);
        }
        return $('<span class="val"/>').text($(this).is("select") ? $(this).find(":selected").text() : $(this).val()).hide().insertBefore($(this));
      });
      $tr.find(".edit").attr("href", "edit/" + kloster.uuid);
      $tr.find(".delete").attr("href", "delete/" + kloster.uuid);
      $tr.find("input.csrf").attr("id", "csrf" + index);
      return dataTable.row.add($tr);
    });
    return dataTable.row($trTemplate).remove().draw();
  });
};
