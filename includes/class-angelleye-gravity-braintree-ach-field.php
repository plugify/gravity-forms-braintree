<?php

defined('ABSPATH') or die('Direct access not allowed');

class Angelleye_Gravity_Braintree_ACH_Field extends GF_Field {

    private $selected_payment_method = '';

    /**
     * @var string $type The field type.
     */
    public $type = 'braintree_ach';

    /**
     * Return the field title, for use in the form editor.
     *
     * @return string
     */
    public function get_form_editor_field_title() {
        return __('ACH Form', 'gravity-forms-braintree');
    }

    /**
     * Assign the field button to the Pricing Fields group.
     *
     * @return array
     */
    public function get_form_editor_button() {
        return ['group' => 'pricing_fields', 'text' => 'ACH Form'];
    }

    /**
     * The settings which should be available on the field in the form editor.
     *
     * @return array
     */
    function get_form_editor_field_settings() {
        return ['label_setting', 'label_placement_setting', 'admin_label_setting', 'description_setting', 'sub_labels_setting',
            'sub_label_placement_setting', 'error_message_setting', 'css_class_setting', 'conditional_logic_field_setting',
            'force_ssl_field_setting', 'rules_setting', 'input_placeholders_setting',
        ];
    }

    /**
     * Enable this field for use with conditional logic.
     *
     * @return bool
     */
    public function is_conditional_logic_supported() {
        return true;
    }

    /**
     * The scripts to be included in the form editor.
     *
     * @return string
     */
    public function get_form_editor_inline_script_on_page_render() {
        
    }

    /**
     * Override default button for the ACH Payments
     * @param $button
     * @param $form
     *
     * @return string
     */
    function overrideSubmitButton($button, $form) {
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $button);
        $input = $dom->getElementsByTagName('input')->item(0);
        if( empty( $input ) ){
            $input = $dom->getElementsByTagName('button')->item(0);
        }
        
