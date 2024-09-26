<?php

function getAngelleyeBraintreePaymentFields($form){
	$response = [
		'creditcard' => false,
		'braintree_ach' => false,
		'braintree_ach_cc_toggle' => false,
		'braintree_credit_card' => false
	];

	if(isset($form['fields'])) {
		foreach ($form['fields'] as $single_field) {
			if ($single_field->type == 'creditcard' || $single_field->type=='braintree_ach' || $single_field->type == 'braintree_ach_cc_toggle' || $single_field->type=='braintree_credit_card') {
				$response[$single_field->type] = $single_field;
			}
		}
	}

	return $response;
}

function getAngelleyeBraintreePaymentMethod($form){
	$selected_method = '';
    $response = getAngelleyeBraintreePaymentFields($form);

    //This means customer is using our toggle button
    if($response['braintree_ach_cc_toggle'] !== false){
	    $selected_method = rgpost( 'input_' . $response['braintree_ach_cc_toggle']->id . '_1' );
    } else {
        if($response['creditcard']!==false){
            if(isset($response['creditcard']['conditionalLogic']) && is_array($response['creditcard']['conditionalLogic']) && count($response['creditcard']['conditionalLogic'])){
                $conditionalLogic =  $response['creditcard']['conditionalLogic'];
                if($conditionalLogic['actionType'] == 'show'){
                    foreach ( $conditionalLogic['rules'] as $rule ) {
                        if($rule['operator'] == 'is') {
                            $fieldId = $rule['fieldId'];
                            $isValue = $rule['value'];
                            $selected_radio_value = rgpost( 'input_' . $fieldId );

                            if($selected_radio_value == $isValue){
                                $selected_method = 'creditcard';
                                break;
                            }
                        }
                    }
                }

            }
        }

        if($selected_method=='' && $response['braintree_ach'] !== false){
	        if(isset($response['braintree_ach']['conditionalLogic']) && is_array($response['braintree_ach']['conditionalLogic']) && count($response['braintree_ach']['conditionalLogic'])){
                $conditionalLogic =  $response['braintree_ach']['conditionalLogic'];
                if($conditionalLogic['actionType'] == 'show'){
                    foreach ( $conditionalLogic['rules'] as $rule ) {
                        if($rule['operator'] == 'is') {
                            $fieldId = $rule['fieldId'];
                            $isValue = $rule['value'];
                            $selected_radio_value = rgpost( 'input_' . $fieldId );
                            if($selected_radio_value == $isValue){
                                $selected_method = 'braintree_ach';
                                break;
                            }
                        }
                    }
                }
	        }
        }

        if($selected_method == '' && $response['creditcard']!==false){
            $selected_method = 'creditcard';
        } else if($selected_method == '' && $response['braintree_ach']!==false){
	        $selected_method = 'braintree_ach';
        } else if($selected_method == '' && $response['braintree_credit_card']!==false){
	        $selected_method = 'braintree_credit_card';
        }

    }

    return $selected_method;
}


/**
 * This is to setup default values for Custom Toggle and ACH form fields in admin panel
 */

add_action( 'gform_editor_js_set_default_values', 'gravityFormSetDefaultValueOnDropin' );
function gravityFormSetDefaultValueOnDropin() {
    ?>
    case "braintree_ach" :
        if (!field.label)
        field.label = <?php echo json_encode( esc_html__( 'Pay through your Bank Account', 'gravity-forms-braintree' ) ); ?>;
        var accNumber, accType, routingNumber, accName;

        accNumber = new Input(field.id + ".1", <?php echo json_encode( gf_apply_filters( array( 'gform_account_number', rgget( 'id' ) ), esc_html__( 'Account Number', 'gravity-forms-braintree' ), rgget( 'id' ) ) ); ?>);
        accType = new Input(field.id + ".2", <?php echo json_encode( gf_apply_filters( array( 'gform_account_type', rgget( 'id' ) ), esc_html__( 'Account Type', 'gravity-forms-braintree' ), rgget( 'id' ) ) ); ?>);
        routingNumber = new Input(field.id + ".3", <?php echo json_encode( gf_apply_filters( array( 'gform_routing_number', rgget( 'id' ) ), esc_html__( 'Routing Number', 'gravity-forms-braintree' ), rgget( 'id' ) ) ); ?>);
        accName = new Input(field.id + ".4", <?php echo json_encode( gf_apply_filters( array( 'gform_account_name', rgget( 'id' ) ), esc_html__( 'Account Holder Name', 'gravity-forms-braintree' ), rgget( 'id' ) ) ); ?>);
        field.inputs = [accNumber, accType, routingNumber, accName];
        break;
    case "braintree_ach_cc_toggle":
        if (!field.label)
        field.label = <?php echo json_encode( esc_html__( 'Select a Payment Method', 'gravity-forms-braintree' ) ); ?>;
        var paymentMethodToggle;
        paymentMethodToggle = new Input(field.id + ".1", <?php echo json_encode( gf_apply_filters( array( 'gform_payment_method_selected', rgget( 'id' ) ), esc_html__( 'Payment Method Toggle', 'gravity-forms-braintree' ), rgget( 'id' ) ) ); ?>);
        field.inputs = [paymentMethodToggle];
        break;
    case "braintree_credit_card":
        if (!field.label)
        field.label = <?php echo json_encode( esc_html__( 'Credit Card', 'angelleye-gravity-forms-braintree' ) ); ?>;
        var braintreeCC = new Input(field.id + ".1", <?php echo json_encode( gf_apply_filters( array( 'gform_payment_method_selected', rgget( 'id' ) ), esc_html__( 'Credit Card', 'angelleye-gravity-forms-braintree' ), rgget( 'id' ) ) ); ?>);
        field.inputs = [braintreeCC];
        break;
    <?php
}