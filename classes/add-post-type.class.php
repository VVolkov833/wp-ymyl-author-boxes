<?php
/**
 * Create custom post type "fcp-author" with meta boxes and gutenberg limit
*/

class FCPAddPostType {

    private $s, $p;

    public static function version() {
        return '1.0.0';
    }

    public function __construct($s, $p, $m = []) {

        $this->s = $s; // overall settings
        $this->p = $p; // post type prefs
        $m = FCPAdminFields::fileOrStructure( $m ); // meta boxes structure or file

        add_action( 'init', [ $this, 'addPostType' ] );

        if ( $p['gutenberg'] && $p['gutenberg_allow'] ) {
            add_filter( 'allowed_block_types', [ $this, 'limitGutenberg' ], 10, 2 );
        }

        if ( $m ) {
            $m->post_types = $m->post_types ? $m->post_types : $p['post-types'];
            $meta = new FCPAddMetaBox($s, $m);
            add_action( 'add_meta_boxes', [ $meta, 'addMetaBox' ] );
            add_action( 'save_post', [ $meta, 'saveMetaBoxes' ] );
        }

    }

    public function addPostType() {

        $p = $this->p;
    
        $labels = [
            'name'                => __( $p['plural'], $s['text_domain'] ),
            'singular_name'       => __( $p['name'], $s['text_domain'] ),
            'menu_name'           => __( $p['plural'], $s['text_domain'] ),
            'all_items'           => __( 'All ' . $p['plural'], $s['text_domain'] ),
            'view_item'           => __( 'View ' . $p['name'], $s['text_domain'] ),
            'add_new_item'        => __( 'Add New ' . $p['name'], $s['text_domain'] ),
            'add_new'             => __( 'Add New', $s['text_domain'] ),
            'edit_item'           => __( 'Edit ' . $p['name'], $s['text_domain'] ),
            'update_item'         => __( 'Update ' . $p['name'], $s['text_domain'] ),
            'search_items'        => __( 'Search ' . $p['name'], $s['text_domain'] ),
            'not_found'           => __( $p['name'] . ' Not Found', $s['text_domain'] ),
            'not_found_in_trash'  => __( $p['name'] . ' Not found in Trash', $s['text_domain'] ),
        ];
            
        $args = [
            'label'               => __( $p['slug'], $s['text_domain'] ),
            'description'         => __( $p['description'], $s['text_domain'] ),
            'labels'              => $labels,
            'supports'            => $p['fields'],
            'hierarchical'        => $p['hierarchical'],
            'public'              => $p['public'],
            'show_in_rest'        => $p['gutenberg'],
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => true,
            'menu_position'       => $p['menu_position'],
            'menu_icon'           => $p['menu_icon'],
            'can_export'          => true,
            'has_archive'         => $p['has_archive'],
            'exclude_from_search' => $p['exclude_from_search'],
            'publicly_queryable'  => $p['publicly_queryable'],
            'capability_type'     => 'page',
        ];
            
        register_post_type( $p['slug'], $args );
        
        // can add slug override and other here

    }
    
    public function limitGutenberg( $allowed_blocks ) {
    
        global $post;
        $p = $this->p;

        if ( $post->post_type !== $p['slug'] || !$p['gutenberg_allow'] ) {
            return $allowed_blocks;
        }

        return $p['gutenberg_allow'];
    }

}
