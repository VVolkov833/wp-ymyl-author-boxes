<?php
/**
 * Dashboard / Common settings
*/

class FCP_Author_Boxes_Bulk {

    private $prefix, $nonce;

    public function __construct() {

        if ( !$structureFile )
            return;
    
        $this->prefix = 'fcp-';
        $this->nonce = 'fcp-authors-settings';
    
        add_action( 'admin_menu', [$this, 'addBulk'] );
        add_action( 'admin_init', [$this, 'saveSettings'] );
        
    }

    public function addBulk() {
        $parent_slug = 'edit.php?post_type=fcp-author';
        $page_title  = 'Bulk Author Boxes apply';
        $menu_title  = 'Bulk Operations';
        $capability  = 'edit_pages';
        $menu_slug   = 'fcp-author-boxes-bulk';
        $function    = [$this, 'printBulks'];
        $position    = 21;
        add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function, $position );
    }

    public function printField_checkbox($a) {
        ?>
        <tr <?php echo $a->showMeWhen ?>>
            <th><?php echo $a->title ?></th>
            <td>
                <fieldset
                    <?php echo $a->preview ?>
                >
                    <legend class="screen-reader-text"><span><?php echo $a->title ?></span></legend>
                    <?php
                        foreach ( $a->options as $b ) :
                    ?>
                        <label>
                            <input type="checkbox"
                                name="<?php echo $a->name ?>[]"
                                value="<?php echo $b->value ?>"
                                <?php echo in_array($b->value, $a->savedValue) ? 'checked' : '' ?>
                            >
                            <span><?php echo $b->title ?></span>
                            <p class="description"><?php echo $b->description ?></p>
                        </label>
                        <br>
                    <?php
                        endforeach;
                    ?>
                </fieldset>
            </td>
        </tr>
        <?php
    }

    public function printBulks() {
    
       $a = $this->structure;

    ?>

        <h1><?php echo $a->title ? $a->title : get_admin_page_title() ?></h1>
        <p><?php echo $a->description ? $a->description : "" ?></p>

        <form method="post" action="options.php" class="fcp-form">
            <?php settings_fields( $this->nonce ) ?>

            <?php
                foreach ( $a->structure as $b ) : 
            ?>

            <div id="<?php echo $b->name ?>">
                <h2><?php echo $b->title ?></h2>
                <p><?php echo $b->description ?></p>

                <table class="form-table">
                <tbody>

                <?php
                    foreach ( $b->fields as $c ) {
                        $methodName = 'printField_'.$c->type;
                        if ( method_exists( $this, $methodName ) ) {
                            $c->name = $this->prefix . $c->name;
                            $c->savedValue = get_option( $c->name );
                            $c->showMeWhen = $c->showMeWhen
                                ? "data-show-when='" . json_encode( $c->showMeWhen ) . "'"
                                : '';
                            $c->preview = $c->preview
                                ? "data-preview='".json_encode( $c->preview, JSON_HEX_APOS )."'"
                                : '';
                            $this->{$methodName}( $c );
                        }
                    }
                ?>

                </tbody>
                </table>
            </div>
            <?php
                endforeach;
            ?>

            <?php submit_button(); ?>
            
        </form>

    <?php

    }

    public function saveSettings() {
        // sanitize functinons https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/ as third argument
        foreach ( $this->structure->structure as $b ) {
            foreach ( $b->fields as $c ) {
                register_setting( $this->nonce, $this->prefix.$c->name );
            }
        }
    }
	
}
