<?php
/**
 * Dashboard / Common settings
*/

class FCP_Author_Boxes_Dashboard {

    private $structure, $prefix, $previews, $nonce;

    public function __construct($structureFile = '') {

        if ( !$structureFile )
            return;
    
        $this->structure = $this->getJsonFile( plugin_dir_path(__FILE__).$structureFile );
        $this->prefix = 'fcp-';
        $this->previews = plugin_dir_url(__FILE__) . 'previews/';
        $this->nonce = 'fcp-authors-settings';
    
        add_action( 'admin_menu', [$this, 'addDashboard'] );
        add_action( 'admin_init', [$this, 'saveSettings'] );
        add_action( 'admin_enqueue_scripts', [$this, 'colorPicker'] );
        add_action( 'admin_enqueue_scripts', [$this, 'dashboardScripts'] );
        
    }

    public function dashboardScripts($hook) {
        add_action( 'admin_footer', function() {
        
            ?><script type="text/javascript">
                jQuery( document ).ready( function($) {
                    var fcpABDGlob = {
                        "prefix": "<?php echo $this->prefix ?>",
                        "path": "<?php echo $this->previews ?>"
                    }

                    /* conditional fields */

                    // track changes in objects, effecting others
                    $.each( getAllObjects().effecting, function(k,$v) {
                        $v.change( function() {
                            showHideEffected();
                        });
                    });
                    
                    showHideEffected();

                    function showHideEffected() {
                        $.each( getAllObjects(true).effected, function(k,v) {
                            var currentValues = valuesByName( v.name );

                            v.$self.removeClass( 'fcp-active' );

                            if ( v.val.toString() === currentValues.toString() ) {
                                v.$self.addClass( 'fcp-active' );
                            }
                        });
                    }

                    function getAllObjects(effectedOnly) {
                        var effecting = {},
                            effected = [];
                        $( '[data-show-when]' ).each( function() {
                            // showMeWhen[0] == effectig.name, [1] == conditions
                            var showMeWhen = JSON.parse( $( this ).attr( 'data-show-when' ) );
                            
                            if ( !showMeWhen )
                                return;

                            if ( !effectedOnly ) {
                                var $effecting = $inputsByName( showMeWhen[0] );

                                if ( $effecting.length && !effecting[showMeWhen[0]] ) {
                                    effecting[showMeWhen[0]] = $effecting;
                                }
                            }
                            
                            var $effected = $( this );
                            
                            effected.push({
                                "name" : showMeWhen[0],
                                "val"  : showMeWhen[1],
                                "$self": $effected
                            });
                        });

                        return {
                            "effecting": effecting,
                            "effected" : effected
                        };
                    }

                    /* preview blocks */

                    // print previews

                    $.each( getAllPreviews().previews, function(k,v) {
                        // get the templates
                        $.get( fcpABDGlob.path + v.file + '?' + Math.random(), function(a) {
                            var $holder = v.$creator.closest( 'td' );
                            $holder.append( '<div class="fcp-preview"></div>' );
                            $holder.children( '.fcp-preview' )[0].fcpPreviewTemplate = {
                                "html": a,
                                "vars": {}
                            };
                        });
                        
                    });

                    // collect all previews
                    function getAllPreviews() {
                        var effecting = {},
                            previews = [],
                            templatesAreLoaded = false;
                            
                        if ( $( '.fcp-preview' ).length ) {
                            templatesAreLoaded = true;
                        }
                            
                        $( '[data-preview]' ).each( function() {
                            var $self = $( this ),
                                data  = JSON.parse( $self.attr( 'data-preview' ) ),
                                name  = clearName( $self.attr( 'name' ) );
                            
                            if ( !data )
                                return;

                            if ( !name ) { // for group of multiple objects
                                name = clearName( $self.find( 'input' ).attr( 'name' ) );
                            }

                            // collect previews (effected)
                            if ( data.file ) {
                                if ( !templatesAreLoaded ) {
                                    previews.push({
                                        "file"     : data.file,
                                        "$creator" : $self
                                    });
                                }
                                
                                effecting[name] = {
                                    "previews": [name],
                                    "default" : data.default,
                                    "$selfs"  : $inputsByName(name)
                                };
                            }
                            
                            // collect effecting objects
                            if ( data.effect ) {
                                effecting[name] = {
                                    "previews": data.effect,
                                    "default" : data.default,
                                    "$selfs"  : $inputsByName(name)
                                };
                            }

                        });
                        
                        return {
                            "effecting": effecting,
                            "previews" : previews
                        };
                    }

                    /* helping functions */

                    function $inputsByName(name) { // to mention inputs with multiple values
                        if ( !name )
                            return;

                        name = clearName( name );
                        return $( '[name="'+name+'"],[name="'+name+'[]"]' );
                    }

                    function valuesByName(name) {
                        if ( !name )
                            return;

                        name = clearName( name );
                        var $self = $inputsByName( name ),
                            type = $self.attr( 'type' ),
                            tag  = $self.prop( 'tagName' ).toLowerCase(),
                            vals  = [];

                        if ( type === 'checkbox' || type === 'radio' ) {
                            $( '[name="'+name+'[]"]:checked' ).each( function() {
                                vals.push( $( this ).val() );
                            });
                            return vals;
                        }

                        if ( tag === 'select' ) {
                            $( '[name="'+name+'[]"]>option:selected' ).each( function() {
                                vals.push ( $( this ).val() );
                            });
                            return vals;
                        }
                            
                        return [ $self.val() ];
                    }

                    function clearName(name) {
                        if ( !name )
                            return '';

                        if ( name.indexOf( fcpABDGlob.prefix ) !== 0 )
                            name = fcpABDGlob.prefix + name;
                            
                        if ( ~name.indexOf( '[]' ) )
                            name = name.substring(0, name.length - 2);

                        return name;
                    }
                    
                    function clearNameNoPrefix(name) {
                        return clearName( name ).substring( fcpABDGlob.prefix.length );
                    }

                });
            
            </script>
            <style>
                /* show-hide effecte fields */
                *[data-show-when] {
                    visibility:hidden;
                    opacity:0;
                    position:absolute;
                    z-index:-1;
                    pointer-events:none;
                    transition:opacity 0s ease, visibility 0s linear;
                }
                *.fcp-active[data-show-when],
                *[data-show-when=""] {
                    visibility:visible;
                    opacity:1;
                    position:static;
                    pointer-events:auto;
                    transition:opacity 0.3s ease, visibility 0s linear;
                }

                /* preview blocks */
                .fcp-preview {
                    display:inline-block;
                    width:240px;
                    height:80px;
                    vertical-align:top;
                    margin:0 15px;
                    position:relative;
                    opacity:0;
                    transition:opacity 0.3s ease;
                }
                .fcp-active.fcp-preview {
                    opacity:1;
                }
            </style><?php
        });
    }
    
    public function colorPicker($hook) {
        if ( !in_array( $hook, array('fcp-author_page_fcp-author-boxes') ) )
            return;

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        add_action( 'admin_footer', function() {
        
            ?><script type="text/javascript">
            
                jQuery( document ).ready( function($) {
                    $( '.color-picker' ).wpColorPicker( {
                        defaultColor: false,
                        change: function(event, ui){ },
                        clear: function(){ },
                        hide: true,
                        palettes: true
                    });
                });
            
            </script><?php
        });
    }

    public function addDashboard() {
        $parent_slug = 'edit.php?post_type=fcp-author';
        $page_title  = 'Author Boxes';
        $menu_title  = 'Settings';
        $capability  = 'edit_pages';
        $menu_slug   = 'fcp-author-boxes';
        $function    = [$this, 'printDashboard'];
        $position    = 20;
        add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function, $position );
    }

    public function getJsonFile($file = '') {
    
        if ( !$file || !is_file( $file ) )
            return;

		$content = file_get_contents( $file );
		return json_decode( $content );

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

    public function printDashboard() {
    
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
