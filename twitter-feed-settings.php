<?php
/**
 * Twitter Feed Shortcode - Settings
 */

class TwitterFeedSettings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Twitter Feed Settings',
            'Twitter Feed',
            'manage_options',
            'twitterfeed-admin',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'twitterfeed_settings' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Twitter Feed Settings</h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'twitterfeed_settings' );
                do_settings_sections( 'twitterfeed-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'twitterfeed_settings', // Option group
            'twitterfeed_settings', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'twitter_api_keys', // ID
            'Twitter API Keys', // Title
            array( $this, 'twitter_api_keys_callback' ), // Callback
            'twitterfeed-admin' // Page
        );

        add_settings_field(
            'consumer_key', // ID
            'Consumer Key', // Title
            array( $this, 'consumer_key_callback' ), // Callback
            'twitterfeed-admin', // Page
            'twitter_api_keys' // Section
        );

        add_settings_field(
            'consumer_secret',
            'Consumer Secret',
            array( $this, 'consumer_secret_callback' ),
            'twitterfeed-admin',
            'twitter_api_keys'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();

        if( isset( $input['consumer_key'] ) )
            $new_input['consumer_key'] = sanitize_text_field( $input['consumer_key'] );

        if( isset( $input['consumer_secret'] ) )
            $new_input['consumer_secret'] = sanitize_text_field( $input['consumer_secret'] );

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function twitter_api_keys_callback()
    {
        print '<p>Enter your API keys below (found at https://dev.twitter.com/apps):</p>';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function consumer_key_callback()
    {
        printf(
            '<input type="text" id="consumer_key" class="regular-text" name="twitterfeed_settings[consumer_key]" value="%s" />',
            isset( $this->options['consumer_key'] ) ? esc_attr( $this->options['consumer_key']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function consumer_secret_callback()
    {
        printf(
            '<input type="text" id="consumer_secret" class="regular-text" name="twitterfeed_settings[consumer_secret]" value="%s" />',
            isset( $this->options['consumer_secret'] ) ? esc_attr( $this->options['consumer_secret']) : ''
        );
    }

}

if( is_admin() )
    $twitterfeed_settings_page = new TwitterFeedSettings();