<?php

/*
Plugin Name: YMYL Author Boxes
Description: Display author boxes on marked posts. With multiple authors and structured data support.
Version: 1.2
Requires at least: 4.7
Requires PHP: 5.2.4
Author: Firmcatalyst, Vadim Volkov
Author URI: https://firmcatalyst.com
License: GPL v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: fcp-author-boxes
Domain Path: dist/languages
*/

/*

wrapper divs into separate function
add filter, if the shortcode is in text, so can not be used anywhere else // aka use only once
    https://wordpress.stackexchange.com/questions/45697/how-to-check-if-a-shortcode-exists
Connect to real WP users table
Different printing option for author type
compile final result to lite version

*/

class FCPAuthorBoxes {

	private function settings() {
        static $s = [];
        
        if ( $s["assigned"] )
            return $s;
        
		$d = true; // developers mode
		$s["assigned"] = true;

		$s["dev_mode"] = $d;
		$s["self_path"] = plugin_dir_path( __FILE__ );
		$s["css_ver"] = $d ? time() : "1.4.8";
		$s["js_ver"] = $d ? time() : "1.1.0";
		$s["css_ver_adm"] = $d ? time() : "1.4.8";
		$s["js_ver_adm"] = $d ? time() : "1.1.0";

		return $s;
	}

////////////////////////////////////////////////////////////
	public function __construct() {

		// add styling and js close button
//		add_action( 'wp_enqueue_scripts', array( $this, 'styles_scripts_add' ) );

		// add shortcodes for boxes
//		add_shortcode( 'ymyl-verified', array( $this, 'deliver_verified' ) );
//		add_shortcode( 'ymyl-author', array( $this, 'deliver_author' ) );

		// print verified box before the content
//		add_filter( 'the_content', array( $this, 'verified_before_content' ) );
//		add_filter( 'the_content', array( $this, 'author_after_content' ) );

		// add check metaboxes to hide the author where not needed
//		add_action( 'add_meta_boxes', array( $this, 'add_ymyl_meta_boxes' ) );
//		add_action( 'save_post', array( $this, 'save_ymyl_meta_boxes' ) );
		
		// ADMIN
		
		include_once( $this->settings()["self_path"] . 'classes/post-type.class.php' );
		new FCP_Author_Boxes_PostType();
		// else..

		// if entered dashboard
		include_once( $this->settings()["self_path"] . 'dashboard/main.php' );
		new FCP_Author_Boxes_Dashboard( 'structure.json' );
		
		
		include_once( $this->settings()["self_path"] . 'dashboard/bulk.php' );
		new FCP_Author_Boxes_Bulk();
		
		// add class with menu settings
		
        add_image_size( 'fcp-author', 512, 512, array( 'center', 'center' ) );
        /*
        if ( has_post_thumbnail() ) {
            the_post_thumbnail( 'category-thumb' ); // category-thumb - название размера
        }
        */
        add_action( 'admin_enqueue_scripts', [$this, 'styleDasboard'] );

	}
////////////////////////////////////////////////////////////

	public function styleDasboard($hook) {

        if ( !in_array( $hook, array('post.php', 'post-new.php', 'fcp-author_page_fcp-author-boxes') ) )
            return;

        wp_enqueue_style(
            'fcp-authors-admin',
            plugins_url( 'admin.css', __FILE__ ),
            false,
            $hook//$this->settings()["css_ver_adm"]
        );
	}

	// layout with schema
	private function format_verified_box($author) {
		static $common_printed = false;

		$this->plugin_setup();

		$format_text = true;
		if ( !$author['verified-text'] && $common_printed === false ) {
			$common_printed = true;
			$author['verified-text'] = $this->verified_common_modify();
			$format_text = false;
		}

		if ( $author['verified-text'] ) {
			return '
<div class="vv-author-verified vv-closed-default">
	<div class="vv-verified-content">
		'.( $format_text ? $this->common_format_content_verified($author) : $author['verified-text'] ).'
	</div>
</div>
	';
		}

	}

