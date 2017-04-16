<?php


require_once('braintree-php-3.22.0/lib/Braintree.php'); 

$clientToken = Braintree_ClientToken::generate();

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>ApplePay Checkout</title>
	
  </head>
  <body
	
<div id="apple_div" style="display:none;">

<form id="applePayForm" action="serverside.php" method="post">
					
	<input type="hidden" name="payment_method_nonce" value="">
	<input type="image" src="applepaybutton.png" name="saveForm" class="submit" id="pay-by-apple" />

</form>
	
</div>
					



	<!-- Load Braintree JS libraries for Apple Pay -->
	<script src="https://js.braintreegateway.com/web/3.11.1/js/client.min.js"></script>
	<script src="https://js.braintreegateway.com/web/3.11.1/js/apple-pay.min.js"></script>
	
<script>

if (window.ApplePaySession && ApplePaySession.canMakePayments()) {

console.log('This device supports support Apple Pay');


	braintree.client.create({
	authorization: '<?php echo $clientToken; ?>'
	}, function (clientErr, clientInstance) {
		if (clientErr) {
		console.error('Error creating client:', clientErr);
		return;
	}

            braintree.applePay.create({
                client: clientInstance
            }, function (applePayErr, applePayInstance) {
                if (applePayErr) {
                  console.error('Error creating applePayInstance:', applePayErr);
                  return;
                }
				
				
                btApplePayInstance= applePayInstance;
                document.getElementById("apple_div").style.display = "inline";

            });
});

// Button event listener, you have to place the var session = new ApplePaySession() inside this listener or else you'll get an error!
document.getElementById("pay-by-apple").addEventListener("click", function(e){

// Prevent form from submitting before buyer goes through Apple Pay authorization
e.preventDefault();

        var paymentRequest = btApplePayInstance.createPaymentRequest({
            total: {
                label: 'Test merchant',
                amount: 15
            },
            
            // You can setup the required fields here, so the buyer would be prompted to provide them during checkout!
            requiredShippingContactFields: [
    			'name', 'phone', 'email', 'postalAddress'
    		]
        });
                console.log(paymentRequest.countryCode);
		console.log(paymentRequest.currencyCode);
		console.log(paymentRequest.merchantCapabilities);
		console.log(paymentRequest.supportedNetworks);
        
        // Declare a variable for ApplePaySession
        var session = new ApplePaySession(1, paymentRequest);
        
        // Validate merchant URL, this is the domain you enter in GW Apple Pay settings. If you don't have your website's domain in GW, your APP Pay integration will fail!
        session.onvalidatemerchant = function (event) {
            btApplePayInstance.performValidation({
                validationURL: event.validationURL,
                displayName: 'Test merchant'
            }, function (validationErr, merchantSession) {
                if (validationErr) {
                    // You should show an error to the user, e.g. 'Apple Pay failed to load.'
                    console.error('Error validating merchant:', validationErr);
                    session.abort();
                    return;
                }
                session.completeMerchantValidation(merchantSession);
            });
        };
        
        // onpaymentauthorized callback can be used to retrieve details such as shipping address.
        session.onpaymentauthorized = function (event) {
        
        // Apple Pay token is returned here via event.payment.token
		
		
            if (event.payment.shippingContact){
                
                // Shipping address returned!
                console.log('Your shipping address is:', event.payment.shippingContact);
            }
			
	    // tokenize method tokenizes the buyer Apple Pay account and creates a payment nonce.
            btApplePayInstance.tokenize({
                token: event.payment.token
            }, function (tokenizeErr, payload) {
                if (tokenizeErr) {
                    console.error('Error tokenizing Apple Pay:', tokenizeErr);
                    session.completePayment(ApplePaySession.STATUS_FAILURE);
                    return;
                }
                session.completePayment(ApplePaySession.STATUS_SUCCESS);
				
                // Payment nonce retrieved
		alert("Payment nonce is " + payload.nonce + " . Send it to your server-side to create a transaction!" + "\n\n" + "Entire payload returned: " + JSON.stringify(payload, null, 4) + "\n\n" + "Your shipping address is: \n" + JSON.stringify(event.payment.shippingContact), null, 4);
				
		// Add the nonce to the form
		document.forms["applePayForm"].elements["payment_method_nonce"].value = payload.nonce;

		//Automatically submit the form
		document.getElementById("applePayForm").submit();
                
            });
        }
        
        // You have to use the session.begin function to start your Apple Pay session or nothing will work!
        session.begin();

    });
	} else {
	
	console.error('This device is not capable of making Apple Pay payments');
	
	
	}
	
	</script>
	

	
	
  </body>
</html>
