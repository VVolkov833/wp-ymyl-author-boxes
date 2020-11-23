<?php

/*
Plugin Name: YMYL Author Boxes
Description: Add YMYL and Author Boxes to your pages and posts
Version: 1.3.2
Requires at least: 4.7
Requires PHP: 5.2.4
Author: Firmcatalyst, Vadim Volkov
Author URI: https://firmcatalyst.com
License: GPL v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: fcp-author-boxes
Domain Path: languages
*/

class FCPAuthorBoxes {

    private $s, $d;

    public function __construct() {

        // as we are slightly dependent on other plugins (pll, yoast..)
        add_action( 'init', [ $this, 'init' ], 0 ); // plugins_loaded couldn't load pll_current_language
        
		// polylang support
		add_action( 'plugins_loaded', function() {
            if ( function_exists( 'pll_current_language' ) ) {
                add_filter( 'pll_get_post_types', function($post_types, $is_settings) {
                    $post_types['fcp-author'] = 'fcp-author';
                    return $post_types;
                }, 10, 2 );
            }
        });

	}
    
	public function init() {

		$d = false; // developers mode

		$s = (object) [];
		$s->dev_mode = $d;
		$s->prefix = 'fcpab_';
		$s->text_domain = 'fcp-author-boxes';
		$s->self_path = plugin_dir_path( __FILE__ );
		$s->css_ver = $d ? time() : '1.4.9';
		$s->js_ver = $d ? time() : '1.1.0';
		$s->css_ver_adm = $d ? time() : '1.4.8';
		$s->js_ver_adm = $d ? time() : '1.1.0';

        $this->s = $s;
        $this->d = $d;


		// ADMIN

        include_once( $this->s->self_path . 'classes/draw-fields-admin.class.php' );
        include_once( $this->s->self_path . 'classes/add-meta-box.class.php' );
		
		// add post type with the author boxes content
		include_once( $this->s->self_path . 'classes/add-post-type.class.php' );
		new FCPAddPostType(
            $this->s,
            [
                'name' => 'Author',
                'slug' => 'fcp-author',
                'plural' => 'Authors',
                'description' => 'YMYL Author Boxes with structured data support',
                'post-types' => ['fcp-author'],
                'fields' => [ 'title', 'editor', 'custom-fields', 'thumbnail' ],
                'hierarchical' => false,
                'public' => false,
                'gutenberg' => true,
                'gutenberg_allow' => [
                    'core/paragraph'
                ],
                'menu_position' => 71, // right after Users
                'menu_icon' => 'dashicons-buddicons-buddypress-logo',
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false
            ]
/*            ,
            [
                'file' => $this->s->self_path . 'structure/meta-authors.json'
            ]
//*/
        );

        // add the settings page and YMYL content
		include_once( $this->s->self_path . 'classes/add-settings-page.class.php' );
		new FCPSettingsPage(
            $this->s,
            [
                'parent_slug' => 'edit.php?post_type=fcp-author',
                'page_title' => 'Settings',
                'menu_title' => 'Settings',
                'capability' => 'edit_pages',
                'menu_slug' => 'fcp-author-settings',
                'position' => 20
            ],
            [
                'file' => $this->s->self_path . 'structure/page-settings.json'
            ]
        );
		
		// add meta box to pages and posts to turn on/off the boxes
//		if ( is_admin() ) {
            $posts_meta = FCPAdminFields::fileOrStructure( [
                    'file' => $this->s->self_path . 'structure/meta-posts.json'
            ]);
            $posts_meta = $this->addPageAuthorsCheckboxes( $posts_meta );
            $posts_meta->post_types = [ 'post', 'page' ];

            new FCPAddMetaBox(
                $this->s,
                $posts_meta
            );
//        }
		
        // bulk operations page
        // ++ separate save function to store post meta, not options
/*
        $bulk_options = $this->addBulkCheckboxes( [ 'post', 'page' ] );

        $authors_tabs = $this->selectAuthors();
        $authors_tabs[0] = 'YMYL Box';

        // ++ here goes the picked values
        
		new FCPSettingsPage(
            $this->s,
            [
                'parent_slug' => 'edit.php?post_type=fcp-author',
                'page_title' => 'Bulk',
                'menu_title' => 'Bulk',
                'capability' => 'edit_pages',
                'menu_slug' => 'fcp-author-bulk',
                'position' => 20
            ],
            [
                'structure' => (object) [
                    "title" => "",
                    "name" => "bulk",
                    "description" => "Bulk operations for YMYL and Author boxes",
                    "tabs" => $authors_tabs,
                    "structure" => [
                        (object) [
                            "name" => "ymyl-box",
                            "title" => "",
                            "fields" => $bulk_options
                        ]
                    ]
                ]
            ]
        );
//*/

		// ++ can check all the sizes first, and pick squares if exist
//        add_image_size( 'fcp-author', 512, 512, [ 'center', 'center' ] );

        add_action( 'admin_enqueue_scripts', [ $this, 'styleAdmin' ] );
        
        // USER

		add_action( 'wp_enqueue_scripts', [ $this, 'addStylesScripts' ], 20 );

		// print before or after the content
		if ( get_option( $this->s->prefix . 'ymyl-position' ) ) {
            add_filter( 'the_content', [ $this, 'contentPrintYMYL' ] );
        }
        if ( get_option( $this->s->prefix . 'authorbox-position' ) ) {
            add_filter( 'the_content', [ $this, 'contentPrintAuthors' ] );
        }

        // add shortcodes for boxes
		add_shortcode( 'fcp-ymyl-box', [ $this, 'shortcodeYMYL' ] );
		add_shortcode( 'fcp-author-boxes', [ $this, 'shortcodeAuthors' ] );

	}
////////////////////////////////////////////////////////////

