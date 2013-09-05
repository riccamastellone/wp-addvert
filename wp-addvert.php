<?php
/*
  Plugin Name: WP Addvert
  Plugin URI: http://addvert.it
  Description: Aggiunge i meta tag necessari al funzionamento di Addvert.
  Version: 1.0
  Author: Riccardo Mastellone
 */

class Addvert_Plugin {

    protected $_metas = array();

    public function __construct() {

        register_activation_hook(__FILE__, array($this, 'addvert_add_defaults'));

        if (is_admin()) {
            add_action('admin_init', array($this, 'woo_check'));
            add_action('admin_init', array($this, 'addvert_init'));
            add_action('admin_menu', array($this, 'addvert_add_options_page'));
        } else {
            add_action('wp_head', array($this, 'add_elements'));
            add_action('wp_enqueue_scripts', array($this, 'addvert_enqueue_scripts'));
            add_action('woocommerce_single_product_summary', array($this, 'show_addvert_button' ), 8 );
        }
    }
    
    /**
     * Impediamo che il plugin venga attivato senza WooCommerce
     */
    function woo_check() {
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $plugin = plugin_basename(__FILE__);
            deactivate_plugins($plugin);
            wp_die("Addvert ha bisogno che WooCommerce sia attivo!");
        }
    }
    
    /**
     * Quando avremo l'url definitivo userei l'url. Magari su CDN?
     */
    function addvert_enqueue_scripts() {
        if (is_product()) {
            wp_enqueue_script('addvert-js', plugins_url('/addvert-btn.js', __FILE__), array(), '1.0.0', true);
        }
    }
    
    function show_addvert_button() {
        echo '<div class="addvert-btn" data-width="450"></div>';
    }
    

    function addvert_validate_options($input) {
        return $input;
    }

    function addvert_get_defaults() {
        return array(
            "addvert_id" => NULL,
            "addvert_secret" => NULL
        );
    }

    function addvert_add_defaults() {

        $tmp = get_option('addvert_options');

        if (($tmp['chk_default_options_db'] == '1') || (!is_array($tmp))) {
            $arr = $this->addvert_get_defaults();
            update_option('addvert_options', $arr);
        }
    }

    function addvert_init() {
        register_setting('addvert_plugin_options', 'addvert_options', array($this, 'addvert_validate_options'));
    }

    function addvert_add_options_page() {
        add_menu_page('Addvert', 'Addvert', 'manage_options', __FILE__, array($this, 'addvert_render_form'), plugin_dir_url(__FILE__) . 'icon.png');
    }

    public function add_elements() {
        if (is_product()) {

            $product = new WC_Product_External(get_the_ID());
            $this->_metas['og:title'] = $product->post->post_title;
            $this->_metas['og:type'] = 'addvert:product';
            $this->_metas['og:url'] = get_permalink();

            $this->_metas['og:description'] = $product->post->post_excerpt ? $product->post->post_excerpt : $product->post->post_content;
            $this->_metas['og:site_name'] = strip_tags(get_bloginfo('name'));
            $this->_metas['og:locale'] = strtolower(str_replace('-', '_', get_bloginfo('language')));

            if (has_post_thumbnail()) {
                $this->_metas['og:image'] = wp_get_attachment_url(get_post_thumbnail_id());
            }

            $options = get_option('addvert_options');
            $this->_metas['addvert:ecommerce_id'] = $options['addvert_id'];

            $cat = wp_get_object_terms(get_the_ID(), 'product_cat');
            $this->_metas['addvert:category'] = !empty($cat) ? $cat[0]->name : '';

            $this->_metas['addvert:price'] = $product->get_price();

            $tags = wp_get_object_terms(get_the_ID(), 'product_tag');
            foreach ($tags as $tag) {
                $this->_metas['addvert:tag'][] = $tag->name;
            }
            $this->render_output();
        }
    }

    protected function render_output() {
        foreach ($this->_metas as $property => $content) {
            $content = is_array($content) ? $content : array($content);

            foreach ($content as $content_single) {
                echo '<meta property="' . $property . '" content="' . esc_attr(trim($content_single)) . '" />' . "\n";
            }
        }
    }

    function addvert_render_form() {
        ?>

        <div class="wrap">
            <div class="icon32" id="icon-options-general"><br></div>
            <h2><a href="http://addvert.it">Addvert</a> WP Plugin</h2>
            <p>L'ID e la chiave sono visibili nella pagina Account del proprio account e-commerce su Addvert</p>
            <form method="post" action="options.php">
                <?php settings_fields('addvert_plugin_options'); ?>
                <?php $options = get_option('addvert_options');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">ID E-Commerce</th>
                        <td>
                            <input name="addvert_options[addvert_id]" type='text' value='<?php echo $options['addvert_id'] ?>'/>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Chiave Segreta</th>
                        <td>
                            <input name="addvert_options[addvert_secret]" type='text' value='<?php echo $options['addvert_secret'] ?>'/>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
                </p>
            </form>
        </div>

        <?php
    }

}

$addvert = new Addvert_Plugin();