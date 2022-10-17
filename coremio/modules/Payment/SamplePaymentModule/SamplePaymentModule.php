<?php
    class SamplePaymentModule extends PaymentGatewayModule
    {
        function __construct()
        {
            $this->name             = __CLASS__;
            $this->standard_card    = true;

            parent::__construct();
        }

        public function config_fields()
        {
            return [
                'example1'          => [
                    'name'              => "Text",
                    'description'       => "Description for Text",
                    'type'              => "text",
                    'value'             => $this->config["settings"]["example1"] ?? '',
                    'placeholder'       => "Example Placeholder",
                ],
                'example2'          => [
                    'name'              => "Password",
                    'description'       => "Description for Password",
                    'type'              => "password",
                    'value'             => $this->config["settings"]["example2"] ?? '',
                    'placeholder'       => "Example Placeholder",
                ],
                'example3'          => [
                    'name'              => "Approval Button",
                    'description'       => "Description for Approval Button",
                    'type'              => "approval",
                    'value'             => 1,
                    'checked'           => (boolean) (int) ($this->config["settings"]["example3"] ?? 0),
                ],
                'example4'          => [
                    'name'              => "Dropdown Menu 1",
                    'description'       => "Description for Dropdown Menu 1",
                    'type'              => "dropdown",
                    'options'           => "Option 1,Option 2,Option 3,Option 4",
                    'value'             => $this->config["settings"]["example4"] ?? '',
                ],
                'example5'          => [
                    'name'              => "Dropdown Menu 2",
                    'description'       => "Description for Dropdown Menu 2",
                    'type'              => "dropdown",
                    'options'           => [
                        'opt1'     => "Option 1",
                        'opt2'     => "Option 2",
                        'opt3'     => "Option 3",
                        'opt4'     => "Option 4",
                    ],
                    'value'         => $this->config["settings"]["example5"] ?? '',
                ],
                'example6'          => [
                    'name'              => "Radio Button 1",
                    'description'       => "Description for Radio Button 1",
                    'width'             => 40,
                    'description_pos'   => 'L',
                    'is_tooltip'        => true,
                    'type'              => "radio",
                    'options'           => "Option 1,Option 2,Option 3,Option 4",
                    'value'             => $this->config["settings"]["example6"] ?? '',
                ],
                'example7'          => [
                    'name'              => "Radio Button 2",
                    'description'       => "Description for Radio Button 2",
                    'description_pos'   => 'L',
                    'is_tooltip'        => true,
                    'type'              => "radio",
                    'options'           => [
                        'opt1'     => "Option 1",
                        'opt2'     => "Option 2",
                        'opt3'     => "Option 3",
                        'opt4'     => "Option 4",
                    ],
                    'value'             => $this->config["settings"]["example7"] ?? '',
                ],
                'example8'          => [
                    'name'              => "Text Area",
                    'description'       => "Description for text area",
                    'rows'              => "3",
                    'type'              => "textarea",
                    'value'             => $this->config["settings"]["example8"] ?? '',
                    'placeholder'       => "Example placeholder",
                ]
            ];
        }

        public function capture($params=[])
        {
            $api_key            = $this->config["settings"]["example1"] ?? 'N/A';
            $secret_key         = $this->config["settings"]["example2"] ?? 'N/A';
            $storage_token      = '';

            if($params['card_storage'])
                $storage_token = $params['card_storage']['token'];


            if($storage_token)
                $card = [];
            else
                $card = [
                    'card_number'        => $params['num'],
                    'card_holder_name'   => $params['holder_name'],
                    'card_expiry'        => $params['expiry_m']."-".$params['expiry_y'],
                    'card_cvc'           => $params['cvc'],
                ];

            // Does the customer want to keep the card?
            if($this->checkSaveCard()) $card['card_storage'] = 'enable';


            $fields             = [
                'client' => [
                    'first_name'    => $params["clientInfo"]->name,
                    'last_name'     => $params["clientInfo"]->surname,
                    'email'         => $params["clientInfo"]->email,
                    'address'       => [
                        'country'       => $params["clientInfo"]->address->country_code,
                        'city'          => $params["clientInfo"]->address->city,
                        'state'         => $params["clientInfo"]->address->counti,
                        'postcode'      => $params["clientInfo"]->address->zipcode,
                        'detail'        => $params["clientInfo"]->address->address,
                    ],
                ],
                'card'                  => $card,
                'storage_token'         => $storage_token,
                'amount'                => $params['amount'],
                'currency'              => $this->currency($params['currency']),
                'custom_id'             => $params["checkout_id"],
            ];


            // Here we are making an API call.
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "api.sample.com/checkout/capture");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'APIKEY: '.$api_key,
                'SECRET: '.$secret_key,
                'Content-Type: application/json',
            ));
            curl_setopt($curl,CURLOPT_POST,1);
            curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($fields));
            $result = curl_exec($curl);
            if(curl_errno($curl))
            {
                return [
                    'status' => 'error',
                    'message' => curl_error($curl)
                ];
            }
            $result             = json_decode($result,true);

            if($result && $result['status'] == 'success')
                return [
                    'status' => 'successful',
                    'message' => ['Merchant Transaction ID' => $result['transaction_id']],
                    'card_storage_token' => $result["storage_token"] ?? '',
                ];
            else
                return [
                    'status'            => 'error',
                    'message'           => $result['error_message'] ?? '!API ERROR!',
                ];

        }

        /*
         * If your payment service provider does not support the card wipe feature, you can remove the functionality.
         */
        public function remove_stored_card($params=[])
        {
            $api_key        = $this->config["settings"]["example1"] ?? 'N/A';
            $secret_key     = $this->config["settings"]["example2"] ?? 'N/A';
            $token          = $params['token'];

            // Here we are making an API call.
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "api.sample.com/card-storage/remove/".$token);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'APIKEY: '.$api_key,
                'SECRET: '.$secret_key,
                'Content-Type: application/json',
            ));
            $result = curl_exec($curl);
            if(curl_errno($curl))
            {
                $result      = false;
                $this->error = curl_error($curl);
            }
            $result             = json_decode($result,true);

            if($result && $result['status'] == 'OK') $result = true;
            else
            {
                $this->error = $result['message'] ?? 'something went wrong';
                $result = false;
            }

            return $result;
        }


        /*
         * If it has "redirect" status from "capture" function, you can use "callback" function to complete payment
         * usually "callback" function is needed for "3D Secure" process.
         */
        public function callback()
        {
            $custom_id      = (int) Filter::init("POST/custom_id","numbers");

            if(!$custom_id){
                $this->error = 'ERROR: Custom id not found.';
                return false;
            }

            $checkout       = $this->get_checkout($custom_id);

            // Checkout invalid error
            if(!$checkout)
            {
                $this->error = 'Checkout ID unknown';
                return false;
            }

            // You introduce checkout to the system
            $this->set_checkout($checkout);

            // You decide the status of the payment.

            return [
                /* You can define it as 'successful' or 'pending'.
                 * 'successful' : Write if the payment is complete.
                 * 'pending' : Write if the payment is pending confirmation.
                 */
                'status'            => 'successful',

                /*
                 * If there is anything you need to inform the manager about the payment, please fill it out.
                 * Acceptable value : 'array' and 'string'
                 */
                'message'        => [
                    'Merchant Transaction ID' => '123X456@23',
                ],
                // Write if you want to show a message to the person on the callback page.
                'callback_message'        => 'Transaction Successful',

                // Usage card storage token
                'card_storage_token' => $this->checkSaveCard() ? '1a2b3c4d5e6f' : '',

                'paid'                    => [
                    'amount'        => 15,
                    'currency'      => "USD",
                ],
            ];
        }

        /*
         * If your payment service provider does not support the refund feature, you can remove the functionality.
         */
        public function refund($checkout=[])
        {
            $custom_id      = $checkout["id"];
            $api_key        = $this->config["settings"]["example1"] ?? 'N/A';
            $secret_key     = $this->config["settings"]["example2"] ?? 'N/A';
            $amount         = $checkout["data"]["total"];
            $currency       = $this->currency($checkout["data"]["currency"]);
            $invoice_id     = $checkout["data"]["invoice_id"] ?? 0;

            $invoice            = Invoices::get($invoice_id);
            $method_msg         = $invoice["pmethod_msg"] ?? [];
            $transaction_id     = $method_msg["Transaction ID"] ?? false;



            $force_curr     = $this->config["settings"]["force_convert_to"] ?? 0;
            if($force_curr > 0)
            {
                $amount         = Money::exChange($amount,$currency,$force_curr);
                $currency       = $this->currency($force_curr);
            }

            // Here we are making an API call.
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "api.sample.com/refund/".$transaction_id);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'APIKEY: '.$api_key,
                'SECRET: '.$secret_key,
                'Content-Type: application/json',
            ));
            $result = curl_exec($curl);
            if(curl_errno($curl))
            {
                $result      = false;
                $this->error = curl_error($curl);
            }
            $result             = json_decode($result,true);

            if($result && $result['status'] == 'OK') $result = true;
            else
            {
                $this->error = $result['message'] ?? 'something went wrong';
                $result = false;
            }

            return $result;
        }

    }