    public function shortcodeYMYL() {
        return getContentYMYL();
    }
    
    public function shortcodeAuthors() {
        return getContentAuthors();
    }

    private function selectAuthors($post__in = []) {

        $query = [
            'post_type'             => 'fcp-author',
            'post_status'           => 'publish',
            'orderby'               => 'title',
            'order'                 => 'ASC',
            'nopaging'              => true
        ];

        if ( isset( $post__in[0] ) ) {
            $query['post__in'] = $post__in;
        }
        
        if ( function_exists( 'pll_current_language' ) ) {
            $query['lang'] = pll_current_language();
        }

        $listAuthors = new WP_Query( $query );

        if ( !$listAuthors->have_posts() ) {
            return;
        }

        $result = [];
        foreach( $listAuthors->posts as $v ) {
            $result[$v->ID] = (object) [
                'title' => $v->post_title,
                'slug' => $v->post_name,
                'content' => $v->post_content
            ];
        }

        return $result;
    }

    private function addBulkCheckboxes($types) {
        $result = [];
        foreach( $types as $type ) {
            $allPosts = new WP_Query( [
                'post_type'             => $type,
                'post_status'           => 'publish',
                'orderby'               => 'title',
                'order'                 => 'ASC',
                'nopaging'              => true
            ]);
            if ( !$allPosts->have_posts() ) {
                continue;
            }

            $fields = [];
            foreach( $allPosts->posts as $v ) {
                $fields[] = (object) [
                    "title" => $v->post_title,
                    "value" => $v->ID
                ];
            }
            $result[] = (object) [
                "type" => "checkbox",
                "name" => "post-id",
                "title" => get_post_type_object( $type )->labels->name,
                "options" => $fields
            ];
        }

        return $result;
    }

    private function addPageAuthorsCheckboxes($meta) {
        $listAuthors = $this->selectAuthors();
        if ( !$listAuthors ) {
            return $meta;
        }

        $fields = [];
        foreach( $listAuthors as $k => $v ) {
            $fields[] = (object) [
                "title" => $v->title,
                "value" => $k
            ];
        }
        $meta->structure[0]->fields[] = (object) [
            "type" => "checkbox",
            "name" => "show-authors",
            "title" => "Authors",
            "options" => $fields
        ];

        return $meta;
    }

	public function styleAdmin($hook) {
        if ( !in_array( $hook, [ 'post.php', 'post-new.php', 'fcp-author_page_fcp-author-boxes', 'fcp-author_page_fcp-author-bulk' ] ) ) {
            return;
        }

        wp_enqueue_style(
            'fcp-authors-admin',
            plugins_url( 'admin.css', __FILE__ ),
            false,
            $this->s->css_ver_adm
        );
	}

	
	public function addStylesScripts() {

		wp_enqueue_style( 'fcp-ymyl-authors', plugins_url( 'style.css', __FILE__ ), false, $this->s->css_ver );
		//wp_enqueue_script( 'fcp-ymyl-authors', plugins_url( 'base.js', __FILE__ ), [ 'jquery' ], $this->s->js_ver );

	}

