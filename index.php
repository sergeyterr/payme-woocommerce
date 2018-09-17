<?php
/*
Plugin Name: WooCommerce PayMe Payment Gateway 
Plugin URI: https://www.paymeservice.com
Description: Accept payments on your Woocommerce store
Version: 1.1.0
Author: PayMe
Author URI: https://www.paymeservice.com

License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('plugins_loaded', 'woocommerce_payme_init', 0);

define( 'payme_URL', plugin_dir_url( __FILE__ ) );
define( 'payme_DIR', plugin_dir_path( __FILE__ ) );

if (isset($_GET['page']) && $_GET['page'] == 'payme' && isset($_GET['ajax'])) {
	if (isset($_GET['action'])) {
		if($_GET['action'] == 'getCardInfo') {
			
			$cardNumber = trim(strip_tags($_GET['cardNumber']));
			$cardNumber = str_replace(' ','',$cardNumber);
			$result = array();
			$result['type'] = '';
			$result['social'] = 0;
			
			$url = "https://lookup.binlist.net/{$cardNumber}";
			try {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					"Accept-Version: 3"
				));
				$response = curl_exec($ch);
				if($response) {
					$response =  json_decode($response,true);
					if (isset($response['scheme'])) {
						$result['type'] = $response['scheme'];
					}
					if (isset($response['country']['name'])) {
						if ($response['country']['name'] == 'Israel') {
							$result['social'] = 1;
						}
					}
				}
			} catch(Exception $e) {
			}
			echo json_encode($result);
			exit;
		}
	}
}
function woocommerce_payme_init() 
{
	if ( !class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	function paymeEnqueueScripts() 
	{
		wp_enqueue_style('payme-css-admin', payme_URL.'assets/css/admin.css');
		wp_enqueue_script('payme-js-admin', payme_URL.'assets/js/admin.js', array('jquery'));
	}
	
	add_action( 'admin_enqueue_scripts', 'paymeEnqueueScripts' );
	
	class WC_PayMe extends WC_Payment_Gateway
	{
		public function __construct()
		{
			$this -> id           = 'payme';
			$this -> method_title = __('PayMe', 'payme');
			$this -> icon         =  plugins_url( 'assets/images/logo.gif' , __FILE__ );
			$this -> has_fields   = true;
			
			$this -> init_form_fields();
			$this -> init_settings();
			
			$this ->formstyleDefault = '{background-color:white; color:#4b4b4f; font-size:13px; border-radius:5px; border-color:#4b4b4f;padding-left:5px;padding-right:5px}';
			$this ->labelstyleDefault = '{color:#4b4b4f; font-size:13px;}';
			$this ->inputstyleDefault = '{background-color:white; color:#4b4b4f; font-size:13px; border-radius:5px; border-color:#4b4b4f; border:1px solid;box-shadow:none;background-image:none}';
			
			
			$this->debug = '';
			if (isset($this -> settings['debug'])) {
				$this -> debug = $this -> settings['debug'];
			}
			if (isset($this -> settings['debugkey'])) {
				$this -> debugkey = $this -> settings['debugkey'];
			}
			if (isset($this -> settings['productionkey'])) {
				$this -> productionkey = $this -> settings['productionkey'];
			}
			if (isset($this -> settings['callback'])) {
				$this -> callback = $this -> settings['callback'];
			}
			if (isset($this -> settings['log'])) {
				$this -> log = $this -> settings['log'];
			}
			if (isset($this -> settings['title'])) {
				$this -> title  = $this -> settings['title'];
			}
			if (isset($this -> settings['description'])) {
				$this -> description = $this -> settings['description'];
			}
			if (isset($this -> settings['lang'])) {
				$this -> lang = $this -> settings['lang'];
			}
			if (isset($this -> settings['currency'])) {
				$this -> currency  = $this -> settings['currency'];
			}
			if (isset($this -> settings['layout'])) {
				$this -> layout   = $this -> settings['layout'];
			}
			if (isset($this -> settings['fullscreen'])) {
				$this -> fullscreen  = $this -> settings['fullscreen'];
			}
			if (isset($this -> settings['installments'])) {
				$this ->installments = $this -> settings['installments'];
			}
			if (isset($this -> settings['installments_setting'])) {
				$this ->installments_setting = $this -> settings['installments_setting'];
			}
			if (isset($this -> settings['installments_preset'])) {
				$this ->installments_preset = $this -> settings['installments_preset'];
			}
			if (isset($this -> settings['formstyle'])) {
				$this ->formstyle = $this -> settings['formstyle'];
			}
			if (isset($this -> settings['labelstyle'])) {
				$this ->labelstyle = $this -> settings['labelstyle'];
			}
			if (isset($this -> settings['inputstyle'])) {
				$this ->inputstyle = $this -> settings['inputstyle'];
			}
			
			$this->supports = array( 'default_credit_card_form' );
			
			$this -> iframemode       = 'yes';
			$this -> hidelogo = 'yes';

			
			if($this -> hidelogo=='yes')
			{
				$this -> icon = '';	
			}
	
			if($this->debug){
				 $this->url_generate_sale = "https://preprod.paymeservice.com/api/generate-sale";
				 $this->url_pay_sale = "https://preprod.paymeservice.com/api/pay-sale";
			}else{
				$this->url_generate_sale = "https://ng.paymeservice.com/api/generate-sale";
				$this->url_pay_sale = "https://ng.paymeservice.com/api/pay-sale";
			}
		
			$this->notify_url = home_url( '/wc-api/WC_PayMe' );
	
			$this -> msg['message'] = "";
			$this -> msg['class']   = "";
			
			add_action( 'woocommerce_api_wc_payme', array( $this, 'check_payme_response' ) );
			add_action('valid-payme-request', array($this, 'successful_request'));
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}
			add_action('woocommerce_receipt_payme', array($this, 'receipt_page'));
			add_action('woocommerce_thankyou_payme',array($this, 'thankyou_page'));
			add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );

		}
		
		/**
		 * Sanitize our settings
		 */
		public function sanitize_settings( $settings ) 
		{
			$value = 1;
			if ( isset( $settings ) &&
				 isset( $settings['installments_setting'] ) &&
				 ($settings['installments_setting'] == '2') ) {
				$params = $_POST;
				$values = array();
				foreach (range(1, 4) as $index) {
					$values[] = array(
						sanitize_text_field($params["from_".$index]),
						sanitize_text_field($params["to_".$index])
					);
				}
				
				if ($values && count($values) > 0) {
					$value = json_encode($values);
				}
			}
			
			if ( isset( $settings ) &&
				 isset( $settings['installments'] ) ) {
				$settings['installments'] = $value;
			}
			
			if ( isset( $settings )) {
				$params = $_POST;
				if($params['woocommerce_payme_fullscreen'] == 'custom') {
					$settings['formstyle'] = $params['form_style'];
					$settings['labelstyle'] = $params['label_style'];
					$settings['inputstyle'] = $params['input_style'];
				} else {
					$settings['formstyle'] = '';
					$settings['labelstyle'] = '';
					$settings['inputstyle'] = '';
				}
			}
			return $settings;
		}
	
		function init_form_fields()
		{
			include_once(payme_DIR.'includes/options.php');
			
			$this -> form_fields = array(
			
				'general' => array(
					'title' => __('General','payme'),
					'type' => 'hidden',
				),
				
				'enabled' => array(
					'title' => __('Enabled', 'payme'),
					'type' => 'select',
					'default' => 'no',
					'options' => PayMeOptions::getYesNo()
				),
				
				'debug' => array(
					'title' => __('Mode', 'payme'),
					'type' => 'select',
					'default' => true,
					'options' => PayMeOptions::getDebug()
				),
					
				'debugkey' => array(
					'title' => __('Staging API Key', 'payme'),
					'type' => 'text',
					'default' => 'MPLDEMO-MPLDEMO-MPLDEMO-MPLDEMO'
				),
					
				'productionkey' => array(
					'title' => __('Production API Key', 'payme'),
					'type' => 'text',
					'default' => 'MPLDEMO-MPLDEMO-MPLDEMO-MPLDEMO'
				),
				
				'callback' => array(
					'title' => __('Callback', 'payme'),
					'type' => 'text',
				),
				
				'log' => array(
					'title' => __('Save LOG', 'payme'),
					'type' => 'select',
					'default' => 'no',
					'options' => PayMeOptions::getYesNo()
				),
				
				'payment' => array(
					'title' => __('Payment Process','payme'),
					'type' => 'hidden',
				),
				 
				'title' => array(
					'title' => __('Payment Option Title', 'payme'),
					'type'=> 'text',
					'default' => __('Credit Card', 'payme')
				),
				
				'description' => array(
					'title' => __('Description', 'payme'),
					'type' => 'textarea',
					'default' => __('Pay securely by PayMe Payment Gateway.', 'payme')
				),
				
				'lang' => array(
					'title' => __('Lang', 'payme'),
					'type' => 'select',
					'options' => PayMeOptions::getLang()
				),
				
				'currency' => array(
					'title' => __('Accepted Currency', 'payme'),
					'type' => 'select',
					'options' => PayMeOptions::getCurrency()
				),
				
				'layout' => array(
					'title' => __('Payment Page Layout', 'payme'),
					'type' => 'text',
				),
				
				'fullscreen' => array(
					'title' => __('Payment Page Type', 'payme'),
					'type' => 'select',
					'options' => PayMeOptions::getFullscreen(),
					'default' => false
				),
				
				'form_design' => array(
					'title' => __('Form Design', 'payme'),
					'type' => 'customform',
				),
				
				'formstyle' => array(
					'type' => 'hidden',
				),
				
				'labelstyle' => array(
					'type' => 'hidden',
				),
				
				'inputstyle' => array(
					'type' => 'hidden',
				),
				
				'installments' => array(
					'title' => __('Installments','payme'),
					'type' => 'hidden',
					'default' => '1',
				),

				'installments_setting' => array(
					'title' => __('Settings', 'payme'),
					'type' => 'select',
					'options' => PayMeOptions::getSettings()
				),
				
				'installments_preset' => array(
					'title' => __('Preset', 'payme'),
					'type' => 'preset',
					'options' => PayMeOptions::getPreset()
				),
				
				'maxPayments' => array(
					'title' => __('According to Order Total', 'payme'),
					'type' => 'button'
				),
			);				
		}

		public function generate_customform_html( $key, $data )
		{
			$formstyle = $this->formstyleDefault;
			$labelstyle = $this->labelstyleDefault;
			$inputstyle = $this->inputstyleDefault;
			
			$show = false;
			if ($this->fullscreen == 'custom') {
				$show = true;
				if (!empty($this->formstyle)) {
					$formstyle = $this->formstyle;
				}
				if (!empty($this->labelstyle)) {
					$labelstyle = $this->labelstyle;
				}
				if (!empty($this->inputstyle)) {
					$inputstyle = $this->inputstyle;
				}
			}
			ob_start();
			?>
			<tr valign="top" style="display:<?php if($show){echo 'contents';}else{echo 'none';}?>" id="customform-container">
				<td class="forminp" colspan="2">
					<fieldset style="border: 2px solid #666; padding: 0 10px; border-radius: 10px; width: 600px;">
						<legend class="screen-reader-text2" style="font-size: 15px; font-weight: bold;padding-right:3px;"><span>+<?php echo __('Form Design', 'payme')?> </span></legend>
                        <table class="border" cellpadding="0" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                <td width="25%"><?php echo __('Form:', 'payme')?></td>
                                <td><textarea rows="3" name="form_style" class="input-text wide-input "><?php echo $formstyle ?></textarea></td>
                                </tr>
                                <tr>
                                <td><?php echo __('Label:', 'payme')?></td>
                                <td><textarea rows="3" name="label_style" class="input-text wide-input "><?php echo $labelstyle ?></textarea></td>
                                </tr>
                                <tr>
                                <td><?php echo __('Input:', 'payme')?></td>
                                <td><textarea rows="3" name="input_style" class="input-text wide-input "><?php echo $inputstyle ?></textarea></td>
                                </tr>
                            </tbody>
                        </table>
					</fieldset>
				</td>
			</tr>
			<?php
			return ob_get_clean();
		}
		
		public function generate_preset_html( $key, $data )
		{
			$values = $this->installments_preset;
			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'class'             => 'button-secondary',
				'css'               => '',
				'custom_attributes' => array(),
				'desc_tip'          => false,
				'description'       => '',
				'title'             => '',
			);
		
			$data = wp_parse_args( $data, $defaults );
		
			ob_start();
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
					<?php echo $this->get_tooltip_html( $data ); ?>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
                        <select class="select " name="woocommerce_payme_installments_preset" id="woocommerce_payme_installments_preset" style="height: 35px;">
                            <optgroup label="Buyer's Choice">
                                <option value="103" <?php if($values == 103) { echo 'selected="selected"'; }?>>Up to 3</option>
                                <option value="106" <?php if($values == 106) { echo 'selected="selected"'; }?>>Up to 6</option>
                                <option value="109" <?php if($values == 109) { echo 'selected="selected"'; }?>>Up to 9</option>
                                <option value="1012" <?php if($values == 1012) { echo 'selected="selected"'; }?>>Up to 12</option>
                            </optgroup>
                            <optgroup label="Fixed - Unchangeble to buyer">
                                <option value="1" <?php if($values == 1) { echo 'selected="selected"'; }?>>1</option>
                                <option value="2" <?php if($values == 2) { echo 'selected="selected"'; }?>>2</option>
                                <option value="3" <?php if($values == 3) { echo 'selected="selected"'; }?>>3</option>
                                <option value="4" <?php if($values == 4) { echo 'selected="selected"'; }?>>4</option>
                                <option value="5" <?php if($values == 5) { echo 'selected="selected"'; }?>>5</option>
                                <option value="6" <?php if($values == 6) { echo 'selected="selected"'; }?>>6</option>
                                <option value="7" <?php if($values == 7) { echo 'selected="selected"'; }?>>7</option>
                                <option value="8" <?php if($values == 8) { echo 'selected="selected"'; }?>>8</option>
                                <option value="9" <?php if($values == 9) { echo 'selected="selected"'; }?>>9</option>
                                <option value="10" <?php if($values == 10) { echo 'selected="selected"'; }?>>10</option>
                                <option value="11" <?php if($values == 11) { echo 'selected="selected"'; }?>>11</option>
                                <option value="12" <?php if($values == 12) { echo 'selected="selected"'; }?>>12</option>
                           </optgroup>
						</select>
					</fieldset>
				</td>
			</tr>
			<?php
			return ob_get_clean();
		}
		
		public function generate_button_html( $key, $data )
		{
			$values = $this->installments;
			if ($values) {
				$values = json_decode($values,true);
			}
			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'class'             => 'button-secondary',
				'css'               => '',
				'custom_attributes' => array(),
				'desc_tip'          => false,
				'description'       => '',
				'title'             => '',
			);
		
			$data = wp_parse_args( $data, $defaults );
		
			ob_start();
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
					<?php echo $this->get_tooltip_html( $data ); ?>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
                        <table class="border" cellpadding="0" cellspacing="0" id="tableinst">
                            <tbody>
                                <tr class="headings" id="headings_595f428b422f7">
                                    <th><?php echo __('From Total', 'payme')?></th>
                                    <th><?php echo __('Up To Total', 'payme')?></th>
                                    <th><?php echo __('Installments', 'payme')?></th>
                                    <th></th>
                                </tr>
                                <?php foreach (range(1, 4) as $value) { ?>
                                    <tr id="maxP">
                                        <td>
                                            <input name="from_<?php echo $value ?>" id="sum_<?php echo $value ?>" value="<?php echo $values[$value - 1][0] ?>"  class="input-text sum" type="text">
                                        </td>
                                        <td>
                                            <input name="to_<?php echo $value ?>" id="sum_<?php echo $value ?>" value="<?php echo $values[$value - 1][1] ?>" class="input-text sum" type="text">
                                        </td>
                                        <td>
                                            <input name="" value="<?php echo $value * 3 ?>" class="input-text" type="text" readonly="readonly">
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
					</fieldset>
				</td>
			</tr>
			<?php
			return ob_get_clean();
		}

		public function admin_options()
		{
			echo '<h3>'.__('PayMe Payment Gateway', 'payme').'</h3>';
			echo '<table class="form-table">';
			$this -> generate_settings_html();
			echo '</table>';
		}
		
		/**
		 *  There are no payment fields for PayMe, but we want to show the description if set.
		 **/
		function payment_fields()
		{
			if($this -> description) {
				echo wpautop(wptexturize($this -> description));
			}
			if ($this->fullscreen == 'custom') {
				$this->payment_form();
			}
		}
		
		public function payment_form()
		{
			$formstyle = str_replace(array('{','}'),array('',''),$this->formstyle);
			$labelstyle = str_replace(array('{','}'),array('',''),$this->labelstyle);
			$inputstyle = str_replace(array('{','}'),array('',''),$this->inputstyle);

			?>
            <link rel="stylesheet" href="<?php echo payme_URL?>assets/css/front.css"  type='text/css' media='all' />
            <script type="text/javascript" src="<?php echo payme_URL?>assets/js/front.js?2010319"></script>
			<fieldset id="wc-payme-cc-form" class="wc-credit-card-form wc-payment-form" style="<?php echo $formstyle ?>">
            	<input id="card-type" name="card-type" type="hidden" value="" />
                <input id="social-id-required" name="social-id-required" type="hidden" value="0" />
                <p class="form-row form-row-wide woocommerce-validated">
                                <label for="payme-card-number" style="<?php echo $labelstyle ?>"><?php echo __( 'Card Number', 'payme' ) ?> <span class="required">*</span></label>
                                <input id="payme-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="•••• •••• •••• ••••" name="payme-card-number" maxlength="19" style="<?php echo $inputstyle ?>"  onkeyup="$cc.validate(event)"  onblur="checkCreditCard(this.value)">
                </p>
                <p class="form-row form-row-first woocommerce-validated">
                                <label for="payme-card-expiry" style="<?php echo $labelstyle ?>"><?php echo __( 'Expiry', 'payme' ) ?> <span class="required">*</span></label>
                                <input id="payme-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" maxlength="7" spellcheck="no" type="tel" placeholder="MM / YY" name="payme-card-expiry" style="<?php echo $inputstyle ?>" onkeyup="$cc.expiry.call(this,event)">
                </p>
                <p class="form-row form-row-last woocommerce-validated">
                                <label for="payme-card-cvc" style="<?php echo $labelstyle ?>"><?php echo __( 'CVV', 'payme' ) ?> <span class="required">*</span></label>
                                <input id="payme-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="CVC" name="payme-card-cvc" style="<?php echo $inputstyle ?>" onkeyup="$cc.cvc.call(this,event)">
                </p>
                <p class="form-row form-row-wide woocommerce-validated" style="display:none">
                                <label for="payme-social-id" style="<?php echo $labelstyle ?>"><?php echo __( 'Social ID', 'payme' ) ?> <span class="required">*</span></label>
                                <input id="payme-social-id" class="input-text wc-credit-card-form-social-id" inputmode="numeric" autocomplete="no" autocorrect="no" autocapitalize="no" spellcheck="no" name="payme-social-id" style="<?php echo $inputstyle ?>"  onkeyup="$cc.social.call(this,event)">
                </p>
                <?php
				global $woocommerce;
				include_once(payme_DIR.'includes/helper.php');
				$installments = PayMeHelper::maxPaymentsNumber($woocommerce->cart->total,$this);
				if($installments == 1) {
				?>
                	<input id="payme-installments" name="payme-installments" type="hidden" value="1" />
                <?php
				} else {
					if ($installments > 1 && $installments < 100) {
						$options[] = $installments;
					} else {
						for ($i=100,$j=1;$i<$installments;$i++,$j++) {
							$options[] = $j;
						}
					}
				?>
                <p class="form-row form-row-wide woocommerce-validated">
                                <label for="payme-installments" style="<?php echo $labelstyle ?>"><?php echo __( 'Installments', 'payme' ) ?> <span class="required">*</span></label>
                                <select id="payme-installments" class="wc-credit-card-form-installments" name="payme-installments" style="width:100%;padding:8px 0;<?php echo $inputstyle ?>">
                                <?php foreach ($options as $option) {?>
                                	<option value="<?php echo $option?>"><?php echo $option?></option>
                                <?php }?>
                                </select>
                </p>
                <?php
				}
				?>
                <div class="clear"></div>
            </fieldset>
            <div id="payme-accepted-card-icons" style="display:none;">
                <div class="payme-accepted-payment-methods" style="display:inline-block;position:relative; top:6px">
                    <span class="payme-enclosed-method-icon amex">
                        <img src="<?php echo payme_URL?>assets/images/cards/png/amex.png">
                    </span>
                    <span class="payme-enclosed-method-icon discover">
                        <img src="<?php echo payme_URL?>assets/images/cards/png/discover.png">
                    </span>
                    <span class="payme-enclosed-method-icon master_card">
                        <img src="<?php echo payme_URL?>assets/images/cards/png/mastercard.png">
                    </span>
                    <span class="payme-enclosed-method-icon visa">
                        <img src="<?php echo payme_URL?>assets/images/cards/png/visa.png">
                    </span>
                </div>
            </div>
            <script>
			var payme_plugin_url = '<?php echo payme_URL?>';
			
			function checkCreditCard(card_number) {
				jQuery.ajax({
					type: 'GET',
					url: '<?php echo site_url()?>?page=payme&ajax=true&action=getCardInfo&cardNumber='+card_number,
					dataType: "json",
					success: function (jsonData) {
						jQuery('#card-type').val(jsonData.type);
						jQuery('#social-id-required').val(jsonData.social);

						if (jsonData.social == 1) {
							jQuery('#payme-social-id').parent().show();
						} else {
							jQuery('#payme-social-id').parent().hide();
						}
					}
				});
			}
			
			jQuery(document).ready(function(e) {
				var card_icons = jQuery('#payme-accepted-card-icons').html();
				jQuery('.wc_payment_method.payment_method_payme > label').append(card_icons);
            });
			</script>
            <?php 
		}
		
		/**
		 * Receipt Page
		 **/
		function receipt_page($order)
		{
			if($this->fullscreen != 'custom') {
				echo $this -> generate_payme_form($order);
			}
		}
		
		 /**
		 * Thankyou Page
		 **/
		function thankyou_page($order)
		{
		  if (!empty($this->instructions))
			echo wpautop( wptexturize( $this->instructions ) );
		
		}
		
		/**
		 * Generate PayMe Iframe
		 **/
		function generate_payme_form($order_id)
		{
			global $woocommerce;
			
			include_once(payme_DIR.'includes/helper.php');
			include_once(payme_DIR.'includes/api.php');
			
			$order = new WC_Order($order_id);
			$order_id = $order_id.'_'.date("ymds");
			
			$post_data = get_post_meta($order_id,'_post_data',true);
			//update_post_meta($order_id,'_post_data',array());
			
			$result = PayMeApi::createSale($order,$post_data,$this);
	
			$the_display_msg = '';
			$form = '';
			if ($result["status_code"] == 0) {
				$url = PayMeHelper::getIFrameUrl($order, $result["sale_url"],$this);
				if($this->fullscreen == 'regular') {
					$form .= @$the_display_msg.'<iframe src="'.PayMeHelper::getIFrameUrl($order, $result["sale_url"],$this).'" id="paymentFrame" name="paymentFrame"  height="2000" width="600" frameborder="0" scrolling="No" ></iframe>
				
					<script type="text/javascript">
						jQuery(document).ready(function(){
							 window.addEventListener(\'message\', function(e) {
								 jQuery("#paymentFrame").css("height",e.data[\'newHeight\']+\'px\'); 	 
							 }, false);
							
						});
					</script>';
				} else if($this->fullscreen == 'overlay') {
					$form .= '<style>
					#iframecontainer {width:600px; height: 500px; display:none; position: absolute; top: 5%; background:#FFF; border: 1px solid #666;border: 1px solid #555;box-shadow: 2px 2px 40px #222; z-index: 999999; marin:0 auto}
					#iframecontainer iframe {display:none; width: 100%; height: 100%; position: absolute; border: none; }
					#payme-loader img {position:absolute;}
					#payme-block {background: #000; opacity:0.6;  position: fixed; width: 100%; height: 100%; top:0; left:0; display:none; z-index: 999998;}
					#payme-close {padding-right: 16px;       padding-top: 4px;}
					#payme-close a{font-size: 20px; color: black; font-weight: bold;}
					</style>';
					$form .= '<div id="payme-block"></div>
					<div id="iframecontainer">
						<div id="payme-close" style="text-align: right;">
							<a href="'.$this->notify_url.'?action=cancel&order='.(int)($order->get_id()).'">X</a>
						</div>
						<div id="payme-loader"><img src="'.payme_URL.'assets/images/ajax-loader.gif"/></div>
						<iframe id="paymentFrame" name="paymentFrame" height="2000" width="600" frameborder="0" scrolling="No"></iframe>
					</div>';
					$form .= "<script type='text/javascript'>
					jQuery(document).ready(function() {
						jQuery('#payme-block').fadeIn();
						jQuery('#iframecontainer').fadeIn();
						resizeIframeContainer();
						jQuery('#iframecontainer iframe').attr('src', '".$url."');
						jQuery('#iframecontainer iframe').load(function() {
							jQuery('#payme-loader').fadeOut(function() {
								jQuery('#iframecontainer iframe').fadeIn();
								resizeIframeContainer();
							});
						});
						
						 jQuery(window).resize(function() {
							 resizeIframeContainer();
						 });
						 
						 function resizeIframeContainer() {
							 jQuery('#iframecontainer').css({
								 left: (jQuery(window).width() - jQuery('#iframecontainer').outerWidth())/2,
        						 top: (jQuery(window).height() - jQuery('#iframecontainer').outerHeight())/2
							 });
							 
							 jQuery('#payme-loader img').css({
								 left: (jQuery('#iframecontainer').width() - jQuery('#payme-loader img').outerWidth())/2,
        						 top: (jQuery('#iframecontainer').height() - jQuery('#payme-loader img').outerHeight())/2
							 });
						 }
					});
					</script>";
				}
			} else {
				 wc_add_notice( $result["status_error_details"], 'error' );
			}
			return $form;
		}
		
		private function get_post( $name )
		{
			if ( isset( $_POST[ $name ] ) ) {
				return trim($_POST[ $name ]);
			}
			return null;
		}
		
		/**
		 * Process the payment and return the result
		 **/
		function process_payment($order_id)
		{
			global $woocommerce;
			
			$order = new WC_Order($order_id);
			if($this->fullscreen == 'custom') {
				$card_number = $this->get_post( 'payme-card-number' );
				$card_expiry = $this->get_post( 'payme-card-expiry' );
				$card_cvc = $this->get_post( 'payme-card-cvc' );
				$card_social_id = $this->get_post( 'payme-social-id' );
				$card_installments = $this->get_post( 'payme-installments' );
				$social_id_required = $this->get_post( 'social-id-required' );
				
				if (empty($card_number)) {
					wc_add_notice( __( 'Enter Card Number', 'payme' ), 'error' );
				} else if (empty($card_expiry)) {
					wc_add_notice( __( 'Enter Expiry Date', 'payme' ), 'error' );
				} else if (empty($card_cvc)) {
					wc_add_notice( __( 'Enter CVV Code', 'payme' ), 'error' );
				} else if ($social_id_required == 1 && empty($card_social_id)) {
					wc_add_notice( __( 'Enter Your Social ID', 'payme' ), 'error' );
				} else {
					include_once(payme_DIR.'includes/creditcard.php');
					$expiryDate  = explode('/',$card_expiry);
					$card = PayMeCreditCard::validCreditCard($card_number);
					if (!$card || !isset($card['valid']) || $card['valid'] != 1) {
						wc_add_notice( __( 'Invalid Card Number', 'payme' ), 'error' );
					} else if (!PayMeCreditCard::validDate('20'.trim($expiryDate[1]), trim($expiryDate[0]))) {
						wc_add_notice( __( 'Invalid Expiry Date', 'payme' ), 'error' );
					} else if (!PayMeCreditCard::validCvc($card_cvc, $card['type'])) {
						wc_add_notice( __( 'Invalid CVV Code', 'payme' ), 'error' );
					} else if (!PayMeCreditCard::validSocialId($card_social_id)) {
						wc_add_notice( __( 'Invalid Social Id', 'payme' ), 'error' );
					} else {
						include_once(payme_DIR.'includes/helper.php');
						include_once(payme_DIR.'includes/api.php');
						
						$result = PayMeApi::createCustomSale($order,$this,$card_installments);
						if ($result["status_code"] == 0) {
							$transauthorised = false;
							$this->msg['class']   = 'error';
							$this->msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";

							$result = PayMeApi::paySale($order,$this,$result["payme_sale_id"],$card_number,$card_expiry,$card_cvc,$card_social_id);
							if (isset($result['payme_status'])) {
								$payme_status = sanitize_text_field($result['payme_status']);
								if ($payme_status == "success") {
									$transauthorised = true;
									$this->msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
									$this->msg['class'] = 'success';
									
									if($order -> get_status() != 'processing') {
										$order -> payment_complete();
										//update_post_meta($order_id, 'PayMe Order Response', json_encode($result));
										$order -> add_order_note('PayMe payment successful.');
										$order -> add_order_note('PayMe Transaction Id: '.$result['payme_transaction_id'].'<br/>Bank card: '.$result['buyer_card_mask'].'<br/>Installments: '.$result['installments']);
										$woocommerce -> cart -> empty_cart();
										return array (
										  'result'   => 'success',
										  'redirect' => $this->get_return_url( $order ),
										);
									}
								}
							}
							
							if ($transauthorised == false) {
								$order -> update_status('failed');
								$order -> add_order_note('Failed');
								$order -> add_order_note($this->msg['message']);
								wc_add_notice($this->msg['message'].(isset($result["status_error_details"]) ? $result["status_error_details"]:''), 'error' );
							}
						} else {
							 wc_add_notice( $result["status_error_details"], 'error' );
						} 
					}
				}
			} else {
				//update_post_meta($order_id,'_post_data',$_POST);
				return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url( true ));
			}
		}
		/**
		 * Check for valid PayMe server callback
		 **/
		function check_payme_response()
		{
			global $woocommerce;
			
			$transauthorised = false;
			$this->msg['class']   = 'error';
			$this->msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
			$data = $_REQUEST;
			
			include_once(payme_DIR.'includes/helper.php');
			PayMeHelper::log($data,'Response',$this);
			
			if (isset($data['order'])) {
				$order_id = (int)(sanitize_text_field($data['order']));
				if($order_id != '') {
					$order = new WC_Order($order_id);
					$action = sanitize_text_field($data['action']);
					if ($action == 'callback') {
						if ($order && isset($data['buyer_card_mask'])) {
							$buyer_card_mask = sanitize_text_field($data['buyer_card_mask']);
							$payme_transaction_id = sanitize_text_field($data['payme_transaction_id']);
							$installments = sanitize_text_field($data['installments']);
							if ($buyer_card_mask != "") {
								if (isset($data['payme_status'])) {
									$payme_status = sanitize_text_field($data['payme_status']);
									if ($payme_status == "success") {
										$transauthorised = true;
										$this->msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
										$this->msg['class'] = 'success';
										
										if($order -> get_status() == 'processing') {
											$order -> add_order_note('PayMe Transaction Id: '.$payme_transaction_id.'<br/>Bank card: '.$buyer_card_mask.'<br/>Installments: '.$installments);
										}
									}
								}
							}
						}
						
						if ($transauthorised == false) {
							$order -> update_status('failed');
							$order -> add_order_note('Failed');
							$order -> add_order_note($this->msg['message']);
						}
						exit;
					} else if($action == 'return') {
						if ($order && isset($data['payme_transaction_id'])) {
							$payme_transaction_id = sanitize_text_field($data['payme_transaction_id']);
							if ($payme_transaction_id != "") {
								if (isset($data['payme_status'])) {
									$payme_status = sanitize_text_field($data['payme_status']);
									if ($payme_status == "success") {
										$transauthorised = true;
										$this->msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
										$this->msg['class'] = 'success';
										
										if($order -> get_status() != 'processing') {
											$order -> payment_complete();
											//update_post_meta($order_id, 'PayMe Order Response', json_encode($data));
											$order -> add_order_note('PayMe payment successful.');
											$woocommerce -> cart -> empty_cart();
										}
										add_action('the_content', array(&$this, 'showMessage'));
									}
								}
							}
						}
						
						if ($transauthorised == false) {
							$order -> update_status('failed');
							$order -> add_order_note('Failed');
							$order -> add_order_note($this->msg['message']);
						}
						add_action('the_content', array(&$this, 'showMessage'));
						$this->redirectToSuccess($this->get_return_url( $order ));
					} else if($action == 'cancel') {
						if ($order) {
							$this->msg['message'] = "Payment has been cancelled by customer.";
							$this->msg['class'] = 'error';
							$order->update_status('failed');
							$order -> add_order_note($this->msg['message']);
							add_action('the_content', array(&$this, 'showMessage'));
							//$woocommerce->add_error($this->msg['message']);
							//$woocommerce->set_messages();
							$this->redirectToSuccess($this->get_return_url( $order ));
						}
					} else {
						echo 'Illegal Access';
						exit;
					}
				}
			}
		}
		
		function redirectToSuccess($url)
		{
			echo '<script>window.top.location.href = "'.$url.'";</script>';
			exit;
		}
		
		function showMessage($content)
		{
			return '<div class="box '.$this->msg['class'].'-box">'.$this->msg['message'].'</div>'.$content;
		}
		
	}
	

    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_payme_gateway($methods) {
        $methods[] = 'WC_PayMe';
		
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_payme_gateway' );
}
