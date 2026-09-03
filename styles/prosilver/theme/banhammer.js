(function($) { // Avoid conflicts with other libraries
	'use strict';

	// Toggle whichever panel belongs to the clicked trigger, not a fixed id -
	// there can be more than one of these on a page (Ban Hammer, Restrict).
	$('.bh-click').click(function() {
		$(this).next('.inner').toggle('slow');
	});

	$('.bh_hover').hover(function() {
		$(this).css('background-color', '#ecf3f7');
	}, function() {
		$(this).css('background-color', '');
	});
})(jQuery);
