// ++ rewrite from scratch when have time
// cookies - taken from https://learn.javascript.ru/cookie
// ++ use local storage when available!!
var fcp_coo = {};
fcp_coo.get = function(name) {
	var matches = document.cookie.match(new RegExp(
		"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
	));
	return matches ? decodeURIComponent(matches[1]) : undefined;
}
fcp_coo.set = function(name, value) {
	document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + "; path=/;";
}
fcp_coo.remove = function(name) {
	fcp_coo.set(name, "", {
		'max-age': -1
	})
}

// close button for ~any box
// ++ change different cookies to one with array
// .fcp-closed-default gotta be added to element to be hidden by default from the start
function fcp_verified_close() {
	var $ = jQuery;
	$( '.fcp-ymyl' ).each( function(a) {
		var self = $(this);
		self.append('<div class="fcp-parent-close"></div>'); // close button
		self.addClass('fcp-closeable'); // styling for fluent close
		self.attr('data-fcp-close-id', a); // id for cookies
		self.after('<div class="fcp-closed-marker"></div>'); // add a marker to mark the box, when it is closed

		// close the boxes, mentioned in cookies and open if hidden on backend
		if ( fcp_coo.get('fcp-closed-'+a) ) {
			self.addClass('fcp-closed');
		}
		self.removeClass('fcp-closed-default');

	});
	
	$( '.fcp-parent-close' ).click( function() {
		var badge = $(this).parent();
		badge.addClass('fcp-closed');
		var a = badge.attr('data-fcp-close-id');
		fcp_coo.set('fcp-closed-'+a, '1');
	});
	$( '.fcp-closed-marker' ).click( function() {
		var badge = $(this).prev();
		badge.removeClass('fcp-closed');
		var a = badge.attr('data-fcp-close-id');
		fcp_coo.remove('fcp-closed-'+a);
	});
};

fcp_verified_close();
