<?php
/*
Plugin Name: Rather Simple WooCommerce Author Taxonomy
Plugin URI:
Update URI: false 
Description: Adds an author taxonomy to products.
Version: 1.0
WC tested up to: 5.6
Author: Oscar Ciutat
Author URI: http://oscarciutat.com/code/
Text Domain: rather-simple-woocommerce-author-taxonomy
License: GPLv2 or later

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Rather_Simple_WooCommerce_Author_Taxonomy {
    
    /**
     * Plugin instance.
     *
     * @see get_instance()
     * @type object
     */
    protected static $instance = null;

    /**
     * Access this plugin’s working instance
     *
     * @wp-hook plugins_loaded
     * @return  object of this class
     */
    public static function get_instance() {
        
        if ( !self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;

    }

    /**
     * Used for regular plugin work.
     *
     * @wp-hook plugins_loaded
     * @return  void
     */
    public function plugin_setup() {
        
        // Init
        add_action( 'init', array( $this, 'load_language' ) );
        add_action( 'init', array( $this, 'register_taxonomy' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_init', array( $this, 'save_admin_settings' ), 0 );
        add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
        add_filter( 'template_include', array( $this, 'template_include' ), 99 );

        // Add form
        add_action( 'product_author_add_form_fields', array( $this, 'add_author_fields' ) );
        add_action( 'product_author_edit_form_fields', array( $this, 'edit_author_fields' ), 10 );
        add_action( 'created_product_author', array( $this, 'save_author_fields' ), 10, 2 );
        add_action( 'edit_product_author', array( $this, 'save_author_fields' ), 10, 2 );

        // Public actions
        add_action( 'woocommerce_author_taxonomy_show_product_author_name', array( $this, 'show_product_author_name' ) );
        add_action( 'woocommerce_author_taxonomy_show_author_thumbnail', array( $this, 'show_author_thumbnail' ) );
        
        // Add columns
        add_filter( 'manage_edit-product_author_columns', array( $this, 'product_author_columns' ) );
        add_filter( 'manage_product_author_custom_column', array( $this, 'product_author_column' ), 10, 3 );
    
    }
    
    /**
     * Constructor. Intentionally left empty and public.
     *
     * @see plugin_setup()
     */
    public function __construct() {}
    
    /**
     * load_language
     *
     * @since 1.0
     */
    function load_language() {
        load_plugin_textdomain( 'rather-simple-woocommerce-author-taxonomy', '', dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
    
    /**
     * register_taxonomy
     *
     * @since 1.0
     */
    function register_taxonomy() {
        $permalinks = get_option( 'wat_permalinks' );
        $product_tax_slug = empty( $permalinks['product_author_tax_base'] ) ? 'product_author' : $permalinks['product_author_tax_base'];
        
        $labels = array(
            'name' => __( 'Authors', 'rather-simple-woocommerce-author-taxonomy' ),
            'singular_name' => __( 'Author', 'rather-simple-woocommerce-author-taxonomy' ),
            'search_items' => __( 'Search Authors', 'rather-simple-woocommerce-author-taxonomy' ),
            'all_items' => __( 'All Authors', 'rather-simple-woocommerce-author-taxonomy' ),
            'parent_item' => __( 'Parent Author', 'rather-simple-woocommerce-author-taxonomy' ),
            'parent_item_colon' => __( 'Parent Author:', 'rather-simple-woocommerce-author-taxonomy' ),
            'edit_item' => __( 'Edit Author', 'rather-simple-woocommerce-author-taxonomy' ),
            'view_item' => __( 'View Author', 'rather-simple-woocommerce-author-taxonomy' ),
            'update_item' => __( 'Update Author', 'rather-simple-woocommerce-author-taxonomy' ),
            'add_new_item' => __( 'Add New Author', 'rather-simple-woocommerce-author-taxonomy' ),
            'new_item_name' => __( 'New Author Name', 'rather-simple-woocommerce-author-taxonomy' ),
            'not_found' => __( 'No authors found', 'rather-simple-woocommerce-author-taxonomy' ),
            'no_terms' => __( 'No authors', 'rather-simple-woocommerce-author-taxonomy' ),
            'items_list_navigation' => __( 'Authors list navigation', 'rather-simple-woocommerce-author-taxonomy' ),
            'items_list' => __( 'Authors list', 'rather-simple-woocommerce-author-taxonomy' )
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $product_tax_slug,
                'with_front' => false,
                'hierarchical' => true
            )
        );
        
        register_taxonomy( 'product_author', 'product', $args );
    }

    /**
     * Enqueues scripts and styles in the frontend.
     */
    function wp_enqueue_scripts(){
        wp_enqueue_style( 'wat-style', plugins_url( 'style.css', __FILE__ ) );
    }

    /**
     * admin_init
     *
     * @since 1.0
     */
    function admin_init() {
        add_settings_field(
            'woocommerce_product_product_author_slug',
            __( 'Product Author base', 'rather-simple-woocommerce-author-taxonomy' ),
            array( $this, 'product_tax_slug_input' ),
            'permalink',
            'optional',
            'product_author'
        ); 
    }
    
    /**
     * product_tax_slug_input
     *
     * @since 1.0
     */
    function product_tax_slug_input( $taxonomy_slug ) {
        $permalinks = get_option( 'wat_permalinks' );
    ?>
        <input name="wc_product_author_slug" type="text" class="regular-text code" value="<?php if ( isset( $permalinks['product_author_tax_base'] ) ) echo esc_attr( $permalinks['product_author_tax_base'] ); ?>" placeholder="<?php echo _x( 'product-author', 'slug', 'rather-simple-woocommerce-author-taxonomy' ) ?>" />
    <?php
    }
        
    /**
     * show_product_author_name
     *
     * @since 1.0
     */
    function show_product_author_name( $product_id ) {
        the_terms( $product_id, 'product_author' );
    }

    /**
     * show_author_thumbnail
     *
     * @since 1.0
     */
    function show_author_thumbnail( $author_id ) {
        $thumbnail_id = absint( get_term_meta( $author_id, 'thumbnail_id', true ) );
        if ( $thumbnail_id ) {
            $image = wp_get_attachment_thumb_url( $thumbnail_id );
        } else {
            $image = wc_placeholder_img_src();
        }
        echo '<img src="' . esc_url( $image ) . '" alt="' . esc_attr__( 'Thumbnail', 'woocommerce' ) .'" class="wp-post-image" />';
    }

    /**
     * save_admin_settings
     *
     * @since 1.0
     */
    function save_admin_settings() {
        if ( ! is_admin() ) {
            return;
        }
        
        $permalinks = array();
        
        if ( isset( $_POST['wc_product_author_slug'] ) ) {
            $permalinks['product_author_tax_base'] = untrailingslashit( woocommerce_clean( $_POST['wc_product_author_slug'] ) );
        }
        
        if ( !empty( $permalinks ) ) {
            update_option( 'wat_permalinks', $permalinks );
            flush_rewrite_rules();   
        }
    }
    
    /**
     * add_author_fields
     *
     * @since 1.0
     */
    public function add_author_fields() {
        ?>
        <div class="form-field term-thumbnail-wrap">
            <label><?php esc_html_e( 'Thumbnail', 'woocommerce' ); ?></label>
            <div id="product_author_thumbnail" style="float: left; margin-right: 10px;"><img src="<?php echo esc_url( wc_placeholder_img_src() ); ?>" width="60" height="60" /></div>
            <div style="line-height: 60px;">
                <input type="hidden" id="product_author_thumbnail_id" name="product_author_thumbnail_id" />
                <button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce' ); ?></button>
                <button type="button" class="remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce' ); ?></button>
            </div>
            <script type="text/javascript">
                // Only show the "remove image" button when needed
                if ( ! jQuery( '#product_author_thumbnail_id' ).val() ) {
                    jQuery( '.remove_image_button' ).hide();
                }
                // Uploading files
                var file_frame;
                jQuery( document ).on( 'click', '.upload_image_button', function( event ) {
                    event.preventDefault();
                    // If the media frame already exists, reopen it.
                    if ( file_frame ) {
                        file_frame.open();
                        return;
                    }
                    // Create the media frame.
                    file_frame = wp.media.frames.downloadable_file = wp.media({
                        title: '<?php esc_html_e( 'Choose an image', 'woocommerce' ); ?>',
                        button: {
                            text: '<?php esc_html_e( 'Use image', 'woocommerce' ); ?>'
                        },
                        library: {
                            type: [ 'image' ]
                        },
                        multiple: false
                    });
                    // When an image is selected, run a callback.
                    file_frame.on( 'select', function() {
                        var attachment = file_frame.state().get( 'selection' ).first().toJSON();
                        var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;
                        jQuery( '#product_author_thumbnail_id' ).val( attachment.id );
                        jQuery( '#product_author_thumbnail' ).find( 'img' ).attr( 'src', attachment_thumbnail.url );
                        jQuery( '.remove_image_button' ).show();
                    });
                    // Finally, open the modal.
                    file_frame.open();
                });
                jQuery( document ).on( 'click', '.remove_image_button', function() {
                    jQuery( '#product_author_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
                    jQuery( '#product_author_thumbnail_id' ).val( '' );
                    jQuery( '.remove_image_button' ).hide();
                    return false;
                });
                jQuery( document ).ajaxComplete( function( event, request, options ) {
                    if ( request && 4 === request.readyState && 200 === request.status
                        && options.data && 0 <= options.data.indexOf( 'action=add-tag' ) ) {
                        var res = wpAjax.parseAjaxResponse( request.responseXML, 'ajax-response' );
                        if ( ! res || res.errors ) {
                            return;
                        }
                        // Clear Thumbnail fields on submit
                        jQuery( '#product_author_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
                        jQuery( '#product_author_thumbnail_id' ).val( '' );
                        jQuery( '.remove_image_button' ).hide();
                        return;
                    }
                } );
            </script>
            <div class="clear"></div>
        </div>
        <?php
    }    
    
    /**
     * edit_author_fields
     *
     * @since 1.0
     */
    public function edit_author_fields( $term ) {
        $thumbnail_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
        if ( $thumbnail_id ) {
            $image = wp_get_attachment_thumb_url( $thumbnail_id );
        } else {
            $image = wc_placeholder_img_src();
        }
        ?>
        <tr class="form-field">
            <th scope="row" valign="top"><label><?php esc_html_e( 'Thumbnail', 'woocommerce' ); ?></label></th>
            <td>
                <div id="product_author_thumbnail" style="float: left; margin-right: 10px;"><img src="<?php echo esc_url( $image ); ?>" width="60" height="60" /></div>
                <div style="line-height: 60px;">
                    <input type="hidden" id="product_author_thumbnail_id" name="product_author_thumbnail_id" value="<?php echo $thumbnail_id; ?>" />
                    <button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce' ); ?></button>
                    <button type="button" class="remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce' ); ?></button>
                </div>
                <script type="text/javascript">
                    // Only show the "remove image" button when needed
                    if ( '0' === jQuery( '#product_author_thumbnail_id' ).val() ) {
                        jQuery( '.remove_image_button' ).hide();
                    }
                    // Uploading files
                    var file_frame;
                    jQuery( document ).on( 'click', '.upload_image_button', function( event ) {
                        event.preventDefault();
                        // If the media frame already exists, reopen it.
                        if ( file_frame ) {
                            file_frame.open();
                            return;
                        }
                        // Create the media frame.
                        file_frame = wp.media.frames.downloadable_file = wp.media({
                            title: '<?php esc_html_e( 'Choose an image', 'woocommerce' ); ?>',
                            button: {
                                text: '<?php esc_html_e( 'Use image', 'woocommerce' ); ?>'
                            },
                            library: {
                                type: [ 'image' ]
                            },
                            multiple: false
                        });
                        // When an image is selected, run a callback.
                        file_frame.on( 'select', function() {
                            var attachment = file_frame.state().get( 'selection' ).first().toJSON();
                            var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;
                            jQuery( '#product_author_thumbnail_id' ).val( attachment.id );
                            jQuery( '#product_author_thumbnail' ).find( 'img' ).attr( 'src', attachment_thumbnail.url );
                            jQuery( '.remove_image_button' ).show();
                        });
                        // Finally, open the modal.
                        file_frame.open();
                    });
                    jQuery( document ).on( 'click', '.remove_image_button', function() {
                        jQuery( '#product_author_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
                        jQuery( '#product_author_thumbnail_id' ).val( '' );
                        jQuery( '.remove_image_button' ).hide();
                        return false;
                    });
                </script>
                <div class="clear"></div>
            </td>
        </tr>
        <?php
    }    
    
    /**
     * save_author_fields
     *
     * @since 1.0
     */
    public function save_author_fields( $term_id, $tt_id = '' ) {
        if ( isset( $_POST['product_author_thumbnail_id'] ) ) {
            update_term_meta( $term_id, 'thumbnail_id', absint( $_POST['product_author_thumbnail_id'] ) );
        }
    }
    
    /**
     * product_author_columns
     *
     * @since 1.0
     */
    public function product_author_columns( $columns ) {
        $new_columns = array();
        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
            unset( $columns['cb'] );
        }
        $new_columns['thumb'] = __( 'Image', 'woocommerce' );
        return array_merge( $new_columns, $columns );
    }
    
    /**
     * product_author_column
     *
     * @since 1.0
     */
    public function product_author_column( $columns, $column, $id ) {
        if ( 'thumb' == $column ) {
            $thumbnail_id = get_term_meta( $id, 'thumbnail_id', true );
            if ( $thumbnail_id ) {
                $image = wp_get_attachment_thumb_url( $thumbnail_id );
            } else {
                $image = wc_placeholder_img_src();
            }
            // Prevent esc_url from breaking spaces in urls for image embeds
            // Ref: https://core.trac.wordpress.org/ticket/23605
            $image = str_replace( ' ', '%20', $image );
            $columns .= '<img src="' . esc_url( $image ) . '" alt="' . esc_attr__( 'Thumbnail', 'woocommerce' ) . '" class="wp-post-image" height="48" width="48" />';
        }
        return $columns;
    }
    
    /**
     * template_include
     *
     * @since 1.0
     */
    function template_include( $template ) {
        global $post;

        if ( is_tax( 'product_author' ) ) {
            if ( $file = locate_template( array( 'taxonomy-product_author.php' ) ) ) {
                $template = $file;
            } else {
                $template = plugin_dir_path( __FILE__ ) . 'templates/taxonomy-product_author.php';
            }
        }
        return $template;
    }
   
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action( 'plugins_loaded', array ( Rather_Simple_WooCommerce_Author_Taxonomy::get_instance(), 'plugin_setup' ) );
}