	private function currentAuthors() {
        static $authors = false;
        
        if ( $authors !== false ) {
            return $authors;
        }

        if ( !$ids = get_post_meta( get_the_ID(), $this->s->prefix . 'show-authors', true ) ) {
            return;
        }
        
        $authors = $this->selectAuthors( $ids );
        
        if ( !$authors || !count( array_filter($authors) ) )
            return;
        
        return $authors;
	}
	
	public function getContentAuthors() {

        if ( !$authors = $this->currentAuthors() ) {
            return;
        }

        foreach ( $authors as $k => &$v ) {
            if ( !$img = get_post_thumbnail_id( $k ) ) {
                continue;
            }

            $img = wp_get_attachment_image_src( $img, 'full' );

            ob_start();

            ?>
            <div itemprop="image" itemscope itemtype="https://schema.org/ImageObject"
                class="fcp-author-image"
                style="background-image:url('<?php echo $img[0] ?>');"
            >
                <img src="<?php echo $img[0] ?>" alt="<?php echo $v->title ?>">
                <meta itemprop="url" content="<?php echo $img[0] ?>">
                <meta itemprop="width" content="<?php echo $img[1] ?>">
                <meta itemprop="height" content="<?php echo $img[2] ?>">
            </div>
            <?php

            $v->img = ob_get_contents();
            ob_end_clean();
            
        }
        unset( $v );
        
        $total = count( $authors );
        foreach ( $authors as $v ) {

            $v->content = $this->relAuthor( $v->content );
        
            ob_start();

            ?>
            <div class="fcp-author" id="<?php echo $v->slug ?>" itemscope itemid="<?php echo $v->slug ?>" itemtype="https://schema.org/Person">
                <div class="fcp-author-content">
                    <?php echo $v->img ?>
                    <div class="fcp-author-about">
                        <p class="fcp-author-title" itemprop="name"><?php echo $v->title ?></p>
                        <div class="fcp-author-description" itemprop="description">
                            <?php echo $v->content ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php

            $content .= ob_get_contents();
            ob_end_clean();

        }
        
        $content = '<div class="fcp-authors-wrap'.( $total > 1 ? ' fcp-authors-x2' : '' ).'">'
            .$content
            .'</div>';
                
        return $content;

	}
	
	private function relAuthor($c) {

        $dom = new DOMDocument();
        $dom->loadHTML( $c );

        $elements = $dom->getElementsByTagName( 'a' );

        if ( !$elements instanceof DOMNodeList ) {
            return $c;
        }
        foreach( $elements as $domElement ) {
            $rel = $domElement->getAttribute( 'rel' );
            $domElement->setAttribute( 'rel', $rel.' author' );
            break;
        }
        $c = $dom->saveHTML();
        return $c;
    }
	
	public function getContentYMYL() {
	
        if ( !get_post_meta( get_the_ID(), $this->s->prefix . 'ymyl-show', true ) ) {
            return;
        }
	
        $content = get_option( $this->s->prefix . 'ymyl-content' );
        $authors = $this->currentAuthors();
        
		$linked_authors = [];
		foreach ( $authors as $v ) {
            $linked_authors[] = '<a href="#'.$v->slug.'">'.str_replace( ' ', '&nbsp;', $v->title ).'</a>';
		}

		$linked_authors = $this->listWithAnd($linked_authors);
		
		$content = wpautop( str_replace( '%name', $linked_authors, $content ) );

		ob_start();

        ?>
        <div class="fcp-ymyl-wrap">
            <div class="fcp-ymyl fcp-closed-default">
                <div class="fcp-ymyl-content">
                    <?php echo $content ?>
                </div>
            </div>
        </div>
        <?php
        
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
	}
	
	public function contentPrintYMYL( $content ) {
        if ( !in_the_loop() ) {
            return $content;
        }
        
        return $this->getContentYMYL() . $content;
	}

	public function contentPrintAuthors( $content ) {
        if ( !in_the_loop() ) {
            return $content;
        }
        
        return $content . $this->getContentAuthors();
	}


	private function listWithAnd($arr) {

		if ( $arr[1] ) {
			$last = array_pop( $arr );
        }

		return implode( ', ', $arr ) . ( $last ? ' '.__( 'and', $this->s->text_domain ).' '.$last : '' );
	}
	
}

new FCPAuthorBoxes();


function fcp_ymyl_box() {
    echo do_shortcode( '[fcp-ymyl-box]' );
}

function fcp_author_boxes() {
    echo do_shortcode( '[fcp-author-boxes]' );
}
