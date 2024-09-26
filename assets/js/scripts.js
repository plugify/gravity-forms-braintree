/* !- gravity forms braintree scripts */

jQuery( function($) {

  // When gravity form selection changes, load the the edit feed page
  $('select#form_id[name="_gaddon_setting_form_id"]').bind('change', function(e) {

    var new_id = $(this).children(':selected').val();
    var dropdown = $(this);

    var ajax_loader = $('<img id="gform-braintree-loader" src="' + gf_braintree_scripts_strings.ajax_loader_url + '" alt="Loading.." style="padding: 0 0 0 5px;"/>');

    if( new_id == '' )
      return false;

    $.ajax({

				type: 'POST',
				dataType: 'json',
				url: gf_braintree_scripts_strings.ajax_url,
				data: {
          id: new_id,
          fid: gf_braintree_scripts_strings.feed_id,
          action: 'map_feed_fields'
        },
				beforeSend: function() {

          ajax_loader.insertAfter('tr#gaddon-setting-row-form_id td select#form_id');
          $('#gform-settings-save,#gaddon-setting-row-gf_braintree_mapped_fields').fadeOut(150);

				},
				success: function ( result ) {

          if( result.success ) {

            var html = $.parseHTML(result.data.html);
            html = $(html).find('#gaddon-setting-row-gf_braintree_mapped_fields').children();

            $('#gaddon-setting-row-gf_braintree_mapped_fields').children().remove();
            $('#gaddon-setting-row-gf_braintree_mapped_fields').append(html);

            $('#gform-settings-save,#gaddon-setting-row-gf_braintree_mapped_fields').fadeIn(150);

          }
          else {

            var message = $('<p class="error">' + result.data.message + '</p>');

            message.appendTo(dropdown.parent());

            setTimeout( function() {
              message.fadeOut(350, function() {
                message.remove();
              });
            }, 3000)

          }

				},
				complete: function () {
          ajax_loader.remove();
				}

			});

  });

  // Delete feeds when the delete anchor is clicked
  $('.submitdelete').bind('click', function(e) {

    // Halt
    e.preventDefault();

    var anchor = $(this);
    var feed_id = anchor.attr('data-feed-id');
    var row = anchor.closest('tr');

    // Delete the feed if confirmed
    if( confirm( 'WARNING: You are about to delete this feed. This cannot be undone. Are you sure?' ) ) {
      $.ajax({

        type: 'POST',
        dataType: 'json',
        url: gf_braintree_scripts_strings.ajax_url,
        data: {
          action: 'delete_feed',
          feed_id: feed_id
        },
        beforeSend: function() {
          row.fadeTo(250, 0.4);
        },
        error: function () {
          row.fadeTo(0, 1);
          alert('There was an error deleting the feed. Please try again.');
        },
        success: function ( result ) {

          row.remove();

          if( result.data.feed_count <= 0 )
          $('tbody#the-list').append('<tr class="no-items"><td class="colspanchange" colspan="5">' + result.data.message + '</td></tr>')

        },
        complete: function () {

        }

      })
    }

  });

  // Toggle feed active state
  $('.column-is_active .toggle_active').bind('click', function(e) {

    var feed_id = $(this).attr('data-feed-id');
    var is_active = $(this).attr('src').indexOf('active1.png') >= 0 ? 1 : 0;
    var img = $(this);

    $.ajax({

      type: 'POST',
      dataType: 'json',
      url: gf_braintree_scripts_strings.ajax_url,
      data: {
        action: 'toggle_feed_active',
        feed_id: feed_id,
        is_active: is_active
      },
      beforeSend: function () {

        if( is_active )
          img.attr('src', img.attr('src').replace('active1', 'active0'));
        else
          img.attr('src', img.attr('src').replace('active0', 'active1'));

        is_active = !is_active;

      },
      success: function ( result ) {

        if( is_active != result.data.is_active )
        alert( 'There was a problem with your request. Please refresh the page and try again. ');

      }

    })

  });

});