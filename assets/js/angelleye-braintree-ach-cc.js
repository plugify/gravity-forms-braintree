jQuery(document).ready(function ($) {
    if($('.gform_payment_method_options').length){
        var payment_methods = {};
        $('.gform_payment_method_options input[type=radio]').each(function () {
            var targetdiv = $(this).attr('targetdiv');
            var value = $(this).val();
            payment_methods[value] = targetdiv;
        });

        $('.gform_payment_method_options').on('click','input[type=radio]', function () {
            var selectedradio = $(this).val();
            for (var i in payment_methods)
                if(i!==selectedradio)
                    $(this).closest('form').find('#'+payment_methods[i]).slideUp()

            var targetdiv = $(this).attr('targetdiv');
            $(this).closest('form').find('#'+targetdiv).slideDown();
        });

        var selectedradio = $('.gform_payment_method_options input[type=radio]:checked').val();

        switch(selectedradio){
            case 'braintree_ach':
                $('.gform_payment_method_options input[value=braintree_ach]').trigger('click');
                break;
            default:
            case 'creditcard':
                $('.gform_payment_method_options input[value=creditcard]').trigger('click');
                break;
        }
    }



    $('.custom_ach_form_submit_btn').click(function (e) {
        e.preventDefault();

        var curlabel = $(this).html();
        var form = $(this).closest('form');

        var selectedradio = form.find('.gform_payment_method_options input[type=radio]:checked').val();

        var check_if_ach_form = form.find('.ginput_ach_form_container');
        if(check_if_ach_form.length && (selectedradio === 'braintree_ach' || check_if_ach_form.closest('.gfield').css('display')!=='none')){
            if(form.find('.ginput_container_address').length==0){
                alert('ACH payment requires billing address fields, so please include Billing Address field in your Gravity form.');
                return;
            }

            var account_number = form.find('.ginput_account_number').val();
            var account_number_verification = form.find('.ginput_account_number_verification').val();
            var account_type = form.find('.ginput_account_type').val();
            var routing_number = form.find('.ginput_routing_number').val();
            var account_holdername = form.find('.ginput_account_holdername').val();

            var streetAddress = form.find('.ginput_container_address .address_line_1 input[type=text]').val();
            var extendedAddress = form.find('.ginput_container_address .address_line_2 input[type=text]').val();
            var locality = form.find('.ginput_container_address .address_city input[type=text]').val();
            var region = form.find('.ginput_container_address .address_state input[type=text]').val();
            var postalCode = form.find('.ginput_container_address .address_zip input[type=text]').val();

            var address_validation_errors = [];
            if(streetAddress==''){
                address_validation_errors.push('Please enter a street address.');
            }

            if(locality==''){
                address_validation_errors.push('Please enter your city.');
            }

            if(region==''){
                address_validation_errors.push('Please enter your state.');
            }

            if(postalCode==''){
                address_validation_errors.push('Please enter your postal code.');
            }

            if(address_validation_errors.length){
                alert(address_validation_errors.join('\n'));
                return;
            }

            var achform_validation_errors = [];
            if(routing_number=='' || isNaN(routing_number) || account_number=='' || isNaN(account_number)){
                achform_validation_errors.push('Please enter a valid routing and account number.')
            }

            if(account_type==''){
                achform_validation_errors.push('Please select your account type.')
            }

            if(account_holdername==''){
                achform_validation_errors.push('Please enter the account holder name.');
            }else {
                var account_holder_namebreak = account_holdername.split(' ');
                if (account_type == 'S' && account_holder_namebreak.length < 2) {
                    achform_validation_errors.push('Please enter the account holder first and last name.');
                }
            }

            if(account_number !== account_number_verification){
                achform_validation_errors.push('Account Number and Account Number Verification field should be same.');
            }

            if(achform_validation_errors.length){
                alert(achform_validation_errors.join('\n'));
                return;
            }

            var submitbtn = $(this);
            submitbtn.attr('disabled', true).html('<span>Please wait...</span>').css('opacity', '0.4');

            braintree.client.create({
                authorization: angelleye_gravity_form_braintree_ach_handler_strings.ach_bt_token
            }, function (clientErr, clientInstance) {
                if (clientErr) {
                    alert('There was an error creating the Client, Please check your Braintree Settings.');
                    console.error('clientErr',clientErr);
                    return;
                }

                braintree.dataCollector.create({
                    client: clientInstance,
                    paypal: true
                }, function (err, dataCollectorInstance) {
                    if (err) {
                        alert('We are unable to validate your system, please try again.');
                        resetButtonLoading(submitbtn, curlabel);
                        console.error('dataCollectorError',err);
                        return;
                    }

                    var deviceData = dataCollectorInstance.deviceData;

                    braintree.usBankAccount.create({
                        client: clientInstance
                    }, function (usBankAccountErr, usBankAccountInstance) {
                        if (usBankAccountErr) {
                            alert('There was an error initiating the bank request. Please try again.');
                            resetButtonLoading(submitbtn, curlabel);
                            console.error('usBankAccountErr',usBankAccountErr);
                            return;
                        }

                        var bankDetails = {
                            accountNumber: account_number, //'1000000000',
                            routingNumber: routing_number, //'011000015',
                            accountType: account_type == 'S' ? 'savings' : 'checking',
                            ownershipType: account_type == 'S' ? 'personal' : 'business',
                            billingAddress: {
                                streetAddress: streetAddress, //'1111 Thistle Ave',
                                extendedAddress: extendedAddress,
                                locality: locality, //'Fountain Valley',
                                region: region, //'CA',
                                postalCode: postalCode //'92708'
                            }
                        };

                        if (bankDetails.ownershipType === 'personal') {
                            bankDetails.firstName = account_holder_namebreak[0];
                            bankDetails.lastName = account_holder_namebreak[1];
                        } else {
                            bankDetails.businessName = account_holdername;
                        }

                        usBankAccountInstance.tokenize({
                            bankDetails: bankDetails,
                            mandateText: 'By clicking ["Submit"], I authorize Braintree, a service of PayPal, on behalf of ' + angelleye_gravity_form_braintree_ach_handler_strings.ach_business_name + ' (i) to verify my bank account information using bank information and consumer reports and (ii) to debit my bank account.'
                        }, function (tokenizeErr, tokenizedPayload) {
                            if (tokenizeErr) {
                                var errormsg = tokenizeErr['details']['originalError']['details']['originalError'][0]['message'];
                                if (errormsg.indexOf("Variable 'zipCode' has an invalid value") != -1)
                                    alert('Please enter valid postal code.');
                                else if (errormsg.indexOf("Variable 'state' has an invalid value") != -1)
                                    alert('Please enter valid state code. (e.g.: CA)');
                                else
                                    alert(errormsg);

                                resetButtonLoading(submitbtn, curlabel);
                                console.error('tokenizeErr', tokenizeErr);
                                return;
                            }

                            form.append("<input type='hidden' name='ach_device_corelation' value='" + deviceData + "' />");
                            form.append('<input type="hidden" name="ach_token" value="' + tokenizedPayload.nonce + '" />');
                            form.submit();
                        });
                    });
                });
            });

        }else {
            form.submit();
        }
    });
});

function resetButtonLoading(submitbtn, curlabel) {
    submitbtn.attr('disabled', false).html(curlabel).css('opacity', '1');
}
