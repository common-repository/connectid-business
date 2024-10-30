<?php
 namespace socbw\inc;
class RequestPortalLink extends \WP_Widget {
 
    public function __construct() {
        // actual widget processes
        parent::__construct( 'request_portal_link', 'Request Portal Link' );
    }
 
    public function widget( $args, $instance ) {
        // outputs the content of the widget
        $login_user = wp_get_current_user();
        $user_id = null;
        if($login_user)
        {
            $user_id = $login_user->ID;
        }
        $link_url = OptionHelper::GetRequestPortalLinkUrl();
        $link_text = OptionHelper::GetRequestPortalLinkText();
        if($user_id){
            $link_url = $link_url . '?customerid=' . $user_id;
        }
        echo $args['before_widget'];
        echo '<a class="request-portal-link" href="' . $link_url . '" target="_blank">' .  $link_text . '</a>';
        echo $args['after_widget'];
    }
 
    public function form( $instance ) {
        // outputs the options form in the admin
    }
 
    public function update( $new_instance, $old_instance ) {
        // processes widget options to be saved
    }
}
 
?>