<?php
namespace socbw\inc;

class OptionHelper{
    /** @var null */
    private static $instance = null;
    /**
     * @return null|OptionHelper
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private static $optionNameCompanyId = SO_CB_PREFIX . '_settings_cbw_company_id';
    public static function GetCompanyId(){
        return get_option(self::$optionNameCompanyId);
    }
    public static function SaveCompanyId($companyId){
        if(update_option(self::$optionNameCompanyId,esc_html($companyId))){
            return get_option(self::$optionNameCompanyId);
        }
    }
    private static $optionNameApiKey = SO_CB_PREFIX . '_settings_cbw_company_woo_api_key';
    public static function GetWooApiKey(){
        return get_option(self::$optionNameApiKey);
    }
    public static function SaveWooApiKey($wooApiKey){
        if(update_option(self::$optionNameApiKey,esc_html($wooApiKey))){
            return get_option(self::$optionNameApiKey);
        }
    }
    private static $optionNameApiSecret = SO_CB_PREFIX . '_settings_cbw_company_woo_api_secret';
    public static function GetWooApiSecret(){
        return get_option(self::$optionNameApiSecret);
    }
    public static function SaveWooApiSecret($wooApiSecret){
        if(update_option(self::$optionNameApiSecret,esc_html($wooApiSecret))){
            return get_option(self::$optionNameApiSecret);
        }
    }
    private static $optionNameShopUrl = SO_CB_PREFIX . '_settings_cbw_company_woo_shop_url';
    public static function GetWooShopUrl(){
        return get_option(self::$optionNameShopUrl);
    }
    public static function SaveWooShopUrl($shopUrl){
        if(update_option(self::$optionNameShopUrl,esc_html($shopUrl))){
            return get_option(self::$optionNameShopUrl);
        }
    }

    private static $optionNameRPLinkUrl = SO_CB_PREFIX . '_settings_cbw_rp_link_url';
    public static function GetRequestPortalLinkUrl() {
       return get_option(self::$optionNameRPLinkUrl);
    }
    public static function SaveRequestPortalLinkUrl($url){
        if(update_option(self::$optionNameRPLinkUrl,esc_html($url))){
            return get_option(self::$optionNameRPLinkUrl);
        }
    }
    private static $optionNameRPLinkText = SO_CB_PREFIX . '_settings_cbw_rp_link_text';
    public static function GetRequestPortalLinkText() {
       return get_option(self::$optionNameRPLinkText);
    }
    public static function SaveRequestPortalLinkText($text){
        if(update_option(self::$optionNameRPLinkText,esc_html($text))){
            return get_option(self::$optionNameRPLinkText);
        }
    }
    
}
?>