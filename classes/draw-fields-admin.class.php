<?php

class FCPAdminFields {

    private $s, $st;

    public static function version() {
        return '1.0.0';
    }

    public function __construct($s, $st) {

        $this->s = $s;
        $this->st = $st;

    }

    public function printField_notice($a) {
        ?>
        <tr <?php echo $a->showMeWhen ?>>
            <th scope="row">
                <?php echo $a->title ?>
            </th>
            <td>
                <?php echo $a->text ?>
                <?php echo $a->after ?>
                <p class="description"><?php echo $a->description ?></p>
            </td>
        </tr>
        <?php
    }

    public function printField_text($a) {
        ?>
        <tr <?php echo $a->showMeWhen ?>>
            <th scope="row">
                <label for="<?php echo $a->name ?>"><?php echo $a->title ?></label>
            </th>
            <td>
                <input
                    type="text"
                    name="<?php echo $a->name ?>"
                    id="<?php echo $a->name ?>"
                    class="regular-text"
                    value="<?php echo esc_attr($a->savedValue) ?>"
                    <?php echo $a->preview ?>
                    <?php echo $a->size ? 'size="'.$a->size.'" style="width:auto;"' : '' ?>
                    placeholder="<?php echo $a->placeholder ?>"
                ><?php echo $a->after ?>
                <p class="description"><?php echo $a->description ?></p>
            </td>
        </tr>
        <?php
    }
    
    public function printField_textarea($a) {
        ?>
        <tr <?php echo $a->showMeWhen ?>>
            <th scope="row"><?php echo $a->title ?></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo $a->title ?></span></legend>
                    <p>
                        <textarea
                            name="<?php echo $a->name ?>"
                            id="<?php echo $a->name ?>"
                            rows="10" cols="50"
                            class="large-text code"
                            <?php echo $a->preview ?>
                            placeholder="<?php echo $a->placeholder ?>"
                        ><?php echo esc_textarea($a->savedValue) ?></textarea>
                    </p>
                    <p class="description"><?php echo $a->description ?></p>
                </fieldset>
            </td>
        </tr>
        <?php
    }
    
    public function printField_color($a) {
        ?>
        <tr <?php echo $a->showMeWhen ?>>
            <th scope="row">
                <label for="<?php echo $a->name ?>"><?php echo $a->title ?></label>
            </th>
            <td>
                <input
                    type="text"
                    name="<?php echo $a->name ?>"
                    id="<?php echo $a->name ?>"
                    value="<?php echo esc_attr($a->savedValue) ?>"
                    class="color-picker"
                    <?php echo $a->preview ?>
                >
                <p class="description"><?php echo $a->description ?></p>
            </td>
        </tr>
        <?php
    }
    
    public function printField_checkbox($a) {
        ?>
        <tr <?php echo $a->showMeWhen ?>>
            <th><?php echo $a->title ?></th>
            <td>
                <fieldset
                    <?php echo $a->preview ?>
                    class="fcp-limit-height"
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
                        </label>
                        <p class="description"><?php echo $b->description ?></p>
                    <?php
                        endforeach;
                    ?>
                </fieldset>
            </td>
        </tr>
        <?php
    }
    
    public function printField_select($a) {
        ?>
        <tr <?php echo $a->showMeWhen ?>>
            <th scope="row"><label for="<?php echo $a->name ?>"><?php echo $a->title ?></label></th>
            <td>
                <select
                    name="<?php echo $a->name ?>[]"
                    id="<?php echo $a->name ?>"
                    <?php echo $a->multiple ? 'multiple' : '' ?>
                    <?php echo $a->preview ?>
                >
                    <?php
                        foreach ( $a->options as $b ) :
                    ?>
                        <option
                            value="<?php echo $b->value ?>"
                            <?php echo in_array($b->value, $a->savedValue) ? 'selected' : '' ?>
                            >
                                <?php echo $b->title ?>
                            </option>
                        <p class="description"><?php echo $b->description ?></p>
                    <?php
                        endforeach;
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    public function printFields($structure, $values) {
        
        $classes = [];
        if ( $this->st->preferences && $this->st->preferences->context ) {
            $classes[] = 'fcp-meta';
            if ( $this->st->preferences->context == 'side' ) {
                $classes[] = 'fcp-meta-side';
            }
        } else {
            $classes[] = 'fcp-page';
        }
    
        foreach ( $structure as $b ) :
        ?>

        <div id="<?php echo $this->s->prefix . $b->name ?>" class="<?php echo implode(' ', $classes) ?>">
            <?php echo $b->title ? '<h2>'.$b->title.'</h2>' : '' ?>
            <?php echo $b->description ? '<p>'.$b->description.'</p>' : '' ?>

            <table class="form-table">
            <tbody>

            <?php
                foreach ( $b->fields as $c ) {

                    $methodName = 'printField_'.$c->type;
                    if ( method_exists( $this, $methodName ) ) {
                        $c->name = $this->s->prefix . $c->name;
                        $c->savedValue = $values[ $c->name ];
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
    }

	public function printMetaBoxes() {
		global $post;

        foreach ( $this->st->structure as $b ) {
            foreach ( $b->fields as $c ) {
                $name = $this->s->prefix . $c->name;
                $values[$name] = $c->savedValue ? $c->savedValue : get_post_meta( $post->ID, $name, true );
            }
        }

        wp_nonce_field( $this->st->name.'_nonce', 'meta_box_nonce' );
        
        $this->printFields( $this->st->structure, $values );
	}
	
    public function printSettings() {

        foreach ( $this->st->structure as $b ) {
            foreach ( $b->fields as $c ) {
                $name = $this->s->prefix . $c->name;
                $values[$name] = $c->savedValue ? $c->savedValue : get_option( $name );
            }
        }

        $a = $this->st;

    ?>

        <h1><?php echo $a->title ? $a->title : get_admin_page_title() ?></h1>
        <p><?php echo $a->description ? $a->description : "" ?></p>

        <form method="post" action="options.php">
            <?php
                settings_fields( $this->s->prefix . $a->name . '_nonce' );

                $this->printTabs( $this->st->tabs );

                $this->printFields( $a->structure, $values );

                submit_button();
            ?>
        </form>

    <?php

    }

    private function printTabs($tabs) { // a tabs version with no content changes
        if ( !is_array( $tabs ) ) {
            return;
        }
        
        $url = get_permalink();
        $tab = $_GET['fcp-author'] ? $_GET['fcp-author'] : 0;
        
        if ( !is_numeric( $tab ) || !$tabs[$tab] ) {
            exit;
        }
        
        $result = [];
        foreach( $tabs as $k => $v ) {
            $link = add_query_arg( ['fcp-author' => $k], $url );
            $result[] = '<a href="'.$link.'" class="'.($tab == $k ? 'fcp-active' : '').'">'.$v.'</a>';
            if ( $tab == $k ) {
                $author = $v;
            }
        }

        if ( count( $tabs ) > 1 ) {
            echo implode( ' | ', $result );
        }

        echo '<input type="hidden" name="fcp-author" value="'.$tab.'">';
        
        echo '<h2>Adding <u>'.$author.'</u> to the posts below</h2>';
    }
    
    public static function fileOrStructure($a = []) {
    
        if ( isset( $a['structure'] ) ) {
            return $a['structure'];

        } elseif ( isset( $a['file'] ) ) {

            if ( !is_file( $a['file'] ) ) {
                return;
            }
            $content = file_get_contents( $a['file'] );
            return json_decode( $content );

        } else {
            return $a;
        }

    }
}
