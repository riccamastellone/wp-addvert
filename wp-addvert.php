<?php
/*
  Plugin Name: WP Addvert
  Plugin URI: http://addvert.it
  Description: Aggiunge i meta tag necessari al funzionamento di Addvert e permette il tracciamento dell'ordine.
  Version: 1.3
  Author: Riccardo Mastellone
 */

class Addvert_Plugin {

    protected $_base = "http://addvert.it";
    protected $_meta_properties = array();
    protected $_meta_names = array();

    public function __construct() {

        register_activation_hook(__FILE__, array($this, 'addvert_add_defaults'));
          
        if (is_admin()) {
            add_action('admin_init', array($this, 'woo_check'));
            add_action('admin_init', array($this, 'addvert_init'));
            add_action('admin_menu', array($this, 'addvert_add_options_page'));
        } else {
            add_action('init',array($this, 'register_session'));
            add_action('wp_head', array($this, 'add_elements')); // Aggiungiamo i meta tag
            add_action('wp_enqueue_scripts', array($this, 'addvert_enqueue_scripts')); // Aggiungiamo lo script per l'add button
            add_action('woocommerce_single_product_summary', array($this, 'show_addvert_button'), 8); // Aggiungiamo l'add button
            add_action('woocommerce_thankyou', array($this, 'addvert_tracking')); // Tracciamo l'ordine
        }
    }
    
    /**
     * Salviamo il parametro, se presente, in sessione
     */
    function register_session(){
        if( !session_id()) {
            session_start();
        }
        if(!empty($_GET['addvert_token'])) {
            $_SESSION['addvert_token'] = $_GET['addvert_token'];
        }
    }
   
    /**
     * Recuperiamo i dati dell'ordine, chiediamo ad Addvert la chiave e inseriamo lo script nella pagina
     */
    function addvert_tracking() {
        $order_id = apply_filters('woocommerce_thankyou_order_id', empty($_GET['order']) ? 0 : absint($_GET['order']) );
        $order = new WC_Order($order_id);
        $options = get_option('addvert_options');
        // Calcoliamo la commissione sul totale dell'ordine senza le spese di spedizione
        $totale = $order->order_total - $order->order_shipping;
        
        // Facciamo la chiamata server side con il metodo token
        if(!empty($_SESSION['addvert_token'])) {
            file_get_contents($this->_base . '/api/order/send_order?ecommerce_id='.$options['addvert_id'].'&secret='.$options['addvert_secret'].'&tracking_id='.$order_id.'&total='.$totale.'&token='.$_SESSION['addvert_token']);
            unset($_SESSION['addvert_token']);
          }
  
        // METODO LEGACY
        // Facchiamo la chiamata server side con il metodo cookie
        //$order_key = file_get_contents($this->_base . '/api/order/prep_total?ecommerce_id=' . $options['addvert_id'] . '&secret=' . $options['addvert_secret'] . '&tracking_id=' . $order_id . '&total=' . $totale);
        //wp_enqueue_script('addvert-tracking-js', $this->_base . '/api/order/send_total?key=' . $order_key, array(), '', true);
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
            wp_enqueue_script('addvert-js', $this->_base . '/api/js/addvert-btn.js', array(), '1.0', true);
        }
    }

    function show_addvert_button() {
        $options = get_option('addvert_options');
        echo '<div class="addvert-btn" data-width="450" data-layout="'.$options['addvert_layout'].'"></div>';
    }

    function addvert_validate_options($input) {
        return $input;
    }

    function addvert_get_defaults() {
        return array(
            "addvert_id" => NULL,
            "addvert_secret" => NULL,
            "addvert_layout" => 'standard'
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
            
            $this->_meta_properties['og:url'] = get_permalink();
            $this->_meta_properties['og:title'] = $product->post->post_title;
            $this->_meta_properties['og:description'] = $product->post->post_excerpt ? $product->post->post_excerpt : $product->post->post_content;
            if (has_post_thumbnail()) {
                $this->_meta_properties['og:image'] = wp_get_attachment_url(get_post_thumbnail_id());
            }

            $this->_meta_names['addvert:type'] = 'product';
            

            
            $this->_meta_properties['og:site_name'] = strip_tags(get_bloginfo('name'));
            $this->_meta_properties['og:locale'] = strtolower(str_replace('-', '_', get_bloginfo('language')));

            
            $options = get_option('addvert_options');
            $this->_meta_names['addvert:ecommerce_id'] = $options['addvert_id'];

            $cat = wp_get_object_terms(get_the_ID(), 'product_cat');
            $this->_meta_names['addvert:category'] = !empty($cat) ? $cat[0]->name : '';

            $this->_meta_names['addvert:price'] = $product->get_price();

            $tags = wp_get_object_terms(get_the_ID(), 'product_tag');
            foreach ($tags as $tag) {
                $this->_meta_names['addvert:tag'][] = $tag->name;
            }
            $this->render_output();
        }
    }

    protected function render_output() {
        echo "\n<!-- Addvert Meta Tags | addvert.it -->\n";
        foreach ($this->_meta_properties as $property => $content) {
            $content = is_array($content) ? $content : array($content);

            foreach ($content as $content_single) {
                echo '<meta property="' . $property . '" content="' . esc_attr(trim($content_single)) . '" />' . "\n";
            }
        }
        
        foreach ($this->_meta_names as $property => $content) {
            $content = is_array($content) ? $content : array($content);

            foreach ($content as $content_single) {
                echo '<meta name="' . $property . '" content="' . esc_attr(trim($content_single)) . '" />' . "\n";
            }
        }
        echo '<!-- End Addvert Meta Tags -->';
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
                    <tr>
                        <th scope="row">Scegli il bottone che vuoi utilizzare</th>
                        <td>
                             <input style="position: relative;bottom: 15px;" type="radio" name="addvert_options[addvert_layout]" value="standard" <?php if($options['addvert_layout']== 'standard') echo 'checked="checked"';?>/><img src="<?php echo plugins_url( '/button_standard.png', __FILE__ ) ?>"><br>
                              <input style="position: relative;bottom: 15px;" type="radio" name="addvert_options[addvert_layout]" value="small" <?php if($options['addvert_layout']== 'small') echo 'checked="checked"';?>/> <img src="<?php echo plugins_url( '/button_small.png', __FILE__ ) ?>">
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
