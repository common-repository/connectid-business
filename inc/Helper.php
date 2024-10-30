<?php
namespace socbw\inc;

class Helper{
    /** @var null */
    private static $instance = null;

    /**
     * @return array
     */
    public static function getPluginData() {
        return get_plugin_data(SO_CB_ROOT_FILE);
    }

    /**
     * @param string $notice
     */
    public static function showAdminNotice($notice = '',$type = 'success') {
        if (!empty($notice)) {
            //$type = 'success';
            $dismissible = true;
            $message = '';
            switch ($notice) {
                case 'socb-cannot-connect-server':
                    $message = __('Can\'t connect to server. Please try again.', SO_CB_SLUG);
                    break;
                case 'socb-settings-saved' :
                    $message = __('Settings saved successfully.', SO_CB_SLUG);
                    break;
                case 'socb-settings-failed' :
                    $message = __('Failed to configure, something went wrong.', SO_CB_SLUG);
                    break;
                case 'socb-get-list-failed' :
                    $message = __('Failed to get the request list, something went wrong.', SO_CB_SLUG);
                    break;
                 case 'socb-get-item-failed' :
                    $message = __('Failed to get the requested item, something went wrong.', SO_CB_SLUG);
                    break;
                case 'socb-approve-item-failed' :
                    $message = __('Failed to approve the requested item, something went wrong.', SO_CB_SLUG);
                    break;
                case 'socb-user-add-failed':
                    $message = __('Failed to add invited user. Please try again.', SO_CB_SLUG);
                    break;
                case 'socb-user-remove-failed':
                    $message = __('Failed to remove invited user. Please try again.', SO_CB_SLUG);
                    break;
                case 'socb-upload-logo-failed':
                    $message = __('Failed to upload logo', SO_CB_SLUG);
                    break;
                case 'socb-api_key_not_found':
                    $message = __('Setup woo commerce settings first', SO_CB_SLUG);
                    break;
            }
            if (empty($message) && !empty($notice)) {
                $message = __($notice, SO_CB_SLUG);
            }
            if (!empty($message)) {
                printf(
                    '<div class="notice notice-%s %s"><p>%s</p></div>',
                    $type,
                    (($dismissible) ? 'is-dismissible' : ''),
                    $message
                );
            }
        }
    }

    /**
     * @param string $type
     * @param array $additionalArgs
     * @return string
     */
    public static function getPluginAdminUrl($type = '', $additionalArgs = array()) {
        $args = array(
            'page' => str_replace('-', '_', SO_CB_SLUG)
        );
        if (!empty($type)) {
            $args['type'] = esc_html($type);
        }
        if (!empty($additionalArgs)) {
            $args = array_merge($args, $additionalArgs);
        }
        $url = add_query_arg($args,
            admin_url('tools.php')
        );
        return $url;
    }

    /**
     * @param string $plugin
     * @return bool
     */
    public static function isPluginEnabled($plugin = '') {
        $activatePlugins = (array)self::getActivePlugins();
        return (in_array($plugin, $activatePlugins));
    }

