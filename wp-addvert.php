<?php
/*
  Plugin Name: WP Addvert
  Plugin URI: http://addvert.it
  Description: Aggiunge i meta tag necessari al funzionamento di Addvert e permette il tracciamento dell'ordine.
  Version: 1.8
  Author: Riccardo Mastellone
  Author: Salvatore Pelligra
 */

class Addvert_Plugin {

    const version = "1.8";

    protected $_base = "//addvert.it";
    protected $_meta_properties = array();
    protected $_meta_names = array();

    public function __construct()
    {

        register_activation_hook(__FILE__, array($this, 'addvert_add_defaults'));

        add_action('woocommerce_order_status_completed', array($this, 'addvert_tracking')); // Tracciamo l'ordine
        add_action('woocommerce_payment_complete', array($this, 'addvert_tracking')); // Tracciamo l'ordine
        add_action('woocommerce_checkout_update_order_meta', array($this, 'store_token')); // Token come meta dell'ordine

        if (is_admin()) {
            add_action('admin_init', array($this, 'woo_check'));
            add_action('admin_init', array($this, 'addvert_init'));
            add_action('admin_menu', array($this, 'addvert_add_options_page'));
        }
        else {
            add_action('init',array($this, 'register_session'));
            add_action('wp_head', array($this, 'add_elements')); // Aggiungiamo i meta tag
            add_action('wp_enqueue_scripts', array($this, 'addvert_enqueue_scripts')); // Aggiungiamo lo script per l'add button
            add_action('woocommerce_share', array($this, 'show_addvert_button'), 8); // Aggiungiamo l'add button
            add_action('addvert_share', array($this, 'show_addvert_button'), 8); // Aggiungiamo l'add button
        }
    }
    /**
     * Salviamo il token come meta dell'ordine
     */
    public function store_token($order_id)
    {
        $customer = new WC_Customer();

        if($customer->addvert_token) {
            update_post_meta( $order_id, '_addvert_token', $customer->addvert_token );
            $customer->__set('addvert_token', NULL);
        } else {
            update_post_meta( $order_id, '_addvert_token', NULL );
        }
    }

    /**
     * Salviamo il parametro, se presente, in sessione
     */
    public function register_session()
    {
        if(!empty($_GET['addvert_token'])) {
            $customer = new WC_Customer();
            $customer->__set('addvert_token', $_GET['addvert_token']);
        }
    }

    /**
     * Controlliamo se possiamo usara una connessione sicura
     * @return boolean
     */
    static private function check_ssl()
    {
        $w = stream_get_wrappers();
        return extension_loaded('openssl') && in_array('https', $w);
    }

    /**
     * Controlliamo se possiamo usare Curl
     * @return boolean
     */
    static private function use_curl()
    {
        return is_callable('curl_init');
    }

    /**
     * Recuperiamo i dati dell'ordine, chiediamo ad Addvert la chiave e inseriamo lo script nella pagina
     */
    public function addvert_tracking($order_id)
    {
        $order = new WC_Order($order_id);
        $options = get_option('addvert_options');
        // Calcoliamo la commissione sul totale dell'ordine senza le spese di spedizione
        $totale = $order->order_total - $order->order_shipping;

        $token = get_post_meta( $order_id, '_addvert_token');
        $token = $token[0];

        // Facciamo la chiamata server side con il metodo token
        if($token)
        {
            $wrapper = self::check_ssl() ? 'https:' : 'http:';
            $url = $wrapper . $this->_base . '/api/order/send_order?ecommerce_id='.$options['addvert_id'].'&secret='.$options['addvert_secret'].'&tracking_id='.$order_id.'&total='.$totale.'&token='.$token;
            $this->get_remote($url);
        }
    }

    /**
     * Impediamo che il plugin venga attivato senza WooCommerce
     */
    public function woo_check()
    {
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
        {
            $plugin = plugin_basename(__FILE__);
            deactivate_plugins($plugin);
            wp_die("Addvert ha bisogno che WooCommerce sia attivo!");
        }
    }

    /**
     * Quando avremo l'url definitivo userei l'url. Magari su CDN?
     */
    public function addvert_enqueue_scripts()
    {
        if (is_product())
        {
            // wp_enqueue_script supporta anche il pattern '//' per lasciare al browser la decisione 
            // se usare http o https
            wp_enqueue_script('addvert-js', $this->_base . '/api/js/addvert-btn.js', array(), '1.0', true);
        }
    }

