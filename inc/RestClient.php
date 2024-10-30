<?php
namespace socbw\inc;

class RestClient {
    /** @var null */
    private static $instance = null;
    /**
     * @return null|RestClient
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private static $apiBaseUrl =  "https://business.connectid.io/api";
    private static $defaultHeaders = array( 'Content-Type' => 'application/json' );
    public static function Get($get_url){
        $apiKey = (strpos($get_url, '?') === false ? '?' : '&') . 'apiKey=' . OptionHelper::GetWooApiKey();
        $url = esc_url_raw(self::$apiBaseUrl . $get_url . $apiKey);
        $response = wp_remote_get($url, array('timeout' => 3600));
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body($response);
        if(is_wp_error($response_body) || $response_code == 500){
            return array('code'=> $response_code, 'error'=> 'Internal server error');
        }
        $result = json_decode($response_body);
        return array('code'=> $response_code, 'result'=> $result, 'error'=> $response->errors);
    }
    public static function Post($post_url, $data){
        return self::Request($post_url, 'POST', $data);
    }
    public static function Put($post_url, $data){
        return self::Request($post_url, 'PUT', $data);
    }
    public static function Request($post_url, $method, $data){
        if(empty($method)){
            $method = "POST";
        }
        if($data != null){
            $json_data = json_encode($data);
            $args = array('method'=> $method, 'timeout'=> 3600, 'headers' => self::$defaultHeaders, 'body' => $json_data );
        }
        else{
            $args = array('method'=> $method, 'timeout'=> 3600, 'headers' => self::$defaultHeaders );
        }
        $apiKey = (strpos($get_url, '?') === false ? '?' : '&') . 'apiKey=' . OptionHelper::GetWooApiKey();
        $url = esc_url_raw(self::$apiBaseUrl . $post_url . $apiKey);
        $response = wp_remote_request($url, $args );
        //print_r($response);
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        if(is_wp_error($response_body) || $response_code == 500){
            return array('code'=> $response_code, 'error'=> 'Internal server error');
        }
        $body = json_decode($response_body);
        return array('code'=> $response_code, 'body'=> $body, 'error'=> $response->errors);
    }
    public static function UploadFile($post_url, $file){
        
        $boundary = 'WebKitFormBoundary' .  uniqid();
        $payload = '';
        if ($file) {
            $payload .= '------' . $boundary . "\r\n";
            $payload .= 'Content-Disposition: form-data; name="logoFile"; filename="' . basename( $file['name'] ) . '"' . "\r\n";
            $payload .= 'Content-Type: '.$file["type"]  . "\r\n\r\n";
            $payload .= file_get_contents( $file["tmp_name"] );
            $payload .= "\r\n";
        }
        $payload .= '------' . $boundary . '--';

        $header = array('content-type' => 'multipart/form-data; boundary=----' . $boundary);
        $body = array(
            'method' => 'PUT',
            'timeout' => 60*1000,
            'headers' => $header,
            'body' => $payload
        );
        //print_r($payload);
        $apiKey = OptionHelper::GetWooApiKey();
        $url = esc_url_raw(self::$apiBaseUrl . $post_url. '?apiKey=' . $apiKey);
        $response = wp_remote_request($url, $body);
        //print_r($response);
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        if(is_wp_error($response_body) || $response_code == 500){
            return array('code'=> $response_code, 'error'=> 'Internal server error');
        }
        $body = json_decode($response_body);
        return array('code'=> $response_code, 'body'=> $body, 'error'=> $response->errors);
    }
}
?>