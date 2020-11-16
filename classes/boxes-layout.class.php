<?php

class FCPAuthorBoxesLayout {

    private $s, $m;

    public static function version() {
        return '1.0.0';
    }

    public function __construct($s, $m = []) {

        $this->s = $s; // overall settings
        $this->m = $m; // meta boxes structure

        if ( $this->m ) {
            add_action( 'add_meta_boxes', [ $this, 'addMetaBox' ] );
            add_action( 'save_post', [ $this, 'saveMetaBoxes' ] );
        }

    }

    public function addMetaBox() {

        $m = $this->m;

        $draw = new FCPAdminFields( $this->s, $m );

		add_meta_box(
            $m->name,
            $m->title,
            [ $draw, 'printMetaBoxes' ],
            $m->post_types,
            $m->preferences->context,
            $m->preferences->priority
        );
	}

	public function saveMetaBoxes($postID) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
		if ( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], $this->m->name.'_nonce' ) ) {
            return;
        }
		if ( !current_user_can( 'edit_post', $postID ) ) {
            return;
        }

        foreach ( $this->m->structure as $b ) {
            foreach ( $b->fields as $c ) {
                $name = $this->s->prefix . $c->name;
                update_post_meta( $postID, $name, $_POST[$name] );
            }
        }

	}

	
}