    public function show_addvert_button()
    {
        $options = get_option('addvert_options');
        if( empty($options['addvert_shortcode']) )
            echo $this->render_button($options);
    }
    public function shortcode()
    {
        $options = get_option('addvert_options');
        return $this->render_button($options);
    }
    private function render_button($options)
    {
        return '<div class="addvert-btn" data-width="'. $options['addvert_width'] 
            .'" data-height="'. $options['addvert_height']
            .'" data-layout="'. $options['addvert_layout']
            .($options['addvert_nocounter'] ? '" data-no-counter="1' : '')
            .'"></div>';
    }

    public function addvert_validate_options($input)
    {
        return $input;
    }

    public function addvert_get_defaults()
    {
        return array(
            'addvert_id' => NULL,
            'addvert_secret' => NULL,
            'addvert_layout' => 'standard',
            'addvert_nocounter' => 0,
            'addvert_shortcode' => '0',
            'addvert_width'     => 450,
            'addvert_height'    => 30,
        );
    }

    public function addvert_add_defaults()
    {
        $tmp = get_option('addvert_options');

        if (($tmp['chk_default_options_db'] == '1') || (!is_array($tmp)))
        {
            $arr = $this->addvert_get_defaults();
            update_option('addvert_options', $arr);
        }
    }

    public function addvert_init()
    {
        register_setting('addvert_plugin_options', 'addvert_options', array($this, 'addvert_validate_options'));
    }

    public function addvert_add_options_page()
    {
        add_menu_page('Addvert', 'Addvert', 'manage_options', __FILE__, array($this, 'render_form'), plugin_dir_url(__FILE__) . 'assets/icon.png');
    }

    public function add_elements()
    {
        if (is_product())
        {

            $product = new WC_Product_External(get_the_ID());

            $this->_meta_properties['og:url'] = get_permalink();
            $this->_meta_properties['og:title'] = $product->post->post_title;
            $this->_meta_properties['og:description'] = 
                $product->post->post_excerpt ?: $product->post->post_content;

            if (has_post_thumbnail())
                $this->_meta_properties['og:image'] = wp_get_attachment_url(get_post_thumbnail_id());

            $this->_meta_names['addvert:type'] = 'product';

            $this->_meta_properties['og:site_name'] = strip_tags(get_bloginfo('name'));
            $this->_meta_properties['og:locale'] = strtolower(str_replace('-', '_', get_bloginfo('language')));


            $options = get_option('addvert_options');
            $this->_meta_names['addvert:ecommerce_id'] = $options['addvert_id'];

            $cat = wp_get_object_terms(get_the_ID(), 'product_cat');
            $this->_meta_names['addvert:category'] = !empty($cat) ? $cat[0]->name : '';

            $this->_meta_names['addvert:price'] = $product->get_price();

            $tags = wp_get_object_terms(get_the_ID(), 'product_tag');
            foreach ($tags as $tag)
            {
                $this->_meta_names['addvert:tag'][] = $tag->name;
            }
            $this->render_output();
        }
    }

    protected function render_output()
    {
        echo "\n<!-- Addvert Meta Tags | addvert.it | WP-Addvert ".self::version." -->\n";
        foreach ($this->_meta_properties as $property => $content)
        {
            $content = is_array($content) ? $content : array($content);

            foreach ($content as $content_single)
            {
                echo '<meta property="' . $property . '" content="' . esc_attr(trim($content_single)) . '" />' . "\n";
            }
        }

        foreach ($this->_meta_names as $property => $content)
        {
            $content = is_array($content) ? $content : array($content);

            foreach ($content as $content_single)
            {
                echo '<meta name="' . $property . '" content="' . esc_attr(trim($content_single)) . '" />' . "\n";
            }
        }
        echo '<!-- End Addvert Meta Tags -->';
    }

    private function get_remote($url)
    {
        if(self::use_curl())
        {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'WP-Addvert '.self::version);
            return curl_exec($ch);
        }

        return file_get_contents($url); 
    }

    public function render_form()
    {
        $options = get_option('addvert_options');
        $this_btn = $options['addvert_layout'];
        $layouts = $this->button_layouts();

        $url = "http:$this->_base";
        $urlec = "http:$this->_base/ecommerce";
        $updated = isset( $_GET['settings-updated'] );
        include __DIR__ . '/form.php';
    }

    private function button_layouts()
    {
        $json = $this->get_remote('http:'. $this->_base . '/api/button/layouts');
        if( empty($json) )
            return array();

        $json = json_decode($json, true);
        return $json['list'];
    }
}

$addvert = new Addvert_Plugin();
add_shortcode( 'addvert', array($addvert, 'shortcode') ); // Shortcode
