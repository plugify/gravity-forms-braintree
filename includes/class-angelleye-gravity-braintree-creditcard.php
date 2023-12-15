<?php
if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Check Angelleye_Gravity_Braintree_CreditCard_Field class exists or not.
 */
if ( ! class_exists( 'Angelleye_Gravity_Braintree_CreditCard_Field' ) ) {

	/**
	 * Class Angelleye_Gravity_Braintree_CreditCard_Field
	 *
	 * This class provides the Braintree CreditCard fields functionality for Gravity Forms.
	 */
	class Angelleye_Gravity_Braintree_CreditCard_Field extends GF_Field {

		/**
		 * @var string $type The field type.
		 */
		public $type = 'braintree_credit_card';

		/**
		 * Return the field title, for use in the form editor.
		 *
		 * @return string|void
		 */
		public function get_form_editor_field_title() {
			return __( 'Braintree Credit Card', 'angelleye-gravity-forms-braintree' );
		}

		/**
		 * Assign the field button to the Pricing Fields group.
		 *
		 * @return array
		 */
		public function get_form_editor_button() {
			return [ 'group' => 'pricing_fields', 'text' => 'Braintree CC' ];
		}

		/**
		 * The settings which should be available on the field in the form editor.
		 *
		 * @return array
		 */
		function get_form_editor_field_settings() {
			return [
				'label_setting',
				'admin_label_setting',
				'description_setting',
				'error_message_setting',
				'css_class_setting',
				'conditional_logic_field_setting',
				'rules_setting',
				'input_placeholders_setting',
				'label_placement_setting'
			];
		}

		/**
		 * Returns the field inner markup.
		 *
		 * @param array  $form  The Form Object currently being processed.
		 * @param string $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
		 * @param null   $entry Null or the Entry Object currently being edited.
		 *
		 * @return false|string
		 * @throws \Braintree\Exception\Configuration
		 */
		public function get_field_input( $form, $value = '', $entry = null ) {

            $html = '';

            try {

                $is_entry_detail = $this->is_entry_detail();
                $is_form_editor  = $this->is_form_editor();

                $form_id  = $form['id'];
                $id       = intval( $this->id );
                $field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
                $form_id  = ( $is_entry_detail || $is_form_editor ) && empty( $form_id ) ? rgget( 'id' ) : $form_id;

                $Plugify_GForm_Braintree = new Plugify_GForm_Braintree();
                $gateway                 = $Plugify_GForm_Braintree->getBraintreeGateway();
                $clientToken             = $gateway->clientToken()->generate();
                ob_start();

                ?>
                <div class='ginput_container gform_payment_method_options ginput_container_<?php echo $this->type; ?>'
                     id='<?php echo $field_id; ?>'>
                    <div id="dropin-container"></div>
                    <input type="hidden" id="nonce" name="payment_method_nonce"/>
                </div>
                <script type="text/javascript">
                    // const form = document.getElementById('gform_<?php echo $form_id; ?>');
                    if(typeof braintree === 'undefined') {
                        // console.log("Braintree is not loaded yet. Loading...");
                        var script = document.createElement('script');
                        script.onload = function () {
                            // console.log("Braintree is now loaded.");
                            braintree.dropin.create({
                                authorization: '<?php echo $clientToken;?>',
                                container: '#dropin-container'
                            }, (error, dropinInstance) => {
                                if (error) console.error(error);

                                document.getElementById('gform_<?php echo $form_id; ?>').addEventListener('submit', event => {
                                    event.preventDefault();

                                    dropinInstance.requestPaymentMethod((error, payload) => {
                                        if (error) console.error(error);
                                        document.getElementById('nonce').value = payload.nonce;
                                        document.getElementById('gform_<?php echo $form_id; ?>').submit();
                                    });
                                });
                            });
                        };
                        script.src = 'https://js.braintreegateway.com/web/dropin/1.26.0/js/dropin.min.js';
                        document.head.appendChild(script);
                    } else {
                        braintree.dropin.create({
                            authorization: '<?php echo $clientToken;?>',
                            container: '#dropin-container'
                        }, (error, dropinInstance) => {
                            if (error) console.error(error);

                            document.getElementById('gform_<?php echo $form_id; ?>').addEventListener('submit', event => {
                                event.preventDefault();

                                dropinInstance.requestPaymentMethod((error, payload) => {
                                    if (error) console.error(error);
                                    document.getElementById('nonce').value = payload.nonce;
                                    document.getElementById('gform_<?php echo $form_id; ?>').submit();
                                });
                            });
                        });
                    }
                </script>
                <?php
                $html = ob_get_contents();
                ob_get_clean();
            } catch (Exception $e) {

                return sprintf( esc_html__("Something went wrong. Please check merchant account details %s.", "angelleye-gravity-forms-braintree"), '<a href="'.esc_url( admin_url( 'admin.php?page=gf_settings&subview=gravity-forms-braintree' ) ).'">'.esc_html__('here', 'angelleye-gravity-forms-braintree').'</a>');
            }

			return $html;
		}
	}
}
