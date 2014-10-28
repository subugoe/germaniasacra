// Generated by CoffeeScript 1.7.1
var createAction, editAction, initEditor, newAction, populateSelectsAction, updateAction;

initEditor = function(type) {
  populateSelectsAction(type);
  $("#edit textarea").autosize();
  $('#edit').hide();
  $("#edit input[type=url]").keyup(function() {
    return $(this).parent().next(".link").html($(this).val() ? '<a class="icon-link" href="' + $(this).val() + '" target="_blank"></a>' : '');
  });
  $("#edit fieldset .multiple .remove").click();
  $("#edit :input:not([name=uuid]):not([name=uUID])").change(function() {
    return $(this).closest("label").addClass("dirty");
  });
  $("#edit .close").click(function(e) {
    e.preventDefault();
    $(this).parent().closest("section[id]").slideUp();
    return $("#search, #list").slideDown();
  });
  $("#edit form").submit(function(e) {
    e.preventDefault();
    $("select:disabled").prop("disabled", false).addClass("disabled");
    if ($(this).find(":input[name=uuid], :input[name=uUID]").first().val().length) {
      updateAction(type);
    } else {
      createAction(type);
    }
    return $("select.disabled").prop("disabled", true);
  });
  return initGeopicker();
};

populateSelectsAction = function() {
  return $.getJSON('getOptions', function(response) {
    return $.each(response, function(name, values) {
      var $selects;
      $selects = $("#edit select[name='" + name + "'], select[name='" + name + "[]'], select[name='" + name + "_uid");
      $selects.empty();
      return $.each(values, function(uuid, text) {
        return $selects.append($('<option>', {
          value: uuid,
          text: text
        }));
      });
    });
  });
};

newAction = function() {
  var $form;
  $form = $('#edit form');
  $form.clearForm();
  $('#search, #list').slideUp();
  $('#edit').slideDown();
  $form.find('select[name=personallistenstatus] option:contains("Erfassung")').attr('selected', 'selected');
  $form.find('select option:contains("keine Angabe")').attr('selected', 'selected');
  $("#edit select").autocomplete();
  $("#edit").find('input[type=url]').keyup();
  return $("#edit").find('textarea').trigger('autosize.resize');
};

createAction = function(type, data) {
  var $form;
  $form = $('#edit form');
  if (type === 'kloster') {
    return $.post('kloster/create', $form.serialize()).done(function(respond, status, jqXHR) {
      $.get('solrUpdateWhenKlosterCreate', {
        uuid: respond
      });
      return message('Ein neuer Eintrag wurde angelegt.');
    }).fail(function() {
      return message('Fehler: Eintrag konnte nicht angelegt werden.');
    });
  } else {
    return $.post(type + '/create', $form.serialize()).done(function(respond, status, jqXHR) {
      return message('Ein neuer Eintrag wurde angelegt.');
    }).fail(function() {
      return message('Fehler: Eintrag konnte nicht angelegt werden.');
    });
  }
};

