import '@/style.css';

// working with cookies
// initially taken from https://learn.javascript.ru/cookie
var vv_cookies = {};
vv_cookies.get = function(name) {
	var matches = document.cookie.match(new RegExp(
		"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
	));
	return matches ? decodeURIComponent(matches[1]) : undefined;
}
vv_cookies.set = function(name, value) {
	document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + "; path=/;";
}
vv_cookies.remove = function(name) {
	vv_cookies.set(name, "", {
		'max-age': -1
	})
}

// close button for ~any box
// change different cookies to one with array
// .vv-closed-default gotta be added to element to be hidden by default from the start
function vv_verified_close() {
	var $ = jQuery;
	$( '.vv-author-verified' ).each( function(a) {
		var self = $(this);
		self.append('<div class="vv-parent-close"></div>'); // close button
		self.addClass('vv-closeable'); // styling for fluent close
		self.attr('data-vv-close-id', a); // id for cookies
		self.after('<div class="vv-closed-marker"></div>'); // add a marker to mark the box, when it is closed

		// close the boxes, mentioned in cookies and open if hidden on backend
		if ( vv_cookies.get('vv-closed-'+a) ) {
			self.addClass('vv-closed');
		}
		self.removeClass('vv-closed-default');

	});
	
	$( '.vv-parent-close' ).click( function() {
		var badge = $(this).parent();
		badge.addClass('vv-closed');
		var a = badge.attr('data-vv-close-id');
		vv_cookies.set('vv-closed-'+a, '1');
	});
	$( '.vv-closed-marker' ).click( function() {
		var badge = $(this).prev();
		badge.removeClass('vv-closed');
		var a = badge.attr('data-vv-close-id');
		vv_cookies.remove('vv-closed-'+a);
	});
};

vv_verified_close();