	private function format_author_box($author) {
		if ( !$author['about-text'] )
			return '';

		return '
<div class="vv-author" id="'.$author['slug'].'" itemscope itemid="'.$author['slug'].'" itemtype="https://schema.org/Person">
	<div class="vv-author-content">
		'.$this->common_format_img($author).'
		<div class="vv-author-about">
			<span class="vv-author-title"><span itemprop="name">'.$author['name'].'</span></span>
			<div class="vv-author-description" itemprop="description">
				'.$this->common_format_content_about($author).'
			</div>
		</div>
	</div>
</div>
	';
	}

	private function common_format_img($author) {
		if ( !$author['img'] )
			return '';

		$avatar = '
		<span itemprop="image" itemscope itemtype="https://schema.org/ImageObject" class="vv-author-image">
			<img src="'.( $author['thumb'] ? $author['thumb'] : $author['img'] ).'" alt="'.$author['name'].'">
			<meta itemprop="url" content="'.$author['img'].'">
			<meta itemprop="width" content="512">
			<meta itemprop="height" content="512">
		</span>
		';
		return $avatar;
	}
	
	// modify common text with wp paragraphs and add links to first author mention
	private function common_format_content_verified($author) {

		$text = $author['verified-text'];

		// check if author has author box, not only verified
		$add_anchor = false;
		$boxes = $this->selected_boxes()[ $author['slug'] ];
		if ( $boxes == 'both' ) {
			$add_anchor = true;
		}

		if ( $add_anchor ) {

			$nowrap_name = str_replace(' ', '&nbsp;', $author['name']);
			$text = $this->replace_first( $text,
				$author['name'],
				'<a href="#'.$author['slug'].'">'.$nowrap_name.'</a>'
			);
		}

		return wpautop( $text );
	}

	private function common_format_content_about($author) {

		$text = $author['about-text'];

		if ( $author['link'] ) {

			$nowrap_name = str_replace(' ', '&nbsp;', $author['name']);
			$text = $this->replace_first( $text,
				$author['name'],
				'<a href="'.$author['link'].'" itemprop="url" rel="author">'.$nowrap_name.'</a>'
			);
		}

		return wpautop( $text );
	}
	
	private function verified_common_modify() {
		static $result = '';

		if ( $result !== '' )
			return $result;

		$this->plugin_setup();
		
		$boxes = $this->selected_boxes();

		// add list of authors to common verified box
		$united_authors = [];
		$linked_authors = [];
		foreach ( $this->authors as $v ) {
			if ( !$v['verified-text'] && ( $boxes[$v['slug']] == 'verified' || $boxes[$v['slug']] == 'both' ) ) {
				$nowrap_name = str_replace(' ', '&nbsp;', $v['name']);

				$united_authors[] = $nowrap_name;

				// add anchors if author box present
				if ( $boxes[$v['slug']] == 'both' ) {
					$linked_authors[] = '<a href="#'.$v['slug'].'">'.$nowrap_name.'</a>';
				} else {
					$linked_authors[] = $nowrap_name;
				}
			}
		}
		$united_authors = $this->list_with_und($united_authors);
		$linked_authors = $this->list_with_und($linked_authors);

		$result = wpautop( sprintf( $this->verified_common_text, $united_authors, $linked_authors ) );
		return $result;
	}
	
	private function selected_boxes() {
		global $post;
		static $result = null;
		
		if ( $result === null ) {
			$result = [];
			$values = get_post_custom( $post->ID );
			if ( isset( $values['vvab_ymyl_boxes'] ) ) {
				$result = unserialize( $values['vvab_ymyl_boxes'][0] );
			}
		}

		return $result;
	}
	
	public function styles_scripts_add() { // try changing to private

		$this->plugin_setup();

		wp_enqueue_style( 'vvab_style', plugins_url( 'style.css', __FILE__ ), false, time() );//$this->css_ver
		wp_enqueue_script( "vvab_script", plugins_url( "base.js", __FILE__ ), array( "jquery" ), $this->js_ver, 1 );
		
	}