editAction = function(type, id) {
  var $form;
  $form = $('#edit form');
  $form.clearForm();
  $('#search, #list').slideUp();
  message(s_loading, false);
  return $.getJSON("" + type + "/edit/" + id).done(function(obj) {
    var $fieldset, $input, name, value;
    for (name in obj) {
      value = obj[name];
      $input = $form.find(":input[data-type=" + name + "], :input[name='" + name + "']").first();
      if (name === 'ort') {
        $input.html($('<option />', {
          value: value.uuid,
          text: value.name
        }).attr('selected', true));
      } else {
        if ($input.is(':checkbox') && value) {
          $input.prop('checked', true);
        } else {
          $input.val(value);
        }
      }
    }
    $fieldset = $('#klosterdaten');
    if ($fieldset.length) {
      $fieldset.find('label :input').each(function() {
        var val;
        name = $(this).attr('name');
        if (name) {
          name = name.replace('[]', '');
        }
        val = obj[name];
        return $(this).val(val);
      });
    }
    $fieldset = $('#klosterorden');
    if ($fieldset.length) {
      $.each(obj.klosterorden, function(index, value) {
        if (index > 0) {
          $fieldset.find('.multiple:last()').addInputs(0);
        }
        return $fieldset.find('.multiple:last() label :input').each(function() {
          name = $(this).attr('name');
          if (typeof name === 'undefined') {
            return;
          }
          name = name.replace('[]', '');
          return $(this).val(value[name]);
        });
      });
    }
    $fieldset = $('#klosterstandorte');
    if ($fieldset.length) {
      $.each(obj.klosterstandorte, function(index, value) {
        if (index > 0) {
          $fieldset.find('.multiple:last()').addInputs(0);
        }
        return $fieldset.find('.multiple:last() label :input').each(function() {
          var checkedCondition, disabledCondition, text, val;
          name = $(this).attr('name');
          if (typeof name === 'undefined') {
            return;
          }
          name = name.replace('[]', '');
          val = value[name];
          if (name === 'wuestung') {
            if (name === 'wuestung') {
              checkedCondition = value[name] === 1;
              return $(this).prop('checked', checkedCondition);
            }
          } else if (name === 'ort') {
            return $(this).html($('<option />', {
              value: value.uuid,
              text: value.ort
            }).attr('selected', true));
          } else if (name === 'bistum') {
            $(this).val(value[name]);
            text = $(this).find(':selected');
            disabledCondition = text !== 'keine Angabe' && text !== '';
            return $(this).prop('disabled', disabledCondition);
          } else {
            return $(this).val(value[name]);
          }
        });
      });
    }
    $fieldset = $('#links');
    if ($fieldset.length) {
      $fieldset.find('.multiple:eq(0)').removeInputs(0);
      $.each(obj.url, function(index, value) {
        if (value.url_typ_name === 'GND') {
          $(':input[name=gnd]').val(value.url);
          return $(':input[name=gnd_label]').val(value.url_label);
        } else if (value.url_typ_name === 'Wikipedia') {
          $(':input[name=wikipedia]').val(value.url);
          return $(':input[name=wikipedia_label]').val(value.url_label);
        } else {
          $fieldset.find('.multiple:last()').addInputs(0);
          return $fieldset.find('.multiple:last() label :input').each(function() {
            name = $(this).attr('name');
            if (typeof name === 'undefined') {
              return;
            }
            name = name.replace('[]', '');
            return $(this).val(value[name]);
          });
        }
      });
    }
    $fieldset = $('#literatur');
    if ($fieldset.length) {
      $.each(obj.literatur, function(index, value) {
        if (index > 0) {
          $fieldset.addInputs(0);
        }
        return $fieldset.find('.multiple:last() label :input').each(function() {
          name = $(this).attr('name');
          if (typeof name === 'undefined') {
            return;
          }
          name = name.replace('[]', '');
          return $(this).val(value);
        });
      });
    }
    $('#edit').slideDown();
    $('#message').slideUp();
    $form.find('select').autocomplete();
    $form.find('input[type=url]').keyup();
    return $form.find('textarea').trigger('autosize.resize');
  }).fail(function() {
    return message('Fehler: Daten konnte nicht werden.');
  });
};

updateAction = function(type) {
  var $form, uuid;
  $form = $("#edit form");
  uuid = $form.find(':input[name=uuid], :input[name=uUID]').first().val();
  return $.post("" + type + "/update/" + uuid, $form.serialize()).done(function(respond, status, jqXHR) {
    message('Ihre Änderungen wurden gespeichert.');
    if (type === 'kloster') {
      return $.post("kloster/updateSolrAfterKlosterUpdate", {
        uuid: respond
      });
    }
  }).fail(function() {
    return message('Fehler: Ihre Änderungen konnten nicht gespeichert werden.');
  });
};
