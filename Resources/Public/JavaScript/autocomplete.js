// Generated by CoffeeScript 1.7.1

/*
Autocomplete for select fields
 */
var delay;

$.fn.autocomplete = function() {
  return this.each(function() {
    var $input, $list, $overlay, $select, $spinner, name;
    $select = $(this);
    name = $select.data('type') ? $select.data('type') : $select.attr('name').replace('[]', '');
    $select.hide().siblings('.autocomplete').remove();
    $input = $('<input type="text" placeholder="Zum Suchen tippen&hellip;">').val($select.find(':selected').text());
    $spinner = $('<i class="spinner spinner-icon"/>');
    $spinner.hide();
    $list = $('<ol class="list"/>');
    $list.css({
      top: $('select:eq(0)').outerHeight()
    });
    $overlay = $('<div class="overlay autocomplete"/>').append($input, $spinner, $list);
    $overlay.insertAfter($select);
    if (!$select.hasClass('ajax')) {
      $.each($select.find('option'), function(index, element) {
        return $list.append("<li data-uuid='" + ($(element).val()) + "'>" + ($(element).text()) + "</li>");
      });
    }
    $input.click(function() {
      $input.val('');
      $list.find('li').show().first().addClass('current');
      return $list.slideDown();
    });
    $list.on('click', 'li', function() {
      $input.val($(this).text());
      $select.setSelected($(this));
      return $input.blur();
    });
    $input.on('keyup', function(e) {
      var $current, $newCurrent, $visibleItems, liHeight;
      if ($select.hasClass('ajax')) {
        if ($input.val().length > 0) {
          delay((function() {
            $spinner.show();
            return $.ajax({
              url: "/search" + (ucfirst(name)) + "?searchString=" + (encodeURIComponent($input.val())),
              type: 'GET',
              complete: function() {
                return $spinner.hide();
              },
              error: function() {
                return alert('Fehler: Daten konnten nicht geladen werden.');
              },
              success: function(data) {
                var json;
                json = $.parseJSON(data);
                $list.empty();
                $.each(json, function(index, item) {
                  return $list.append("<li data-uuid='" + item.uuid + "'>" + item.name + "</li>");
                });
                return $list.slideDown().scrollTop(0).find('li').first().addClass('current');
              }
            });
          }), 500);
        }
      } else {
        $.each($list.find('li'), function(index, item) {
          if ($(item).text().toLowerCase().indexOf($input.val().toLowerCase()) > -1) {
            return $(item).show();
          } else {
            return $(item).hide();
          }
        });
      }
      if ($list.is(':visible')) {
        $visibleItems = $list.children(':visible');
        liHeight = $list.children(':first').outerHeight();
        $visibleItems.filter('.current:gt(0)').removeClass('current');
        $current = $visibleItems.filter('.current');
        if (!$current.length) {
          $current = $visibleItems.first().addClass('current');
        }
        switch (e.which) {
          case 13:
            e.preventDefault();
            $input.val($current.text().trim());
            $select.setSelected($current);
            return $input.blur();
          case 38:
            $newCurrent = $current.prevAll(':visible').first();
            if (!$newCurrent.length) {
              $newCurrent = $visibleItems.last();
            }
            $current.removeClass('current');
            $newCurrent.addClass('current');
            $list.scrollTop($visibleItems.index($newCurrent) * liHeight - ($list.height() - liHeight) / 2);
            return false;
          case 9:
          case 40:
            $newCurrent = $current.nextAll(':visible').first();
            if (!$newCurrent.length) {
              $newCurrent = $visibleItems.first();
            }
            $current.removeClass('current');
            $newCurrent.addClass('current');
            $list.scrollTop($visibleItems.index($newCurrent) * liHeight - ($list.height() - liHeight) / 2);
            return false;
          case 35:
          case 36:
          case 27:
            return $input.blur();
        }
      }
    });
    return $input.blur(function() {
      $list.slideUp();
      $list.find('.current').removeClass('current');
      return $input.val($select.find(':selected').text());
    });
  });
};

$.fn.setSelected = function($el) {
  return this.each(function() {
    return $(this).empty().append("<option value='" + ($el.data('uuid')) + "' selected>" + ($el.text()) + "</option>");
  });
};

delay = (function() {
  var timer;
  timer = 0;
  return function(callback, ms) {
    clearTimeout(timer);
    return timer = setTimeout(callback, ms);
  };
})();