        //$input->removeAttribute('onkeypress');
        //$input->removeAttribute('onclick');
        $classes = $input->getAttribute('class');
        $classes .= " custom_ach_form_submit_btn";
        $input->setAttribute('class', $classes);
        return $dom->saveHtml($input);
    }

    /**
     * Returns the fields input for backend and frontend
     * @param array $form
     * @param string $value
     * @param null $entry
     *
     * @return string
     */
    public function get_field_input($form, $value = '', $entry = null) {
        add_filter('gform_submit_button', [$this, 'overrideSubmitButton'], 10, 2);
        $is_entry_detail = $this->is_entry_detail();
        $is_form_editor = $this->is_form_editor();

        $form_id = $form['id'];
        $id = intval($this->id);
        $field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
        $form_id = ( $is_entry_detail || $is_form_editor ) && empty($form_id) ? rgget('id') : $form_id;

        $disabled_text = $is_form_editor ? "disabled='disabled'" : '';
        $class_suffix = $is_entry_detail ? '_admin' : '';

        $sub_label_placement = rgar($form, 'subLabelPlacement');
        $field_sub_label_placement = $this->subLabelPlacement;
        $is_sub_label_above = $field_sub_label_placement == 'above' || ( empty($field_sub_label_placement) && $sub_label_placement == 'above' );
        $sub_label_class_attribute = $field_sub_label_placement == 'hidden_label' ? "class='hidden_sub_label screen-reader-text'" : '';

        $account_number = '';
        $routing_number = '';
        $account_type = '';
        $account_holder_name = '';
        $autocomplete = RGFormsModel::is_html5_enabled() ? "autocomplete='off'" : '';

        if (is_array($value)) {
            $account_number = esc_attr(rgget($this->id . '.1', $value));
            $account_type = esc_attr(rgget($this->id . '.2', $value));
            $routing_number = esc_attr(rgget($this->id . '.3', $value));
            $account_holder_name = esc_attr(rgget($this->id . '.4', $value));
        }


        $tabindex = $this->get_tabindex();
        $account_type_field_input = GFFormsModel::get_input($this, $this->id . '.2');
        $account_type_options = $this->getAccountTypeSelectOptions($account_type);

        $account_types = $this->getAccountTypeList();
        //$radio_buttons = [];
        //foreach ($account_types as $ac_key => $ac_label)
        //	$radio_buttons[]= "<label><input type='radio' name='input_{$id}.2' id='{$field_id}_2' class='ginput_account_type' {$tabindex} {$disabled_text} class='ginput_card_expiration' value='{$ac_key}' ".($ac_key==$account_type?'checked':'')."/> {$ac_label}</label>";

        $account_type_label = rgar($account_type_field_input, 'customLabel') != '' ? $account_type_field_input['customLabel'] : __('Account Type', 'gravity-forms-braintree');
        $account_type_label = gf_apply_filters(array('gform_accounttype', $form_id), $account_type_label, $form_id);
        if ($is_sub_label_above) {
            $account_type_field = "<span class='ginput_full{$class_suffix}' id='{$field_id}_2_container'>
                                    <label for='{$field_id}_2' id='{$field_id}_2_label' {$sub_label_class_attribute}>{$account_type_label}</label>
									<select name='input_{$id}.2' id='{$field_id}_2' class='ginput_account_type' {$tabindex} {$disabled_text} class='ginput_card_expiration'>
										{$account_type_options}
									</select>
                                 </span>";
        } else {
            $account_type_field = "<span class='ginput_full{$class_suffix}' id='{$field_id}_2_container'>
                                   <select name='input_{$id}.2' id='{$field_id}_2' class='ginput_account_type' {$tabindex} {$disabled_text} class='ginput_card_expiration'>
										{$account_type_options}
									</select>
                                    <label for='{$field_id}_2' id='{$field_id}_2_label' {$sub_label_class_attribute}>{$account_type_label}</label>
                                 </span>";
        }

        $tabindex = $this->get_tabindex();
        $routing_number_field_input = GFFormsModel::get_input($this, $this->id . '.3');
        $html5_output = !is_admin() && GFFormsModel::is_html5_enabled() ? "pattern='[0-9]*' title='" . __('Only digits are allowed', 'gravity-forms-braintree') . "'" : '';
        $routing_number_label = rgar($routing_number_field_input, 'customLabel') != '' ? $routing_number_field_input['customLabel'] : __('Routing Number', 'gravity-forms-braintree');
        $routing_number_label = gf_apply_filters(array('gform_routingnumber', $form_id), $routing_number_label, $form_id);

        $routing_number_placeholder = $this->get_input_placeholder_attribute($routing_number_field_input);
        if ($is_sub_label_above) {
            $routing_field = "<span class='ginput_full{$class_suffix}' id='{$field_id}_3_container' >
                                    <label for='{$field_id}_2' id='{$field_id}_3_label' {$sub_label_class_attribute}>{$routing_number_label}</label>
                                    <input type='text' name='input_{$id}.3' id='{$field_id}_3' class='ginput_routing_number' value='{$routing_number}' {$tabindex} {$disabled_text} {$autocomplete} {$html5_output} {$routing_number_placeholder}/>
                                 </span>";
        } else {
            $routing_field = "<span class='ginput_full{$class_suffix}' id='{$field_id}_3_container' >
                                    <input type='text' name='input_{$id}.3' id='{$field_id}_3' class='ginput_routing_number' value='{$routing_number}' {$tabindex} {$disabled_text} {$autocomplete} {$html5_output} {$routing_number_placeholder}/>
                                    <label for='{$field_id}_2' id='{$field_id}_3_label' {$sub_label_class_attribute}>{$routing_number_label}</label>
                                 </span>";
        }

        $tabindex = $this->get_tabindex();
        $account_number_field_input = GFFormsModel::get_input($this, $this->id . '.1');
        $html5_output = !is_admin() && GFFormsModel::is_html5_enabled() ? "pattern='[0-9]*' title='" . __('Only digits are allowed', 'gravity-forms-braintree') . "'" : '';
        $account_number_label = rgar($account_number_field_input, 'customLabel') != '' ? $account_number_field_input['customLabel'] : __('Account Number', 'gravity-forms-braintree');
        $account_number_label = gf_apply_filters(array('gform_1', $form_id), $account_number_label, $form_id);

        $account_number_placeholder = $this->get_input_placeholder_attribute($account_number_field_input);
        if ($is_sub_label_above) {
            $account_field = "<span class='ginput_full{$class_suffix}' id='{$field_id}_1_container' >
									<label for='{$field_id}_1' id='{$field_id}_1_label' {$sub_label_class_attribute}>{$account_number_label}</label>
									<input type='text' name='input_{$id}.1' id='{$field_id}_1' class='ginput_account_number' value='{$account_number}' {$tabindex} {$disabled_text} {$autocomplete} {$html5_output} {$account_number_placeholder}/>
								 </span>";
        } else {
            $account_field = "<span class='ginput_full{$class_suffix}' id='{$field_id}_1_container' >
									<input type='text' name='input_{$id}.1' id='{$field_id}_1' class='ginput_account_number' value='{$account_number}' {$tabindex} {$disabled_text} {$autocomplete} {$html5_output} {$account_number_placeholder}/>
									<label for='{$field_id}_1' id='{$field_id}_1_label' {$sub_label_class_attribute}>{$account_number_label}</label>
								 </span>";
        }

        $account_number_verification_field = '';
        if ($is_sub_label_above) {
            $account_number_verification_field = "<span class='ginput_full{$class_suffix}' id='{$field_id}_1_verification_container' >
									<label for='{$field_id}_1_verification' id='{$field_id}_1_verification_label' {$sub_label_class_attribute}>{$account_number_label} Verification</label>
									<input type='text' id='{$field_id}_1_verification' class='ginput_account_number_verification' value='{$account_number}' {$tabindex} {$disabled_text} {$autocomplete} {$html5_output} {$account_number_placeholder}/>
								 </span>";
        } else {
            $account_number_verification_field = "<span class='ginput_full{$class_suffix}' id='{$field_id}_1_verification_container' >
									<input type='text' id='{$field_id}_1' class='ginput_account_number_verification' value='{$account_number}' {$tabindex} {$disabled_text} {$autocomplete} {$html5_output} {$account_number_placeholder}/>
									<label for='{$field_id}_1_verification' id='{$field_id}_1_verification_label' {$sub_label_class_attribute}>{$account_number_label} Verification</label>
								 </span>";
        }

        $tabindex = $this->get_tabindex();
        $account_holder_name_field_input = GFFormsModel::get_input($this, $this->id . '.4');
        $account_holder_name_label = rgar($account_holder_name_field_input, 'customLabel') != '' ? $account_holder_name_field_input['customLabel'] : __('Account Holder Name', 'gravity-forms-braintree');
        $account_holder_name_label = gf_apply_filters(array('gform_accountholdername', $form_id), $account_holder_name_label, $form_id);

        $account_holder_name_placeholder = $this->get_input_placeholder_attribute($account_holder_name_field_input);
        if ($is_sub_label_above) {
            $account_holder_name_field = "<span class='ginput_full{$class_suffix}' id='{$field_id}_4_container'>
                                            <label for='{$field_id}_4' id='{$field_id}_4_label' {$sub_label_class_attribute}>{$account_holder_name_label}</label>
                                            <input type='text' name='input_{$id}.4' id='{$field_id}_4' class='ginput_account_holdername' value='{$account_holder_name}' {$tabindex} {$disabled_text} {$account_holder_name_placeholder}/>
                                        </span>";
        } else {
            $account_holder_name_field = "<span class='ginput_full{$class_suffix}' id='{$field_id}_4_container'>
                                            <input type='text' name='input_{$id}.4' id='{$field_id}_4' class='ginput_account_holdername' value='{$account_holder_name}' {$tabindex} {$disabled_text} {$account_holder_name_placeholder}/>
                                            <label for='{$field_id}_4' id='{$field_id}_4_label' {$sub_label_class_attribute}>{$account_holder_name_label}</label>
                                        </span>";
        }

        return "<div class='ginput_container ginput_ach_form_container ginput_container_{$this->type} ginput_complex{$class_suffix}' id='{$field_id}'>" . $account_type_field . $account_holder_name_field . $routing_field . $account_field . $account_number_verification_field . ' </div>';
    }

    /**
     * Returns Braintree ACH Supported account types
     * @return mixed|void
     */
    public function getAccountTypeList() {
        $account_types = apply_filters('angelleye_gravity_braintree_account_types', [
            'S' => __('Savings', 'gravity-forms-braintree'),
            'C' => __('Checking', 'gravity-forms-braintree'),
        ]);
        return $account_types;
    }

    /**
     * Returns the account type options for select field
     * @param $selected_value
     * @param string $placeholder
     *
     * @return string
     */
    private function getAccountTypeSelectOptions($selected_value, $placeholder = '') {
        if (empty($placeholder)) {
            $placeholder = __('Select', 'gravity-forms-braintree');
        }
        $options = $this->getAccountTypeList();
        $str = "<option value=''>{$placeholder}</option>";
        foreach ($options as $value => $label) {
            $selected = $selected_value == $value ? "selected='selected'" : '';
            $str .= "<option value='{$value}' {$selected}>{$label}</option>";
        }

        return $str;
    }

    public function get_field_label_class() {
        return 'gfield_label gfield_label_before_complex';
    }

    public function is_value_submission_empty($form_id) {
        return false;
    }

    /**
     * When customer chooses ACH form method then avoid credit card field validation errors
     * @param $validation_result
     *
     * @return mixed
     */
    function customCCValidation($validation_result) {
        $form = $validation_result['form'];
        $this->selected_payment_method = getAngelleyeBraintreePaymentMethod($form);

        if ($this->selected_payment_method !== 'braintree_ach')
            return $validation_result;

        $failed_validation = 0;
        if (isset($form['fields'])) {

            foreach ($form['fields'] as $key => $single_field) {
                if ($single_field->type == 'creditcard') {
                    $form['fields'][$key]['failed_validation'] = false;
                    $form['fields'][$key]['validation_message'] = '';
                } else if ($single_field['failed_validation'])
                    $failed_validation++;
            }
        }

        $validation_result['form'] = $form;
        if ($failed_validation == 0) {
            $validation_result['failed_validation_page'] = "1";
            $validation_result['is_valid'] = true;
        }

        return $validation_result;
    }

    /**
     * Constructor to add custom validation filter to exclude CC validation when ACH payment method is selected
     * Angelleye_Gravity_Braintree_ACH_Field constructor.
     *
     * @param array $data
     */
    public function __construct($data = array()) {
        parent::__construct($data);
        add_filter('gform_validation', [$this, 'customCCValidation'], 50);
    }

    /**
     * Validate the user input
     * @param array|string $value
     * @param array $form
     */
    public function validate($value, $form) {

        //check if toggle field exist
        $this->selected_payment_method = getAngelleyeBraintreePaymentMethod($form);

        if ($this->selected_payment_method !== 'braintree_ach') {
            return;
        }

        $account_number = rgpost('input_' . $this->id . '_1');
        $routing_number = rgpost('input_' . $this->id . '_2');
        $account_type = rgpost('input_' . $this->id . '_3');
        $account_holder_name = rgpost('input_' . $this->id . '_4');

        //$this->isRequired &&
        if (( empty($account_number) || empty($routing_number) || empty($account_type) || empty($account_holder_name))) {
            $this->failed_validation = true;
            $this->validation_message = empty($this->errorMessage) ? __('Please enter your bank account information.', 'gravity-forms-braintree') : $this->errorMessage;
        } elseif (!empty($account_number)) {

            if (empty($routing_number)) {
                $this->failed_validation = true;
                $this->validation_message = __("Please enter your bank account's routing number.", 'gravity-forms-braintree');
            }
            if (empty($account_type)) {
                $this->failed_validation = true;
                $this->validation_message = __("Please select the account type.", 'gravity-forms-braintree');
            }

            if (empty($account_holder_name)) {
                $this->failed_validation = true;
                $this->validation_message = __("Please enter account holder name.", 'gravity-forms-braintree');
            }
        }
    }

    /**
     * Shows the filled information by the users on frontend form
     * @param array $field_values
     * @param bool $get_from_post_global_var
     *
     * @return array|string
     */
    public function get_value_submission($field_values, $get_from_post_global_var = true) {

        if ($get_from_post_global_var) {
            $value[$this->id . '.1'] = $this->get_input_value_submission('input_' . $this->id . '_1', rgar(@$this->inputs[0], 'name'), $field_values, true);
            $value[$this->id . '.2'] = $this->get_input_value_submission('input_' . $this->id . '_2', rgar(@$this->inputs[1], 'name'), $field_values, true);
            $value[$this->id . '.3'] = $this->get_input_value_submission('input_' . $this->id . '_3', rgar(@$this->inputs[2], 'name'), $field_values, true);
            $value[$this->id . '.4'] = $this->get_input_value_submission('input_' . $this->id . '_4', rgar(@$this->inputs[3], 'name'), $field_values, true);
        } else {
            $value = $this->get_input_value_submission('input_' . $this->id, $this->inputName, $field_values, $get_from_post_global_var);
        }
        return $value;
    }

    /**
     * Returns the ACH form entry input values
     * @return array|null
     */
    public function get_entry_inputs() {
        $inputs = array();
        if (is_array($this->inputs)) {
            foreach ($this->inputs as $input) {
                if (in_array($input['id'], array(
                            $this->id . '.1',
                            $this->id . '.2',
                            $this->id . '.3',
                            $this->id . '.4'
                        ))) {
                    $inputs[] = $input;
                }
            }
        }

        return $inputs;
    }

    /**
     * Format the entry value for display on the entries list page.
     *
     * Return a value that's safe to display on the page.
     *
     * @param string|array $value    The field value.
     * @param array        $entry    The Entry Object currently being processed.
     * @param string       $field_id The field or input ID currently being processed.
     * @param array        $columns  The properties for the columns being displayed on the entry list page.
     * @param array        $form     The Form Object currently being processed.
     *
     * @return string
     */
    public function get_value_entry_list($value, $entry, $field_id, $columns, $form) {

        $allowable_tags = $this->get_allowable_tags($form['id']);

        list( $input_id, $field_id ) = rgexplode('.', $field_id, 2);
        switch ($field_id) {
            case 2:
                $return = $value == 'C' ? 'Checking' : 'Savings';
                break;
            case 1:
            case 3:
            case 4:
            default:
                if ($allowable_tags === false) {
                    // The value is unsafe so encode the value.
                    $return = esc_html($value);
                } else {
                    // The value contains HTML but the value was sanitized before saving.
                    $return = $value;
                }
        }

        return $return;
    }

    /**
     * Returns the ACH form value on Entry detail page
     * @param array|string $value
     * @param string $currency
     * @param bool $use_text
     * @param string $format
     * @param string $media
     *
     * @return string
     */
    public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen') {

        if (is_array($value)) {
            $account_number = trim(rgget($this->id . '.1', $value));
            $account_type = trim(rgget($this->id . '.2', $value));
            $routing_number = trim(rgget($this->id . '.3', $value));
            $account_holder_name = trim(rgget($this->id . '.4', $value));
            if ($account_number == '')
                return 'N/A';
            if ($format == 'html')
                return "<b>Account Number: </b> $account_number <br/>
<b>Account Type: </b> " . ($account_type == 's' ? 'Savings' : 'Checking') . " <br/>
<b>Routing Number: </b> $routing_number <br/>
<b>Account Holder: </b> $account_holder_name <br/>";
        } else {
            return '';
        }
    }

    /**
     * Mask the values before saving
     * @param string $value
     * @param array $form
     * @param string $input_name
     * @param int $lead_id
     * @param array $lead
     *
     * @return array|string
     */
    public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead) {
        list( $input_token, $field_id_token, $input_id ) = rgexplode('_', $input_name, 3);
        if ($input_id == '1' || $input_id == '3') {
            $value = str_replace(' ', '', $value);
            $card_number_length = strlen($value);
            $value = substr($value, - 4, 4);
            $value = str_pad($value, $card_number_length, 'X', STR_PAD_LEFT);
        }

        return $this->sanitize_entry_value($value, $form['id']);
    }

}