	// make the shortcodes
	public function deliver_verified() {
		return $this->deliver_the_box('verified');
	}
	public function deliver_author() {
		return $this->deliver_the_box('author');
	}
	private function deliver_the_box($box) {
		if (!$box)
			return;

		$this->plugin_setup();

		if ( $this->admin_mode && !current_user_can('administrator') )
			return '';

		if ( !in_array( get_post_type(), $this->post_type ) )
			return '';

		$values = $this->selected_boxes();

		$result = '';
		$count_results = 0;
		foreach ($this->authors as $author) {
			if ( isset($values[$author['slug']]) && ( $values[$author['slug']] == $box || $values[$author['slug']] == 'both' ) ) {
				$result .= call_user_func( array($this, 'format_'.$box.'_box'), $author );
				$count_results++;
			}
		}
		
		// additions
		if ($box == 'verified') {
			$result = '<div class="vv-verifieds-wrap">'.$result.'</div>';
		}
		if ($box == 'author') {
			$result = '<div class="vv-authors-wrap '.($count_results > 1 ? 'vv-authors-x2' : '').'">'.$result.'</div>';
		}
		
		return $result;
	}

	// if content hooks are active
	public function verified_before_content( $content ) {
		return vvab_ymyl_verified_print(true).$content;
	}
	public function author_after_content( $content ) {
		return $content.vvab_ymyl_author_print(true);
	}



	// admin meta boxes
	public function add_ymyl_meta_boxes() {
		$this->plugin_setup();
		
		add_meta_box( 'ymyl_boxes', 'YMYL Authors Boxes', array($this, 'draw_ymyl_meta_boxes'), $this->post_type, 'side', 'default' );
	}

	public function draw_ymyl_meta_boxes() {
		$this->plugin_setup();
		
		$values = $this->selected_boxes();

		// used to save verify
		wp_nonce_field( 'vvab_ymyl_meta_box_nonce', 'meta_box_nonce' );

		// main printing
		foreach ($this->authors as $author) {

			// author options
			$options = '<option value="0">Show NO boxes</option>';
			foreach ($this->author_options as $option => $text) {
				$options .= '<option value="'.$option.'"'.(isset($values[$author['slug']])&&$values[$author['slug']]==$option?' selected':'').'>'.$text.'</option>';
			}

			echo '
		<p>
			<strong>'.$author['name'].'</strong>
			<br>
			<select name="vvab_ymyl_boxes['.$author['slug'].']">
				'.$options.'
			</select>
		</p>			
			';
		}
		
	}

	public function save_ymyl_meta_boxes($post_id){
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'vvab_ymyl_meta_box_nonce' ) ) return;
		if ( !current_user_can( 'edit_post', $post_id ) ) return;

		update_post_meta( $post_id, 'vvab_ymyl_boxes', $_POST['vvab_ymyl_boxes'] );

	}


	// helping functions
	private function replace_first($haystack, $needle, $replace) {
		if ( !$haystack || !$needle || !$replace )
			return $haystack;

		$pos = strpos( $haystack, $needle );
		if ($pos === false)
			return $haystack;

		$newstring = substr_replace(
			$haystack,
			$replace,
			$pos,
			strlen($needle)
		);
		return $newstring;
	}
	
	private function list_with_und($arr) {
		$last = '';

		if ( $arr[1] )
			$last = array_pop($arr);

		return implode( ', ', $arr ) . ( $last ? ' und '.$last : '' );
	}
	
}

new FCPAuthorBoxes();

// print author boxes anywhere in php files
function vvab_ymyl_verified_print($return_only = false) {
	return vvab_ymyl_print('verified', $return_only);
}
function vvab_ymyl_author_print($return_only = false) {
	return vvab_ymyl_print('author', $return_only);
}

function vvab_ymyl_print($box, $return_only) {

	$authors = false;
	if ( !$authors = get_post_meta( get_the_ID(), 'vvab_ymyl_boxes', true ) )
		return '';
	if ( count( array_filter($authors) ) == 0 )
		return '';
	if ( is_archive() || ( !is_front_page() && is_home() ) )
		return '';

	if ( $return_only )
		return do_shortcode('[ymyl-'.$box.']');

	echo do_shortcode('[ymyl-'.$box.']');

}

?>
