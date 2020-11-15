<?php
class FCPSettingsPage {

    private $s, $p, $st;

    public static function version() {
        return '1.0.0';
    }
    
    public function __construct($s, $p, $st = []) {

        $this->s = $s; // overall settings
        $this->p = $p; // the page preferences
        $this->st = FCPAdminFields::fileOrStructure( $st ); // structure or file

        add_action( 'admin_menu', [ $this, 'addPage' ] );
        add_action( 'admin_init', [ $this, 'saveSettings' ] );
    }

    public function addPage() {

        $p = $this->p;
        $draw = new FCPAdminFields( $this->s, $this->st );

        add_submenu_page(
            $p['parent_slug'],
            $p['page_title'],
            $p['menu_title'],
            $p['capability'],
            $p['menu_slug'],
            [ $draw, 'printSettings' ],
            $p['position']
        );
    }

    public function saveSettings() {
        foreach ( $this->st->structure as $b ) {
            foreach ( $b->fields as $c ) {
                // ++ add types filter here??
                register_setting( $this->s->prefix . $this->st->name . '_nonce', $this->s->prefix . $c->name );
            }
        }
    }
	
}
