<?php
add_shortcode('PRAYER', function($attr){
	ob_start();
	$submit_text = isset($attr['btn_txt']) && $attr['btn_txt'] ? $attr['btn_txt'] : 'Submit';
	?>

		<form class="prayer-form">
			<input type="text" id="name-input" name="name-input" required="">
			<button type="submit" class="submit-button"><?php echo $submit_text; ?></button>
			<div class="chat-response"></div>
		</form>

	<?php
	
	return ob_get_clean();
});

if(!function_exists('pree')){
    function pree($d){
        echo "<pre>";
        print_r($d);
        echo "</pre>";
    }
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'dsom/v1', '/prayer', array(
        'methods' => 'POST',
        'callback' => 'dsom_prayer_function',
		'permission_callback' => function () {
            return true;
        }
    ) );
} );

function dsom_get_api_resp($prayer_for){

    $request_url = 'https://api.openai.com/v1/completions';
    $request_headers = array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ************Your API Keys******',
    );
    $request_body = array(
        'prompt' => "Please write a prayer for $prayer_for",
        'temperature' => 0,
        "model" =>  "text-curie-001",
        "temperature" => 0,
        "max_tokens" => 500,
        "top_p" => 1,
        "frequency_penalty" => 0,
        "presence_penalty" => 0
    );

    $response = wp_remote_post( $request_url, array(
        'headers' => $request_headers,
        'body' => json_encode( $request_body ),
    ) );

    if ( is_wp_error( $response ) ) {
        // Handle error
        return [
            'status' => 'error',
            'response' => $response
        ];
    } else {
        $response_body = wp_remote_retrieve_body( $response );
        // Handle response

        return [
            'status' => 'success',
            'response' => $response_body
        ];
    }

}

function dsom_prayer_function( $request ) {
    $data = $request->get_params();


    $data = $request->get_params(); // Get the data from the request

    if(isset($data['prayer_for']) && $data['prayer_for']){

        $sanitized_text = sanitize_text_field( $data['prayer_for'] );
        
        $resp = dsom_get_api_resp($sanitized_text);
        return rest_ensure_response( $resp ); // Return the data in the response
    }else{
        
        $data = [
            'status' => 'error',
            'response' => '',
            'message' => 'Name is required!'
        ];
        return rest_ensure_response( $data ); // Return the data in the response
    }

    // Do something with the data here
    // ...



   
}


add_action('wp_footer', 'dsm_prayer_footer');
function dsm_prayer_footer(){
	?>

    <style>

        .chat-response.typing::after {
            content: "|";
            animation: typing 1s infinite;
        }
		.chat-response{
			margin-block: 32px;
		}

        .chat-response::after {
            content: "";
            animation: none;
        }

        @keyframes typing {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

    </style>

	<script>

        jQuery(document).ready(function($){

            
            var resp_text = "";
            function typeWriter() {
                let index = 0;
                responseContainer.textContent = "";
                if (index < resp_text.length) {
                    responseContainer.textContent += resp_text.charAt(index);
                    index++;
                    setTimeout(typeWriter, 50);
                } else {
                    responseContainer.classList.remove('typing');
                }
            }


            $('.prayer-form').on('submit', function(e){

                e.preventDefault();
                
                
                let this_form = $(this);
                let this_form_button = this_form.find('.submit-button');
                let prayer_for = this_form.find('input').val();
                prayer_for = prayer_for.trim();
                this_form_button.prop('disabled', true);
                let responseContainer = this_form.find('.chat-response');
                

                if(prayer_for.length > 0 ){
                    responseContainer.addClass('typing');
                    // Replace with the nonce value for your AJAX request
                    const ajaxNonce = "<?php echo wp_create_nonce( 'my_ajax_action_nonce' ); ?>";

                    const ajaxData = {
                        prayer_for: prayer_for,
                    };
                    var nonce = '<?php echo wp_create_nonce( "my_nonce" ); ?>';
                    $.post('/wp-json/dsom/v1/prayer', ajaxData, function(resp, code){
                        setTimeout(() => {
                            this_form_button.prop('disabled', false);
                        }, 2000);
                        if(resp.status == 'success'){
                            $resp = JSON.parse(resp.response);
                            resp_text = resp.response;
                            if($resp['choices'].length > 0){
                                resp_text = $resp['choices'][0];
                                resp_text = resp_text.text;
                                resp_text = resp_text.replace(/\n/g, '<br>');
                                responseContainer.html(resp_text);
                                responseContainer.removeClass('typing');
                                // typeWriter();
                            }
                        }
                    })


                }else{
                    console.log('Please Enter The Name For Prayer');
                }

            })

 

        })



		
	</script>

	<?php
}
