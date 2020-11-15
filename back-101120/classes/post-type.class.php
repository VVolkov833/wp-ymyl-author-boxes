<?php
/**
 * Create custom post type "fcp-author" with meta boxes and gutenberg limit
*/

class FCP_Author_Boxes_PostType {

    public function __construct() {

        add_action( 'init', [$this, 'addPostType'] );
        
		add_action( 'add_meta_boxes', [$this, 'addMetaBoxes'] );
		add_action( 'save_post', [$this, 'saveMetaBoxes'] );
		
        add_filter( 'allowed_block_types', [$this, 'limitGutenberg'], 10, 2 );

    }

    public function addPostType() {

        $labels = [
            'name'                => __( 'Authos', 'fcp-author-boxes' ),
            'singular_name'       => __( 'Author', 'fcp-author-boxes' ),
            'menu_name'           => __( 'Authors', 'fcp-author-boxes' ),
            'all_items'           => __( 'All Authors', 'fcp-author-boxes' ),
            'view_item'           => __( 'View Author', 'fcp-author-boxes' ),
            'add_new_item'        => __( 'Add New Author', 'fcp-author-boxes' ),
            'add_new'             => __( 'Add New', 'fcp-author-boxes' ),
            'edit_item'           => __( 'Edit Author', 'fcp-author-boxes' ),
            'update_item'         => __( 'Update Author', 'fcp-author-boxes' ),
            'search_items'        => __( 'Search Author', 'fcp-author-boxes' ),
            'not_found'           => __( 'Author Not Found', 'fcp-author-boxes' ),
            'not_found_in_trash'  => __( 'Author Not found in Trash', 'fcp-author-boxes' ),
        ];
            
        $args = [
            'label'               => __( 'fcp-author', 'fcp-author-boxes' ),
            'description'         => __( 'YMYL Author Boxes with structured data support', 'fcp-author-boxes' ),
            'labels'              => $labels,
            'supports'            => [
                                        'title',
                                        //'excerpt',
                                        'editor',
                                        'custom-fields',
                                        'thumbnail'
                                    ],
            'hierarchical'        => false,
            'public'              => false,
            'show_in_rest'        => true, // turn on Gutenberg
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => true,
            'menu_position'       => 71, // right after Users
            'menu_icon'           => 'dashicons-buddicons-buddypress-logo',
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'page',
        ];
            
        register_post_type( 'fcp-author', $args );

    }

	public function addMetaBoxes() {
		add_meta_box( 'author-settings', __( 'Author Settings', 'fcp-author-boxes' ), [$this, 'drawMetaBoxes'], ['fcp-author'], 'normal', 'high' );
	}

	public function drawMetaBoxes() {
		global $post;
		wp_nonce_field( 'cases_meta_box_nonce', 'meta_box_nonce' );
		
		$v = get_post_custom( $post->ID );
		
		?>
		<label>
			Author's official page:
			<input type="text" name="link" size="30" value="<?php echo esc_attr( $v['link'][0] ); ?>">
		</label>
		<label for="verified_on">
			Use custom Verified box:
        </label>
        <input type="checkbox" id="verified_on" <?php echo $v['verified'][0] ? 'checked' : ''; ?>>
        <div>
            <label>
                Text for custom Verified box:
                <textarea name="verified" rows="4" cols="30"><?php echo esc_attr( $v['verified'][0] ); ?></textarea>
            </label>
        </div>
		<?php
	}

	public function saveMetaBoxes($post_id) {

/*
        $format = 'The %2$s contains %1$d monkeys.
        That\'s a nice %2$s full of %1$d monkeys.';
        echo sprintf($format, $num, $location);
//*/
	
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'vvab_ymyl_meta_box_nonce' ) ) return;
		if ( !current_user_can( 'edit_post', $post_id ) ) return;

		update_post_meta( $post_id, 'link', $_POST['link'] );
		update_post_meta( $post_id, 'verified', $_POST['verified'] );

	}

    public function limitGutenberg( $allowed_blocks, $post ) {
        if ( $post->post_type !== 'fcp-author' )
            return $allowed_blocks;

        return [
            'core/paragraph'
        ];
    }
	
}
