jQuery(document).ready(function($){
	
	// Change titles (and values) when user add new item to the menu:
	var oldAddMenuItemToBottom = wpNavMenu.addMenuItemToBottom;
	wpNavMenu.addMenuItemToBottom = function( menuMarkup, req ) {
		oldAddMenuItemToBottom( menuMarkup, req );
		changeTitles();
	}
	var oldAddMenuItemToTop = wpNavMenu.addMenuItemToTop;
	wpNavMenu.addMenuItemToTop = function( menuMarkup, req ) {
		oldAddMenuItemToTop( menuMarkup, req );
		changeTitles();
	}
	
	// Change titles (and values) when document is ready:
	var lang = $('#qt-languages :radio:checked').val();
	changeTitles();
	
	// Change titles (and values) when language is changed:
	$('#qt-languages :radio').change( function() {
		lang = $('#qt-languages :radio:checked').val();
		changeTitles();
	});
	
	// Change titles (and values) when new menu is added:
	$('.submit-add-to-menu').click( function() {
		lang = $('#qt-languages :radio:checked').val();
		changeTitles();
	});
		
	// Update original value when user changed a value:
	$('input.edit-menu-item-title').live('change', function() {
		regexp = new RegExp('(<!--:' + lang + '-->)(.*?)(<!--:-->)', 'i');
		if( regexp.test( $(this).data( 'qt-value' ) ) )
			$(this).data( 'qt-value', $(this).data('qt-value').replace( regexp, '$1' + $(this).val() + '$3' ) );
		else
			$(this).data( 'qt-value', $(this).val() );
	});
	
	// Change titles (and values):
	function changeTitles() {
		// Change menu item titles and links (on the right side):
		regexp = new RegExp('&lt;!--:' + lang + '--&gt;(.*?)&lt;!--:--&gt;', 'i');
		$('.item-title').each( function() {
			if ($(this).data('qt-value') == undefined) $(this).data('qt-value', $(this).html());
			if (matches = $(this).data('qt-value').match(regexp)) {
				$(this).html( matches[1] );
				$(this).closest('li').find('.link-to-original a').text( matches[1] );
			}
		});
		
		// Change menu item title inputs (on the right side):
		regexp = new RegExp('<!--:' + lang + '-->(.*?)<!--:-->', 'i');
		$('input.edit-menu-item-title').each( function() {
			if ($(this).data('qt-value') == undefined) $(this).data('qt-value', $(this).val());
			if (matches = $(this).data('qt-value').match(regexp)) {
				$(this).val( matches[1] );
			}
		});
		
		// Change menu item checkbox labels (on the left side):
		$('label.menu-item-title').each( function() {
			var textNode = $(this).contents().get(1);
			if ($(this).data('qt-value') == undefined) $(this).data('qt-value', textNode.nodeValue);
			if (matches = $(this).data('qt-value').match(regexp)) {
				textNode.nodeValue = ' ' + matches[1];
			}
		});
	}

	// Restore the original input values:
	function restoreValues(){ 
		$('input.edit-menu-item-title').each( function() {
			$(this).val( $(this).data( 'qt-value') );
		});
	}
	
	// Just before saving restore the original input values:
	$('.menu-save').click(function() {
		restoreValues();
	});

	// Just before leaving the page (or refresh) restore the original input values:
	window.onbeforeunload = function(){ 
		restoreValues();		
		return
	}

});