     /**
     * @param string $option
     * @param string $type
     * @return bool
     */
    public static function isEnabled($option = '', $type = 'integrations') {
        return filter_var(get_option(SO_CB_PREFIX . '_' . $type . '_' . $option, false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param $data
     * @return string
     */
    public function sanitizeData($data) {
        if (is_array($data)) {
            foreach ($data as &$value) {
                $value = sanitize_text_field($value);
            }
        } else {
            $data = sanitize_text_field($data);
        }
        return $data;
    }
    /**
     * JSON data to html table
     *
     * @param object $data
     *
     */
    public static function jsonToTable ($data)
    {
        $table = '<table class="json-table" width="100%">';
        foreach ($data as $key => $value) {
            $table .= '<tr valign="top">';
            if ( ! is_numeric($key)) {
                $table .= '<td><strong>'. $key .':</strong></td><td>';
            } else {
                $table .= '<td colspan="2">';
            }
            if (is_object($value) || is_array($value)) {
                $table .= self::jsonToTable($value);
            } else {
                $table .= $value;
            }
            $table .= '</td></tr>';
        }
        $table .= '</table>';
        return $table;
    }

    /**
     * @return null|Helper
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function GetAllUsers(){
        $args = array('role'=> 'Administrator', 'orderby'=> array('display_name'=> 'ASC'));
        $query = new \WP_User_Query($args);
        $users = $query->get_results();
       
        foreach($users as $user){
            $firstname = get_user_meta( $user->ID, 'billing_first_name', true );
            $lastname = get_user_meta( $user->ID, 'billing_last_name', true );
            if (isset($user->first_name) && !empty($user->first_name)) {
                $fullname = $user->first_name.' '.$user->last_name;
            } elseif (isset($firstname) && !empty($firstname)) {
                $fullname = $firstname.' '.$lastname;
            } else {
                $fullname = $user->user_login;
            }
            $user->name = $fullname;
            $user->email = $user->user_email;
        }
        return $users;
    }
    public static function SearchUsersByEmail($email) {
        $args =  array('search' => "{$email}",
        'search_columns' => array('user_email') );
        $query = new \WP_User_Query($args);
        $src_users = $query->get_results();
        $users = [];
        foreach($src_users as $user){
            $firstname = get_user_meta( $user->ID, 'billing_first_name', true );
            $lastname = get_user_meta( $user->ID, 'billing_last_name', true );
            if (isset($user->first_name) && !empty($user->first_name)) {
                $firstname = $user->first_name;
            } 
            if (isset($user->last_name) && !empty($user->last_name)) {
                $lastname = $user->last_name;
            } 
            if (!isset($firstname) || empty($firstname)) {
                $firstname = $user->user_login;
            }
            array_push($users, array('id'=>$user->ID, 'email'=> $user->user_email, 'login_name'=> $user->user_login, 'first_name'=> $firstname, 'last_name'=>$lastname ));
        }
        return $users;
    }
    public static function IfEmpty($obj, $altValue){
        return empty($obj) || !isset($obj) ? $altValue : $obj;
    }
    public static function FindUserByEmail($email, $users){
        foreach($users as $item){
            if( strtolower($item->email) == strtolower($email)) return $item;
        }
        return null;
    }
    public static function startsWith ($string, $startString) 
    { 
        $len = strlen($startString); 
        return (substr($string, 0, $len) === $startString); 
    } 
    public static function GetRemoveInvitedUserId($post_values, $prefix){
        $keys = array_keys($post_values);
        foreach($keys as $key){
            if(self::startsWith($key, $prefix))
                return str_replace($prefix,'', $key);
        }
        return null;
    }
    public static function CreateBlockContent($dir_name){
        $link_url = OptionHelper::GetRequestPortalLinkUrl();
        $link_text = OptionHelper::GetRequestPortalLinkText();
        $block_element = "el(
            'p',
            { 
                className: 'block-request-portal-link'
            },
            el('a', {
                style: blockStyle,
                target: '_blank',
                href: '$link_url'
            },'$link_text')
            )";
        $content = "( function( blocks, i18n, element ) {
            var el = element.createElement;
            var __ = i18n.__;
        
            var blockStyle = {
                padding: '5px 2px',
            };
        
            blocks.registerBlockType('socb-gutenberg/request-portal-link', {
                title: __( 'Request Portal Link', 'socb-cbw-sl' ),
                icon: 'admin-links',
                category: 'widgets',
                example: {},
                edit: function() {
                    return $block_element
                },
                save: function() {
                    return $block_element
                },
            } );
        } )( window.wp.blocks, window.wp.i18n, window.wp.element );";

        $file = $dir_name . 'request_portal_link.js';
        $fs = fopen( $file, "w" ); 
        $write = fputs( $fs, $content ); 
        fclose( $fs );
    }
}