<?php
namespace socbw\inc;

class Page {
    /** @var null */
    private static $instance = null;
    /**
     * @return null|Page
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private static $companyId = null;
    private static $company = null;
    public function addAdminMenu() {
        $pluginData = Helper::getPluginData();
        add_submenu_page(
            'tools.php',
            $pluginData['Name'],
            $pluginData['Name'],
            'manage_options',
            str_replace('-', '_', SO_CB_SLUG),
            array($this, 'Page_Load')
        );
    }
    private static $message = null;
    private static $tab = "";
    private static $plugin = null;
    private static $requests = [];
    private static $login_user = null;
    private static $languages = [];
    public function Page_Load(){
        self::$tab = (isset($_REQUEST['sofpn-tab'])) ? esc_html($_REQUEST['sofpn-tab']) : false;
        self::$plugin = Helper::getPluginData();
        self::$plugin['AdminUrl'] = Helper::getPluginAdminUrl();
        self::$companyId = OptionHelper::GetCompanyId();
        self::$login_user = wp_get_current_user();
        $wp_users = Helper::SearchUsersByEmail(self::$login_user->user_email);
        if(count($wp_users) > 0 ){
            self::$login_user->first_name = $wp_users[0]['first_name'];
            self::$login_user->last_name = $wp_users[0]['last_name'];
        }
        $response = RestClient::Get('/languages');
        if($response['error'] != null){
            Helper::showAdminNotice('socb-cannot-connect-server', 'error');
            return;
        }
        self::$languages = $response['result']->items;

        if(self::$companyId != null){
            $response = RestClient::Get('/adapter/woo/companies/'. self::$companyId);
            if($response['error'] != null){
                Helper::showAdminNotice('socb-cannot-connect-server', 'error');
                return;
            }
            self::$company = $response['result'];
        }
        if(empty(self::$tab) && self::$companyId != null){
            self::RequestListPage_Load();
        }
        else if(self::$tab == 'new-customer' || (empty(self::$tab) && self::$companyId == null)){
            self::NewCustomerPage_Load();
        }
        self::Page_Render();
    }
    private static function Page_Render(){
        ?>
        <form name="formSetup" id="formSetup" method="post" action=""  enctype='multipart/form-data'>
        <div class="sofpn-body">
            <div class="container">
                <h1 class="">
                    <?= self::$plugin['Name'] ?>
                    <span><?php printf('v%s', self::$plugin['Version']); ?></span>
                </h1>
                <nav class="sofpn-nav-tab-wrapper">
                    <?php if(self::$companyId != null) { ?>
                        <a  class="<?= (empty(self::$tab) ? 'sofpn-nav-tab sofpn-nav-tab-active' : 'sofpn-nav-tab') ?>"
                            href="<?= self::$plugin['AdminUrl'] ?>"><?= _e('GDPR Requests', SO_CB_SLUG) ?>
                        </a>
                    <?php } ?>
                    <a class="<?= (self::$tab=='new-customer' || (empty(self::$tab) && self::$companyId == null) ? 'sofpn-nav-tab sofpn-nav-tab-active' : 'sofpn-nav-tab') ?>"
                        href="<?= self::$plugin['AdminUrl'] ?>&sofpn-tab=new-customer"><?= _e('Setup Customer', SO_CB_SLUG) ?>
                    </a>
                </nav>
                <?php
                if(empty(self::$tab) && self::$companyId != null){
                    self::RequestListPage_Render();
                }
                else if(self::$tab == 'new-customer' || (empty(self::$tab) && self::$companyId == null)){
                    self::NewCustomerPage_Render();
                }
                ?>
            </div>
        </div>
        </form>
        <?php
    }
    public static function NewCustomerPage_Load(){
        //print_r($_POST);
        if(isset($_FILES['companyLogo']) && !empty($_FILES['companyLogo']['tmp_name'])){
            //print_r($_FILES);
            $upload_api_url = '/adapter/woo/companies/'.self::$companyId.'/companyLogo/file';
            $uploadResponse = RestClient::UploadFile($upload_api_url, $_FILES['companyLogo']);
            if($uploadResponse['error'] != null){
                self::$message = array( 'code'=>'socb-upload-logo-failed', 'type'=> 'error');
                return false;
            }
        }
        $remove_user_id = Helper::GetRemoveInvitedUserId($_POST, 'submitRemove_');
        if( $remove_user_id != null){
            $remove_api_url = '/adapter/woo/companies/' . self::$companyId . '/employees/' . $remove_user_id . '/remove';
            //print_r('Remove API: '.  $remove_api_url);
            $removeUserResponse = RestClient::Put($remove_api_url, array('t'=> $remove_user_id));
            if($removeUserResponse['error'] != null){
                self::$message = array( 'code'=>'socb-user-remove-failed', 'type'=>'error');
                return false;
            }
        }
        if(isset($_POST['submitNewUser'])){
			$post_newUser = sanitize_email($_POST['newUser']);
            $arg_invite_data = array(
                'email' => Helper::IfEmpty($post_newUser, ''),
                'clientUrl' => "trial/create-pwd",
                'roles' => array("SystemManager", "SystemUser")
            );
            //print_r($arg_invite_data);
            $inviteUserResponse = RestClient::Post('/adapter/woo/companies/'.self::$companyId.'/employees/invite', $arg_invite_data);
            if($inviteUserResponse['error'] != null){
                self::$message = array( 'code'=>'socb-user-add-failed', 'type'=> 'error');
                return false;
            }
        }
        if (isset($_POST['submit']) && check_admin_referer('save_settings', 'settings_nonce')) {
			$post_companyName = sanitize_text_field($_POST['companyName']);
			$post_wooApiKey = sanitize_key($_POST['wooApiKey']);
			$post_wooApiSecret = sanitize_key($_POST['wooApiSecret']);
			$post_shopUrl = esc_url_raw($_POST['shopUrl']);
			$post_companyLanguage = sanitize_text_field($_POST['companyLanguage']);
			$post_requestPortalLinkText = sanitize_text_field($_POST['requestPortalLinkText']);
			$post_requestPortalLinkUrl = esc_url_raw($_POST['requestPortalLinkUrl']);
            $arg_company_data = array(
                'companyId'=> Helper::IfEmpty(self::$companyId, null),
                'companyName' => Helper::IfEmpty($post_companyName, null),
                'wooApiKey'=> Helper::IfEmpty($post_wooApiKey, null),
                'wooApiSecret'=> Helper::IfEmpty($post_wooApiSecret, null),
                'websiteUrl'=> Helper::IfEmpty(get_site_url(), null),
                'email'=> Helper::IfEmpty(self::$login_user->user_email, null),
                'shopUrl'=> Helper::IfEmpty($post_shopUrl, null),
                'languageCulture'=> Helper::IfEmpty($post_companyLanguage, 'en-US'),
                'firstName' => self::$login_user->first_name,
                'lastName' => self::$login_user->last_name
            );
            //print_r($arg_company_data);
            $response = RestClient::Post('/adapter/woo/companies', $arg_company_data);
            if($response['code'] != 200){
                if($response['body']->message && in_array($response['body']->message, array('ValidationException', 'CompanyExistByNameExact'))) {
                    $issue_messages ='';
                    if($response['body']->issues) {
                        foreach($response['body']->issues as $issue) {
                            $msgs = '';
                            if($issue->messages) {
                                foreach($issue->messages as $msg){
                                    $msgs .= $msg . '<br/>';
                                }
                            }
                            $issue_messages .= $issue->key . '<br/>' . $msgs;
                        }
                    }
                    self::$message = array( 'code'=> $issue_messages, 'type'=> 'error');
                    return false;
                }
                else {
                    echo "<br/>";
                    print_r($response);
                } 
            }
            
            if ($response['error'] != null) {
                self::$message = array( 'code'=>'socb-settings-failed', 'type'=> 'error');
                return false;
            }
            if ($response['body']->id != null) {
                self::$companyId = $response['body']->id;
                OptionHelper::SaveCompanyId($response['body']->id);
                OptionHelper::SaveRequestPortalLinkText($post_requestPortalLinkText);
                OptionHelper::SaveWooApiKey(Helper::IfEmpty($post_wooApiKey, null));
                OptionHelper::SaveWooApiSecret(Helper::IfEmpty($post_wooApiSecret, null));
                OptionHelper::SaveWooShopUrl(Helper::IfEmpty($post_shopUrl, null));
                Helper::CreateBlockContent(plugin_dir_path( __FILE__ ));
            }
            self::$message = array( 'code'=> 'socb-settings-saved', 'type'=> 'success');
        }
        if (self::$companyId != null) {
            $response = RestClient::Get('/adapter/woo/companies/'. self::$companyId);
            if ($response['error'] != null) {
                self::$message = array( 'code'=>'socb-cannot-connect-server', 'type'=> 'error');
                return false;
            }
            self::$company = $response['result'];
            $rpLinkUrl = $post_requestPortalLinkUrl;
            if(empty($rpLinkUrl)) $rpLinkUrl = self::$company->requestPortalLink . '/request';
            OptionHelper::SaveRequestPortalLinkUrl($rpLinkUrl);
        }
        self::$message = null;
    }
    public static function NewCustomerPage_Render() {
        ?>
        <?php if(self::$message != null) Helper::showAdminNotice(self::$message['code'], self::$message['type']); ?>
        <?php wp_nonce_field('save_settings', 'settings_nonce'); ?>
            <div class="row" style="padding: 0 12px;">
                <label><?= _e('Company Name', SO_CB_SLUG) ?></label>
                <div class="input-group">
                    <input type="text" placeholder="<?= _e('Company Name', SO_CB_SLUG) ?>" name="companyName" class="regular-text" id="companyName"
                     value="<?= esc_attr(Helper::IfEmpty(self::$company->name, $post_companyName)) ?>" required="required"/>
                </div>
                <label><?= _e('Language', SO_CB_SLUG) ?></label>
                <div class="input-group">
                    <select name="companyLanguage" style="max-width: initial;" id="companyLanguage" required="required">
                    <?php 
                    foreach (self::$languages as $lang) { 
                    ?>
                    <option <?= ($lang->languageCode == esc_attr(Helper::IfEmpty(self::$company->settings->languageCulture, $post_companyLanguage))? "selected": "") ?> value="<?= esc_attr($lang->languageCode) ?>"><?= esc_attr($lang->localizedName) ?></option>
                    <?php } ?>
                    </select>
                </div>
                <fieldset>
                    <legend><?php _e('WooCommerce Settings', SO_CB_SLUG); ?></legend>
                <label><?= _e('API Key', SO_CB_SLUG) ?></label>         
                <div class="input-group">
                    <input type="text" placeholder="<?= _e('WooCommerce API Key', SO_CB_SLUG) ?>" name="wooApiKey" class="regular-text" id="wooApiKey"
                           value="<?= esc_attr(Helper::IfEmpty(OptionHelper::GetWooApiKey(), $post_wooApiKey)) ?>" required="required"/>
                </div>
                <label><?= _e('API Secret', SO_CB_SLUG) ?></label>
                <div class="input-group">
                    <input type="text" placeholder="<?= _e('WooCommerce API Secret', SO_CB_SLUG) ?>" name="wooApiSecret" class="regular-text" id="wooApiSecret"
                        value="<?= esc_attr(Helper::IfEmpty(OptionHelper::GetWooApiSecret(), $post_wooApiSecret)) ?>" required="required"/>
                </div>
                <label><?= _e('Shop Url', SO_CB_SLUG) ?></label>
                <div class="input-group">
                    <input type="text" placeholder="<?= _e('Shop Url', SO_CB_SLUG) ?>" name="shopUrl" class="regular-text" id="shopUrl"
                           value="<?= Helper::IfEmpty(OptionHelper::GetWooShopUrl(), $post_shopUrl)  ?>" required="required"/>
                </div>
                </fieldset>
                <?php if(self::$companyId != null) { ?>
                <fieldset>
                    <legend><?php _e('Logo', SO_CB_SLUG); ?></legend>
                    <div class="input-group">
                        <div class="logo-conatiner">
                            <img id="logo" src="<?= self::$company->logoUrl?>" />
                        </div>
                        <div class="logo-upload-container">
                            <p>
                                <?= _e('This logo will be applied to all users. Square images work best, and should be no bigger than 160px in width or height. Format can be .svg, .jpg, .png, or .gif', SO_CB_SLUG) ?>
                            </p>
                            <div class="upload-wraper">
                                <button class="btn-upload" disabled ><?= _e('Choose Logo', SO_CB_SLUG) ?></button>
                                <input class="file-upload" accept="image/jpeg, image/png" type="file" name="companyLogo" id="companyLogo" onchange="showLogo(this);"  />
                            </div>
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend><?= _e('Request Portal Link', SO_CB_SLUG) ?></legend>
                    <?php 
                    $rpLinkUrl = OptionHelper::GetRequestPortalLinkUrl();
                    $rpLinkText = OptionHelper::GetRequestPortalLinkText();
                    if(empty($rpLinkUrl)) $rpLinkUrl = self::$company->requestPortalLink . '/request';
                    if(empty($rpLinkText)) $rpLinkText = 'Request for data';
                    ?>
                    <div class="input-group">
                    <label style="color:#000"><?= _e('This is the URL of the portal from which your customers can request data. You can make this portal available by adding a link to your website, or however you choose.', SO_CB_SLUG) ?></label>
                    <input type="hidden" name="requestPortalLinkUrl" id="requestPortalLinkUrl" value="<?= esc_url($rpLinkUrl) ?>" />
					<a href="<?= esc_url($rpLinkUrl) ?>?customerid=<?= self::$login_user->ID ?>" target="_blank"><?= _e('Request Portal', SO_CB_SLUG) ?></a>
                    </div>
                    <div class="input-group">
                    <label style="color:#000">
                        <?= _e('You can customize the welcome message for your request portal, as well as many of the messages used in various screens throughout the portal', SO_CB_SLUG) ?>
                    </label>
                    <a target="_blank" href="<?= esc_url(self::$company->requestPortalLink . '/edit?username=' .  self::$login_user->user_email)  ?>"><?= _e('Customize Request Portal', SO_CB_SLUG) ?></a>
                    </div>
                    <div class="input-group">
                    <label style="color:#000"><?= _e('Request portal link text in this site', SO_CB_SLUG) ?></label>
                    <input type="text" placeholder="<?= _e('Text', SO_CB_SLUG) ?>" name="requestPortalLinkText" class="regular-text" id="requestPortalLinkText"
                           value="<?= esc_attr($rpLinkText) ?>" />
                    </div>
                </fieldset>
                <?php } ?>
                <?php submit_button(); ?>
                <?php if(self::$companyId != null) { ?>
                <fieldset>
                    <legend><?php _e('Invited Users', SO_CB_SLUG); ?></legend>
                    <div class="input-group">
                    <label><?php _e('Invite New User', SO_CB_SLUG); ?></label>
                    <select name="newUser" id="newUser">
                    <?php
                    $wp_users = Helper::GetAllUsers();
					if($wp_users){
						foreach($wp_users as $user){
							if( Helper::FindUserByEmail($user->email, self::$company->employees) == null )
							{
								echo "<option value='".$user->email."'>".$user->name."</option>";
							}
						}
					}
                    ?>
                    </select>
                    <input style="float:right;margin-top:10px;width:14%;" class="button button-primary" type="submit" name="submitNewUser" id="submitNewUser" value="Add" />
                    </div>
                    <div class="input-group">
                                <?php 
								if (self::$company->employees){
									foreach (self::$company->employees as  $user) { 
										if($user->name == $user->email){
											$wp_user = Helper::FindUserByEmail($user->email, $wp_users);
											if($wp_user != null) $user->name = $wp_user->name;
										}
                                    ?>
                                    <div class="invited-user">
                                    <div class="user-info">
                                     <div class="user-name"><?= $user->name ?> (<span><?= $user->email ?></span>)</div>
                                     <div class="user-roles"><?= implode(', ', $user->roles) ?></div>
                                    </div>
                                      <?php if(!in_array('Owner', $user->roles)) {?>
                                            <input class="button button-default btn-remove-user" type="submit" name="submitRemove_<?=$user->id?>" value="Remove" />
                                        <?php } ?>
                                    </div>
                                <?php } } ?>
                    </div>
                </fieldset>
                <?php } ?>
            </div>
            <script type="text/javascript">
                function showLogo(sender) {
                    if (sender.files && sender.files[0]) {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            document.getElementById('logo').setAttribute('src', e.target.result);
                        };

                        reader.readAsDataURL(sender.files[0]);
                    }
                }
            </script>
        <!-- </form> -->
        <?php
    }
    public static function RequestListPage_Load(){
        $apiKey = OptionHelper::GetWooApiKey();
        if(!isset($apiKey)){
            self::$message = array('code'=> 'socb-api_key_not_found', 'type'=> 'error');
            return;
        }
        else {
            
            $url = '/adapter/woo/companies/' . self::$company->id . '/requests';
            $response = RestClient::Get($url);
            if($response['error'] != null){
                self::$message = array('code'=> 'socb-get-list-failed', 'type'=>'error');
                return;
            }
            self::$requests = $response['result']->items;
            self::$message = null;
        }
    }
    public static function RequestListPage_Render(){
        ?>
        <script type="text/javascript">
			function toClientTime(server_time_str){
				try {
				 var server_time = new Date(server_time_str); 
				 var tz = -1 * (new Date().getTimezoneOffset());
				 var local_time =  new Date(server_time.getTime() + tz * 60000);
				 var local_time_str = local_time.toLocaleString('en-US', {day: '2-digit', month: 'long', year: 'numeric', hour:'2-digit', minute: '2-digit'})
				 document.write(local_time_str);
				}
				catch(ex){
					document.write(server_time_str);
				}
			}
		 </script>
        <?php if(self::$message != null) Helper::showAdminNotice(self::$message['code'], self::$message['type']); ?>
        <!-- <?php print_r(self::$requests) ?> -->
        <div class="row" style="padding: 0 12px;">
            <div class="container-table100">
                <div class="wrap-table100">
                    <div class="table100">
                        <table>
                            <thead>
                            <tr class="table100-head">
                                <th><?=_e('Created', SO_CB_SLUG) ?></th>
								<th><?=_e('Requester', SO_CB_SLUG) ?></th>
                                <th><?=_e('Due In Days',SO_CB_SLUG)?></th>
                                <th><?=_e('Due Date', SO_CB_SLUG) ?></th>
                                <th><?=_e('Status', SO_CB_SLUG) ?></th>
                                <th><?=_e('Type', SO_CB_SLUG) ?></th>                                
                                <th><?=_e('Action', SO_CB_SLUG) ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if(self::$requests) { foreach (self::$requests as  $request) { ?>
                            <tr>
                                <td><script>toClientTime("<?=$request->requestCreatedDate ?>");</script></td>
                                <td><?= $request->consumerEmail ?></td>
                                <td><?= $request->dueInDays ?></td>
                                <td><script>toClientTime("<?=$request->dueDate ?>");</script></td>
                                <td><?= $request->requestStatusName ?></td>
                                <td><?= $request->requestTypeName ?></td>
                                <td>
                                    <a target="_blank" href="<?= esc_url(str_replace('{requestNumber}',$request->requestNumber, self::$company->requestLink)) ?>">
                                        <?= _e('View', SO_CB_SLUG) ?>
                                    </a>
                                </td>
                            </tr>
                            <?php } } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
?>