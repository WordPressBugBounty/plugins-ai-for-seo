<?php
/**
 * The RobHub Api Communicator. Is used to get client data or to use its AI tools
 *
 * @since 1.0
 */

if (!defined("ABSPATH")) {
    exit;
}

class Ai4Seo_RobHubApiCommunicator {
    public bool $is_initialized = false;
    private string $version = "v1";
    private string $api_url = "https://api.robhub.ai";
    private string $api_username = "";
    private string $api_password = "";
    private string $public_get_free_account_api_password = "_get-free-account-with-some-credits-to-play-with";
    private string $public_client_operations_api_password = "_this-secret-can-be-used-when-no-client-id-isset";
    private string $product = "robhub";
    private string $product_version = "0.0.0";
    private int $min_credits_balance = 1; # todo: will be replaced by the users settings based on the quality of the ai generations
    private bool $does_user_need_to_accept_tos_toc_and_pp = false;
    private bool $is_local_api_enabled = false;
    private string $local_api_url = "http://localhost";
    public int $product_activation_time = 0;
    public const ACCOUNT_SYNC_INTERVAL = 3600; // 1 hour in seconds
    public const BACKGROUND_ACCOUNT_SYNC_INTERVAL = 86400; // 24 hours in seconds
    private bool $has_reset_last_account_sync = false;
    private int $max_api_attempts = 3;
    private array $non_retriable_error_codes = array(
        371816823, # no more credits
        591716925, # could not send email (send-licence-data)
        41228125, # client already exists (get-free-account)
        25164525, # error while downloading file from url
        916101025, # invalid credentials: invalid api username
        351816823, # invalid credentials: invalid api password
        431319725, # invalid credentials: access denied
        3619101024, # inappropriate content detected
        3204525, # cloudflare challenge detected
        311014824, # file not accessible at given URL
    );

    public array $non_post_related_error_codes = array(
        1115424,   # no credits left
        1215424,   # no credits left
        371816823, # no credits left (server side)
        201313823, # endpoint not allowed
        211313823, # request method not allowed
        2113111223,# missing or corrupt auth credentials
        521561224, # endpoint locked
        2313111223, # TypeError while making API call
        2413111223, # Exception while making API call
        2411301024, # user did not accept terms of service
        1913111223, # invalid URL
        4314181024, # request blocked by server provider
        401211124,  # server maintenance
        4414181024, # error receiving proper response from server
        361823824, # api call did not return consumed credits
        371823824, # api call did not return new credits balance
    );

    private int $max_api_payload_size_bytes = 2097152; // 2 MB
    private int $max_response_bytes = 1572864; // what we accept from the API: 1.5 MB
    private int $second_attempt_delay_ms = 500; // 500 milliseconds
    private int $third_attempt_delay_ms = 2000; // 2 second

    private bool $debug_api_call = false; // set to true to enable debug logging for api calls

    private const ENDPOINT_LOCK_DURATIONS = array( # in seconds
        "ai4seo/generate-all-metadata" => 1,
        "ai4seo/generate-all-attachment-attributes" => 1,
        "client/get-free-account" => 5,
        "client/accept-terms" => 60,
        "client/reject-terms" => 60,
        "client/product-deactivated" => 60,
        "client/product-updated" => 60,
        "client/changed-api-user" => 5,
        "client/payg-settings" => 5,
        "client/init-purchase" => 5,
        "client/send-licence-data" => 61
    );

    private array $recent_endpoint_responses = array();

    // === ENVIRONMENTAL VARIABLES ================================================================================= \\

    public string $environmental_variables_option_name = "robhub_environmental_variables";

    public const ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA = "auth_data";
    public const ENVIRONMENTAL_VARIABLE_API_USERNAME = "api_username";
    public const ENVIRONMENTAL_VARIABLE_API_PASSWORD = "api_password";
    public const ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE = "credits_balance";
    public const ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP = "next_free_credits";
    public const ENVIRONMENTAL_VARIABLE_SUBSCRIPTION = "subscription";
    public const ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC = "last_account_sync";
    public const ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED = "is_account_synced";
    public const ENVIRONMENTAL_VARIABLE_GROUP = "group";

    public const DEFAULT_ENVIRONMENTAL_VARIABLES = array(
        self::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA => array(),
        self::ENVIRONMENTAL_VARIABLE_API_USERNAME => "",
        self::ENVIRONMENTAL_VARIABLE_API_PASSWORD => "",
        self::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE => 0,
        self::ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP => 0,
        self::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION => array(),
        self::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC => 0,
        self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED => false,
        self::ENVIRONMENTAL_VARIABLE_GROUP => 'x',
    );
    private array $environmental_variables = self::DEFAULT_ENVIRONMENTAL_VARIABLES;

    private array $allowed_endpoints = array(
        "ai4seo/generate-all-metadata",
        "ai4seo/generate-all-attachment-attributes",

        "client/get-free-account",
        "client/sync",
        "client/accept-terms",
        "client/reject-terms",
        "client/product-deactivated",
        "client/product-updated",
        "client/changed-api-user",
        "client/payg-settings",
        "client/init-purchase",
        "client/send-licence-data",
    );

    private array $generation_endpoints = array(
        "ai4seo/generate-all-metadata",
        "ai4seo/generate-all-attachment-attributes",
    );

    private array $free_endpoints = array(
        "client/get-free-account",
        "client/sync",
        "client/accept-terms",
        "client/reject-terms",
        "client/product-deactivated",
        "client/product-updated",
        "client/changed-api-user",
        "client/payg-settings",
        "client/init-purchase",
        "client/send-licence-data",
    );

    private array $no_need_to_accept_tos_endpoints = array(
        "client/reject-terms",
        "client/product-deactivated"
    );


    // ___________________________________________________________________________________________ \\
    // === INIT ================================================================================== \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    function __construct() {

    }

    // =========================================================================================== \\

    /**
     * Set some product related parameters, during the initialization of the class.
     * @param $product string The product name.
     * @param $product_version string The product version.
     * @param $product_activation_time int the product activation time
     * @return void
     */
    function set_product_parameters(string $product, string $product_version, int $product_activation_time = 0): void {
        if (!$product_activation_time) {
            $product_activation_time = time();
        }
        
        $this->product = $product;
        $this->product_version = $product_version;
        $this->product_activation_time = $product_activation_time;
    }

    // =========================================================================================== \\

    function set_does_user_need_to_accept_tos_toc_and_pp(bool $does_user_need_to_accept_tos_toc_and_pp): void {
        $this->does_user_need_to_accept_tos_toc_and_pp = $does_user_need_to_accept_tos_toc_and_pp;
    }


    // ___________________________________________________________________________________________ \\
    // === CALL ================================================================================== \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    /**
     * Function to call the API.
     *
     * Retries up to two times if a failure occurs and the interpreted error code
     * is not listed in the global non-retriable codes array.
     *
     * @param string $endpoint        The endpoint to check.
     * @param array  $parameters      Additional parameters to send to the API.
     * @param string $request_method  The request method to use. Can be GET, POST, PUT or DELETE.
     * @return array|mixed|string     The response from the API.
     */
    function call( string $endpoint, array $parameters = array(), string $request_method = 'POST' ) {
        $api_call_checksum = $this->prepare_call( $endpoint, $parameters, $request_method );

        if ( ! is_numeric( $api_call_checksum ) ) {
            return $api_call_checksum;
        }

        // build URL (without query string)
        $api_url = $this->build_api_url( $endpoint );

        // build arguments
        $api_arguments = $this->build_api_arguments( $parameters, $request_method, $endpoint );

        // retry configuration
        $attempt      = 0;

        $normalized_response = null;

        while ( $attempt < $this->max_api_attempts ) {
            $attempt++;

            try {
                $raw_response = wp_safe_remote_request( $api_url, $api_arguments );

                // check and normalize response
                $raw_response = $this->check_raw_response( $raw_response, $api_url );
                $normalized_response = $this->normalize_response($raw_response);

            } catch ( TypeError $e ) {
                if ( $this->debug_api_call ) {
                    error_log( 'AI for SEO: TypeError while making API call to ' . $api_url . ': ' . $e->getMessage() );
                }

                $normalized_response = $this->respond_error('TypeError while making API call: ' . $e->getMessage(), 2313111223);
            } catch ( Exception $e ) {
                if ( $this->debug_api_call ) {
                    error_log( 'AI for SEO: Exception while making API call to ' . $api_url . ': ' . $e->getMessage() );
                }

                $normalized_response = $this->respond_error('Exception while making API call: ' . $e->getMessage(), 2413111223);
            }

            // success
            if ( isset( $normalized_response['success'] ) && $normalized_response['success'] === true ) {
                if ( $this->debug_api_call ) {
                    error_log( 'AI for SEO: API call to ' . $api_url . ' was successful on attempt #' . $attempt . '. Response: ' . print_r( $normalized_response, true ) );
                }

                // save the response in the recent_endpoint_responses array
                $this->recent_endpoint_responses[ $api_call_checksum ] = $normalized_response;

                // update new credits balance
                if (isset($normalized_response["new-credits-balance"]) && is_numeric($normalized_response["new-credits-balance"])) {
                    $this->update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE, $normalized_response["new-credits-balance"]);
                }

                return $normalized_response;
            }

            // failure: decide whether to retry
            $this_error_code = $normalized_response['code'] ?? null;

            // stop if code is marked non-retriable or attempts exhausted
            $is_non_retriable = ( $this_error_code !== null && in_array( $this_error_code, $this->non_retriable_error_codes, true ) );
            $has_more_attempts = ( $attempt < $this->max_api_attempts );

            if ( $this->debug_api_call ) {
                error_log(
                    'AI for SEO: API call to ' . $api_url .
                    ' failed on attempt #' . $attempt .
                    ' with error: ' . ( $normalized_response['message'] ?? 'Unknown error' ) .
                    ' (Code: ' . ( $this_error_code !== null ? $this_error_code : 'n/a' ) . ').' .
                    ' Non-retriable=' . ( $is_non_retriable ? 'yes' : 'no' ) .
                    ', WillRetry=' . ( $has_more_attempts && ! $is_non_retriable ? 'yes' : 'no' )
                );
            }

            if ( ! $has_more_attempts || $is_non_retriable ) {
                // stop retry loop
                break;
            }

            // small exponential backoff between retries to reduce burst failures
            // $this->second_attempt_delay_ms, then ~$this->third_attempt_delay_ms
            if ( $attempt === 1 ) {
                usleep( $this->second_attempt_delay_ms * 1000 );
            } elseif ( $attempt === 2 ) {
                usleep( $this->third_attempt_delay_ms * 1000 );
            }

            // reset for next attempt
            $raw_response         = null;
            $normalized_response = null;
        }

        // final failure logging
        if ( $this->debug_api_call ) {
            $final_code    = $normalized_response['code'] ?? 'n/a';
            $final_message = $normalized_response['message'] ?? 'Unknown error';
            error_log( 'AI for SEO: API call to ' . $api_url . ' failed after ' . $attempt . ' attempts. Final error: ' . $final_message . ' (Code: ' . $final_code . ')' );
        }

        // some errors need more attention
        $this->try_handle_special_api_errors($normalized_response);

        return $normalized_response;
    }


    // =========================================================================================== \\

    function prepare_call($endpoint, $parameters, $request_method) {
        // user did not accept terms of service, terms of conditions and privacy policy
        // except for the endpoints in no_need_to_accept_tos_endpoints
        if ($this->does_user_need_to_accept_tos_toc_and_pp && !in_array($endpoint, $this->no_need_to_accept_tos_endpoints)) {
            if ($this->debug_api_call) {
                error_log("AI for SEO: User did not accept Terms of Service, Terms of Conditions and Privacy Policy. Endpoint: " . $endpoint);
            }

            return $this->respond_error("Terms of Service have to be accepted first.", 2411301024);
        }

        // check if we already have a response for this endpoint and parameters
        $api_call_checksum = $this->get_api_call_checksum($endpoint, $parameters, $request_method);
        $api_call_endpoint_checksum = $this->get_api_call_endpoint_checksum($endpoint);
        $transient_name = "robhub_api_lock_" . $api_call_endpoint_checksum;

        if (isset($this->recent_endpoint_responses[$api_call_checksum])) {
            if ($this->debug_api_call) {
                error_log("AI for SEO: Returning cached response for endpoint: " . $endpoint . " with parameters: " . print_r($parameters, true));
            }
            return $this->recent_endpoint_responses[$api_call_checksum];
        }

        // check if this endpoint/parameter/method combination is locked by an active transient
        $endpoint_lock_duration = self::ENDPOINT_LOCK_DURATIONS[$endpoint] ?? 0;

        if ($endpoint_lock_duration > 0) {
            $last_api_call_checksum = get_transient($transient_name);

            if ($last_api_call_checksum == $api_call_checksum) {
                if ($this->debug_api_call) {
                    error_log("AI for SEO: Endpoint " . $endpoint . " is locked for " . $endpoint_lock_duration . " seconds. Last API call checksum: " . $last_api_call_checksum);
                }

                return $this->respond_error("This endpoint is still locked for " . $endpoint_lock_duration . " seconds.", 521561224);
            }
        }

        // check if endpoint is allowed
        if (!$this->is_endpoint_allowed($endpoint)) {
            if ($this->debug_api_call) {
                error_log("AI for SEO: Endpoint " . $endpoint . " is not allowed. Allowed endpoints: " . implode(", ", $this->allowed_endpoints));
            }

            return $this->respond_error("Endpoint " . $endpoint . " is not allowed.", 201313823);
        }

        // check request method
        $request_method = sanitize_text_field($request_method);

        if (!in_array($request_method, array("GET", "POST", "PUT", "DELETE"))) {
            if ($this->debug_api_call) {
                error_log("AI for SEO: Request method " . $request_method . " is not allowed for endpoint " . $endpoint . ". Allowed methods: GET, POST, PUT, DELETE.");
            }

            return $this->respond_error("Request method " . $request_method . " is not allowed.", 211313823);
        }

        // check for proper credentials
        if (!$this->init_credentials()) {
            if ($this->debug_api_call) {
                error_log("AI for SEO: Could not initialize credentials for endpoint " . $endpoint . ".");
            }

            return $this->respond_error("Missing or corrupt auth credentials", 2113111223);
        }

        // if this is not a free endpoint, check credits balance is at least 1
        if (!in_array($endpoint, $this->free_endpoints)) {
            $credits_balance = $this->get_credits_balance();

            if ($credits_balance < 1) {
                if ($this->debug_api_call) {
                    error_log("AI for SEO: No credits left for endpoint " . $endpoint . ". Current balance: " . $credits_balance);
                }

                return $this->respond_error("No Credits left. Please get more Credits.", 1115424);
            }

            // if we have the approximate cost parameter, we use this here for comparison
            if (isset($parameters["approximate_cost"]) && is_numeric($parameters["approximate_cost"])) {
                $approximate_cost = (int) $parameters["approximate_cost"];

                if ($credits_balance < $approximate_cost) {
                    if ($this->debug_api_call) {
                        error_log("AI for SEO: Not enough credits to call endpoint " . $endpoint . ". Required: " . $approximate_cost . ", available: " . $credits_balance);
                    }

                    return $this->respond_error("Not enough Credits left. Please get more Credits.", 1215424);
                }
            }
        }

        // set transient
        if ($endpoint_lock_duration > 0) {
            set_transient($transient_name, $api_call_checksum, $endpoint_lock_duration);
        }

        return $api_call_checksum;
    }

    // =========================================================================================== \\

    function build_api_url($endpoint) {
        # do we use local api?
        if ($this->are_we_using_local_api()) {
            $this->api_url = $this->local_api_url;
        }

        // build URL (without query string)
        $api_url = $this->api_url . "/" . $this->version . "/" . $endpoint;
        $api_url = esc_url_raw($api_url);
        $api_url = filter_var($api_url, FILTER_VALIDATE_URL);

        if ( ! $api_url ) {
            if ( $this->debug_api_call ) {
                error_log( "AI for SEO: Invalid URL for endpoint " . $endpoint . ": " . $api_url );
            }

            return $this->respond_error( "Invalid URL", 1913111223 );
        }

        return $api_url;
    }

    // =========================================================================================== \\

    function build_api_arguments($parameters, $request_method, $endpoint) {
        // prepare headers
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode( $this->api_username . ':' . $this->api_password ),
            'Content-Type'  => 'application/json; charset=utf-8',
        );

        // add generation_settings if available
        if (in_array($endpoint, $this->generation_endpoints)) {
            $use_existing_metadata_as_reference = (bool) ai4seo_get_setting(AI4SEO_SETTING_USE_EXISTING_METADATA_AS_REFERENCE);
            $use_existing_attachment_attributes_as_reference = (bool) ai4seo_get_setting(AI4SEO_SETTING_USE_EXISTING_ATTACHMENT_ATTRIBUTES_AS_REFERENCE);
            $enable_enhanced_entity_recognition = (bool) ai4seo_get_setting(AI4SEO_SETTING_ENABLE_ENHANCED_ENTITY_RECOGNITION);
            $enable_enhanced_celebrity_recognition = (bool) ai4seo_get_setting(AI4SEO_SETTING_ENABLE_ENHANCED_CELEBRITY_RECOGNITION);

            $parameters["generation_settings"] = array(
                "use_existing_metadata_as_reference" => $use_existing_metadata_as_reference,
                "use_existing_attachment_attributes_as_reference" => $use_existing_attachment_attributes_as_reference,
                "enable_enhanced_entity_recognition" => $enable_enhanced_entity_recognition,
                "enable_enhanced_celebrity_recognition" => $enable_enhanced_celebrity_recognition,
            );
        }

        // sanitize and encode parameters
        $parameters = $this->deep_sanitize( $parameters );
        $parameters = $this->deep_sanitize( $parameters, 'html_entity_decode'); # necessary?

        // add product parameter
        $parameters["product"] = $this->product;
        $parameters["product_version"] = $this->product_version;
        $parameters["credits_balance"] = $this->get_credits_balance();

        $api_arguments = $this->compress_api_call_parameters( $parameters, $headers );

        if ( !$api_arguments || !is_array($api_arguments) ) {
            return $this->respond_error( 'Request payload too large. Please reduce input size.', 3811211 );
        }

        $api_arguments += array(
            'method'  => $request_method,
            'timeout' => 60,
            'limit_response_size' => $this->max_response_bytes,
        );

        if ($this->debug_api_call) {
            error_log( "AI for SEO: API arguments for endpoint: " . print_r( $api_arguments, true ) );
        }

        return $api_arguments;
    }

    // =========================================================================================== \\

    /**
     * Encode and optionally compress API call parameters for transport.
     *
     * - JSON encodes the parameters using wp_json_encode.
     * - If JSON exceeds $max_bytes, tries gzip (gzencode) and sets headers.
     * - Enforces a hard size limit of $this->max_api_payload_size_bytes MB (configurable).
     * - Backward compatible: adds a custom header only when compressed.
     *
     * @param array $parameters  The parameters to send.
     * @param array $headers     Existing headers to merge into (optional).
     *
     * @return array|false       Array with keys 'body' (string), 'headers' (array), 'compressed' (bool) or false on failure.
     */
    function compress_api_call_parameters( array $parameters, array $headers = array() ) {
        // Sanity fallback.
        if ( empty( $parameters ) ) {
            $parameters = array();
        }

        // JSON encode with safe flags.
        $json = wp_json_encode(
            $parameters,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        if ( $json === false ) {
            // Malformed or unencodable data.
            error_log('AI for SEO: Failed to JSON encode API call parameters.');
            return false;
        }

        // Prepare a safe byte-length check.
        $json_length = strlen( $json ); // strlen() is byte-safe for PHP strings.

        // Case 1: Fits without compression.
        if ( $json_length <= $this->max_api_payload_size_bytes ) {
            return array(
                'body'       => $json,
                'headers'    => $headers,
                'compressed' => false,
            );
        }

        // Case 2: Try gzip if available.
        $can_gzip = function_exists( 'gzencode' );

        if ( $can_gzip ) {
            // Moderate level 5 keeps CPU cost reasonable in shared hosting.
            $compressed_parameters = @gzencode( $json, 5 );

            if ( $compressed_parameters !== false ) {
                $compressed_parameters_length = strlen( $compressed_parameters );

                if ( $compressed_parameters_length <= $this->max_api_payload_size_bytes ) {
                    // Add standard and custom headers so the API can detect compression.
                    // Backwards compatibility: these headers are only present when compressed.
                    $headers['Content-Encoding']           = 'gzip';
                    $headers['X-AI4SEO-Body-Compressed']   = 'gzip';
                    $headers['X-AI4SEO-Original-Size']     = (string) $json_length;
                    $headers['X-AI4SEO-Compressed-Size']   = (string) $compressed_parameters_length;
                    $headers['Content-Type']               = 'application/json; charset=utf-8';

                    return array(
                        'body'       => $compressed_parameters,
                        'headers'    => $headers,
                        'compressed' => true,
                    );
                }
            }
            // If gzencode failed or still too large, fall through to hard-fail.
        }

        // Case 3: Still too large or compression not possible.
        return false;
    }

    // =========================================================================================== \\

    /**
     * Validate and normalize a WP HTTP response.
     *
     * Enforces a maximum response size to avoid multi-MB JSON parsing.
     *
     * @param mixed  $raw_response The WP HTTP raw response.
     * @param string $api_url      The requested API URL.
     * @return array               Normalized array or ai4seo error array.
     */
    function check_raw_response($raw_response, string $api_url ): array {
        // === CHECK FOR WP AND NETWORK ERRORS ================================================ \\

        if ( is_wp_error( $raw_response ) ) {
            if ( $this->debug_api_call ) {
                error_log( 'AI for SEO: WP Error while making API call to ' . $api_url . ': ' . $raw_response->get_error_message() );
            }

            return $this->respond_error( 'WP Error while making API call: ' . $raw_response->get_error_message(), 5416211025 );
        }

        // Pre-flight size check via Content-Length header (may be absent or compressed)
        try {
            $http_status     = wp_remote_retrieve_response_code( $raw_response );
            $content_length  = wp_remote_retrieve_header( $raw_response, 'content-length' );
            $content_length  = is_numeric( $content_length ) ? (int) $content_length : 0;

            if ( $content_length > 0 && $content_length > $this->max_response_bytes ) {
                if ( $this->debug_api_call ) {
                    error_log( 'AI for SEO: Aborting API call to ' . $api_url . ' due to excessive Content-Length ' . $content_length . ' bytes.' );
                }

                return $this->respond_error( 'API response too large. Please try again later.', 4517211025 );
            }

            // Body retrieval (may be empty if request used limit_response_size and hit the cap)
            $raw_response_body = wp_remote_retrieve_body( $raw_response );
        } catch ( Exception $e ) {
            if ( $this->debug_api_call ) {
                error_log( 'AI for SEO: Exception while retrieving response code/body from ' . $api_url . ': ' . $e->getMessage() );
            }

            return $this->respond_error( 'Error retrieving response status and body: ' . $e->getMessage(), 31224725 );
        }


        // === STATUS CHECK =================================================================== \\

        if ( (int) $http_status !== 200 ) {
            if ( $this->debug_api_call ) {
                // Do not log multi-MB bodies; log only a prefix.
                $log_snippet = is_string( $raw_response_body ) ? substr( $raw_response_body, 0, 2048 ) : '';
                error_log( 'AI for SEO: API request failed with HTTP status ' . $http_status . ' for api url ' . $api_url . '. Response (first 2KB): ' . $log_snippet );
            }

            return $this->respond_error( 'API request failed with HTTP status ' . $http_status . ' - Please check your network connection and try again.', 261823824 );
        }


        // === BODY CHECKS ==================================================================== \\

        if ( empty( $raw_response_body ) ) {
            return $this->respond_error( 'Could not execute API call: empty response.', 271823824 );
        }

        // Enforce decoded body size cap
        if ( strlen( $raw_response_body ) > $this->max_response_bytes ) {
            if ( $this->debug_api_call ) {
                error_log( 'AI for SEO: Aborting API call to ' . $api_url . ' due to oversized body ' . strlen( $raw_response_body ) . ' bytes post-decode.' );
            }

            return $this->respond_error( 'API response too large. Please try again later.', 4617211025 );
        }


        // === VALIDATE PAYLOAD FORMAT ======================================================== \\

        if ( $this->is_json( $raw_response_body ) ) {
            $raw_response_array = json_decode( $raw_response_body, true );

            if ( ! is_array( $raw_response_array ) || empty( $raw_response_array ) ) {
                return $this->respond_error( 'Could not decode JSON response from API call.', 281823824 );
            }

            // Optional: if JSON can expand post-decode (unlikely here), enforce a secondary cap
            // on encoded back length to avoid pathological cases.
            return $raw_response_array;
        } else {
            // Check for HTML error responses
            if ( strpos( $raw_response_body, '<html' ) !== false || strpos( $raw_response_body, 'html>' ) !== false ) {
                if ( strpos( $raw_response_body, 'One moment, please' ) !== false ) {
                    return $this->respond_error( "Failed to connect to our servers. It’s possible that your request was blocked by our server provider's security system, which may occur if your IP address has been flagged as suspicious. Please try again later. If this error persists, please contact our support team.", 4314181024 );
                } elseif ( strpos( $raw_response_body, '<title>Maintenance</title>' ) !== false ) {
                    return $this->respond_error( 'Our servers are currently undergoing maintenance. Please try again later.', 401211124 );
                } else {
                    return $this->respond_error( 'There was an error receiving a proper response from our server. Please try again later.', 4414181024 );
                }
            }

            return $this->respond_error( 'API response is not valid JSON.', 291823824 );
        }
    }


    // =========================================================================================== \\

    function normalize_response(array $raw_response): array {
        $normalized_response = array();


        // === CHECK SUCCESS PARAMETER ============================================================================ \\

        if (isset($raw_response["success"]) && $raw_response["success"] === "true") {
            $raw_response["success"] = true;
        }

        if (isset($raw_response["success"]) && $raw_response["success"] === "false") {
            $raw_response["success"] = false;
        }

        if (isset($raw_response["error"]) && $raw_response["error"] === "true") {
            $raw_response["success"] = false;
        }

        if (isset($raw_response["error"]) && $raw_response["error"] === "false") {
            $raw_response["success"] = true;
        }

        if (isset($raw_response["error"]) && $raw_response["error"] === true) {
            $raw_response["success"] = false;
        }


        // === ALREADY AN RAW OR NORMALIZED ERROR -> MAKE SURE TO NORMALIZE IT PROPERLY! ========================== \\

        if (!isset($raw_response["success"]) || $raw_response["success"] !== true) {
            $raw_response["code"] = isset($raw_response["code"]) ? (int) $raw_response["code"] : 391124725;
            $raw_response["message"] = isset($raw_response["message"]) ? sanitize_text_field($raw_response["message"]) : "API call returned an error without a message.";
            $raw_response["message"] = "API-Error #{$raw_response["code"]}: " . $raw_response["message"];

            return $this->respond_error($raw_response["message"], $raw_response["code"]);
        }


        // === CHECK PAYLOAD COMPLETENESS ======================================================================== \\

        // check if data is set
        if (!isset($raw_response["data"])) {
            return $this->respond_error("API call did not return any data.", 331823824);
        }

        if (empty($raw_response["data"])) {
            return $this->respond_error("API call did not return any data.", 341823824);
        }

        // check if data is an array
        if ($this->is_json($raw_response["data"])) {
            $raw_response["data"] = json_decode($raw_response["data"], true);
        }

        // sanitize data
        $raw_response["data"] = $this->deep_sanitize($raw_response["data"], 'ai4seo_wp_kses');

        if (empty($raw_response["data"])) {
            return $this->respond_error("Could not decode or sanitize API call data.", 341823824);
        }

        // check if credits are set (mandatory for all calls)
        if (!isset($raw_response["credits-consumed"])) {
            return $this->respond_error('API call did not return consumed Credits.', 361823824);
        }

        // check if new credits balance is set (mandatory for all calls)
        if (!isset($raw_response["new-credits-balance"])) {
            return $this->respond_error('API call did not return new Credits balance.', 371823824);
        }

        $normalized_response["success"] = (bool) $raw_response["success"];
        $normalized_response["data"] = $raw_response["data"];
        $normalized_response["credits-consumed"] = (int) $raw_response["credits-consumed"];
        $normalized_response["new-credits-balance"] = (int) $raw_response["new-credits-balance"];

        return $normalized_response;
    }

    // =========================================================================================== \\

    /**
     * Return weather the given string is a valid json
     * @param $string
     * @return bool
     */
    function is_json($string): bool {
        if (!is_string($string)) {
            return false;
        }

        // check if string starts with { or [
        if ($string[0] !== "{" && $string[0] !== "[") {
            return false;
        }

        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }

    // =========================================================================================== \\

    function was_call_successful($response): bool {
        if (is_array($response) && isset($response["success"]) && $response["success"] === true) {
            return true;
        }

        return false;
    }

    // =========================================================================================== \\

    function is_error_post_related($response): bool {
        if (isset($response["code"]) && is_numeric($response["code"])) {
            if (in_array($response["code"], $this->non_post_related_error_codes)) {
                return false;
            }
        }

        return true;
    }

    // =========================================================================================== \\

    function try_handle_special_api_errors($response) {
        if (isset($response["code"]) && is_numeric($response["code"])) {
            # insufficient credits -> discard cache
            if ($response["code"] == 371816823) {
                // singleton -> only do this once per request
                if ($this->has_reset_last_account_sync) {
                    return;
                }

                $this->has_reset_last_account_sync = true;

                // fetch the current credits balance by syncing the account
                $this->reset_last_account_sync();
                $this->sync_account("insufficient-credits");
            }
        }
    }

    // =========================================================================================== \\

    /**
     * Function to either get user credentials from wp_options or to create a free account and save the credentials.
     * @param $try_create_free_account bool Whether to try to create a free account if no credentials are found.
     * @return bool True if credentials are valid, false otherwise.
     */
    function init_credentials(bool $try_create_free_account = true): bool {
        // credentials already saved previously? -> skip
        if ($this->has_credentials()) {
            return true;
        }

        // read robhub auth data from json data in wp_options
        $auth_data = $this->read_auth_data();

        // we do not have any auth data? ask for free account
        if (empty($auth_data) || !isset($auth_data[0]) || !isset($auth_data[1]) || !$auth_data[0] || !$auth_data[1]) {
            if (!$try_create_free_account) {
                return false;
            }

            return $this->init_free_account();
        }

        // otherwise, try to use the saved credentials
        $auth_data = $this->deep_sanitize($auth_data);

        if (isset($auth_data[0]) && isset($auth_data[1])) {
            return $this->use_this_credentials($auth_data[0], $auth_data[1]);
        }

        return false;
    }

    // =========================================================================================== \\

    /**
     * Function to create a free account and save the credentials.
     * @param $base_username string The base username to use for the free account (optional).
     * @param $update_to_database bool Whether to save the new credentials to the database.
     * @return bool True if free account was successfully created, false otherwise.
     */
    function init_free_account(string $base_username = "", bool $update_to_database = true): bool {
        // build pseudo api username and password first
        $new_api_username = $this->build_api_username($base_username);

        if (!$this->use_this_credentials($new_api_username, $this->public_get_free_account_api_password)) {
            error_log("AI for SEO: Could not build api for free account creation.");
            return false;
        }

        $site_url = sanitize_text_field(get_site_url());
        $website_name = sanitize_text_field(get_bloginfo("name"));
        $admin_email = sanitize_email(ai4seo_get_option("admin_email"));
        $client_ip = ai4seo_get_client_ip();
        $server_ip = ai4seo_get_server_ip();
        $user_agent = ai4seo_get_client_user_agent();

        $parameters = array(
            "product_activation_time" => $this->product_activation_time,
            "users_current_time" => time(),
            "website_url" => $site_url,
            "website_name" => $website_name,
            "admin_email_address" => $admin_email,
            "client_ip_address" => $client_ip,
            "server_ip_address" => $server_ip,
            "user_agent" => $user_agent,
        );

        // retrieve our real credentials
        $response = $this->call("client/get-free-account", $parameters);

        // check response
        if (!$this->was_call_successful($response) || !isset($response["data"]["api_username"]) || !isset($response["data"]["api_password"])) {
            $this->api_username = "";
            $this->api_password = "";
            error_log("AI for SEO: Could not create free account. Response: " . print_r($response, true));
            return false;
        }

        // try save new credentials
        if (!$this->use_this_credentials($response["data"]["api_username"], $response["data"]["api_password"], $update_to_database)) {
            $this->api_username = "";
            $this->api_password = "";
            error_log("AI for SEO: Could not save free account credentials.");
            return false;
        }

        // everything went fine
        return true;
    }

    // =========================================================================================== \\

    /**
     * Checks if we got valid credentials already
     * @return bool True if credentials are set, false otherwise.
     */
    function has_credentials(): bool {
        return isset($this->api_username) && $this->api_username && isset($this->api_password) && $this->api_password;
    }

    // =========================================================================================== \\

    /**
     * Saves the given credentials to the corresponding variables.
     * @param $api_username string The api username to save.
     * @param $api_password string The api password to save.
     * @param $update_in_database bool If true, the credentials will be saved in the database.
     * @return bool True if credentials are valid and saved, false otherwise.
     */
    function use_this_credentials(string $api_username, string $api_password, bool $update_in_database = false): bool {
        $api_username = sanitize_key($api_username);
        $api_password = sanitize_key($api_password);

        // validate api username (lowercase, alphanumeric, dashes, underscores, 5-48 characters)
        if (!preg_match("/^[a-z0-9_\-]{5,48}$/", $api_username)) {
            return false;
        }

        // validate api password (alphanumeric, exactly 48 characters)
        if (!preg_match("/^[a-z0-9_\-]{48}$/", $api_password)) {
            return false;
        }

        $this->api_username = $api_username;
        $this->api_password = $api_password;

        if ($update_in_database) {
            $this->update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_API_USERNAME, $api_username);
            $this->update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_API_PASSWORD, $api_password);
            return true;
        }

        return true;
    }

    // =========================================================================================== \\

    /**
     * Function to read the auth data from the environmental variables.
     * @return array The auth data.
     */
    function read_auth_data(): array {
        $api_username = $this->read_environmental_variable(self::ENVIRONMENTAL_VARIABLE_API_USERNAME);
        $api_password = $this->read_environmental_variable(self::ENVIRONMENTAL_VARIABLE_API_PASSWORD);

        return array($api_username, $api_password);
    }

    // =========================================================================================== \\

    /**
     * We build an api username for a free account based on the domain name.
     * @param $base_username string The base username to use (optional).
     * @return string The potential new api username
     */
    function build_api_username(string $base_username = ""): string {
        if (!$base_username) {
            $base_username = $this->get_server_identity();
        }

        // remove schema
        $base_username = str_replace("http://", "", $base_username);
        $base_username = str_replace("https://", "", $base_username);
        // remove port
        $base_username = explode(":", $base_username)[0];
        // remove www.
        $base_username = str_replace("www.", "", $base_username);

        // replace dots with dashes
        $base_username = str_replace(".", "-", $base_username);

        // replace duplicate dashes with a single dash
        $base_username = preg_replace("/-+/", "-", $base_username);

        // remove leading and trailing dashes
        $base_username = trim($base_username, "-");

        // remove all non-alphanumeric characters
        $base_username = preg_replace("/[^a-zA-Z0-9\-]/", "", $base_username);

        // fallback: use random if still empty or shorter than 3 -> generate a random pseudo api username
        if (!$base_username || strlen($base_username) <= 3) {
            $base_username = $this->generate_random_pseudo_api_username();
        }

        // lowercase
        $base_username = strtolower($base_username);

        // use the first 32 chars of the base_username and then add 6 chars of the md5 hash
        $base_username = substr($base_username, 0, 32);
        $md5_hash = md5($base_username . $this->product);

        // use the first 6 chars of the md5 hash
        $base_username .= "-" . substr($md5_hash, 0, 6);

        // generate md5 hash
        return $base_username;
    }

    // =========================================================================================== \\

    /**
     *
     * @return string A reliable server identity based on the server name, address, hostname or site URL.
     */
    function get_server_identity(): string {
        $identifier = '';

        // Check SERVER_NAME
        if (!empty($_SERVER['SERVER_NAME']) && is_string($_SERVER['SERVER_NAME']) && strlen($_SERVER['SERVER_NAME']) >= 3) {
            $identifier = $_SERVER['SERVER_NAME'];
        }
        // Fallback: SERVER_ADDR
        elseif (!empty($_SERVER['SERVER_ADDR']) && is_string($_SERVER['SERVER_ADDR']) && strlen($_SERVER['SERVER_ADDR']) >= 3) {
            $identifier = $_SERVER['SERVER_ADDR'];
        }
        // Fallback: gethostname()
        elseif (function_exists('gethostname') && is_string(gethostname()) && strlen(gethostname()) >= 3) {
            $identifier = gethostname();
        }
        // Fallback: WordPress site URL
        else {
            $site_url = ai4seo_get_option('siteurl');
            if (is_string($site_url) && strlen($site_url) >= 3) {
                $identifier = $site_url;
            }
        }

        // Final fallback
        if (empty($identifier)) {
            $identifier = '';
        }

        // Calculate hash and determine group
        return $identifier;
    }

    // =========================================================================================== \\

    /**
     * Function to sync with client's RobHub Account
     * @param string $sync_reason The reason for the sync (optional).
     * @return array $api_response if the RobHub Account was synced, false on error
     */
    function sync_account(string $sync_reason = "unknown"): array {
        $sync_reason = sanitize_key($sync_reason);
        $api_response = self::call("client/sync", ["reason" => $sync_reason]);

        // Interpret response & check data payload
        if (!self::was_call_successful($api_response) || !isset($api_response["data"]) || !is_array($api_response["data"]) || !$api_response["data"]) {
            self::update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED, false);
            return is_array($api_response) ? $api_response : array(
                "success" => false,
                "message" => "Failed to sync account: " . (is_string($api_response) ? $api_response : "Unknown error"),
                "code" => 461220825
            );
        }

        $synced_account_data = $api_response["data"];

        // next free credits
        $next_free_credits_countdown = (int) ($synced_account_data["next_free_credits_countdown"] ?? 0);
        self::update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP, time() + $next_free_credits_countdown);

        // group
        if (isset($synced_account_data["group"]) && in_array($synced_account_data["group"], array('a', 'b', 'c', 'd', 'e', 'f'))) {
            $group = $synced_account_data["group"];
        }

        self::update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_GROUP, $group ?? 'x');

        // subscription
        if (isset($synced_account_data["plan"]) && $synced_account_data["plan"] != "free") {
            // build subscription array, base on
            $subscription = array(
                "plan" => $synced_account_data["plan"],
                "subscription_start" => $synced_account_data["subscription_start"] ?? "",
                "subscription_end" => $synced_account_data["subscription_end"] ?? "",
                "next_credits_refresh" => $synced_account_data["next_credits_refresh"] ?? "",
                "do_renew" => (bool) ($synced_account_data["do_renew"] ?? false),
                "renew_frequency" => $synced_account_data["renew_frequency"] ?? "monthly",
            );

            self::update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION, $subscription);
        } else {
            self::delete_environmental_variable(self::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION);
        }

        // set the last account sync time
        self::update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC, time());
        self::update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED, true);

        return $api_response;
    }

    // =========================================================================================== \\

    /**
     * Determines an A-F group
     *
     * @return string 'a' to 'f' or 'x' if not determined
     */
    function get_ab_group(): string {
        return $this->read_environmental_variable(self::ENVIRONMENTAL_VARIABLE_GROUP) ?: 'x';
    }

    // =========================================================================================== \\

    function is_group($group):  bool {
        return $this->get_ab_group() === $group;
    }

    // =========================================================================================== \\

    /**
     * This creates and error array response with message and code
     * @param string $message The message to return as an error.
     * @param mixed $code The error code to return. Keep this mixed to prevent int overflow errors on large numbers
     * @return array The error response.
     */
    function respond_error(string $message, $code): array {
        if (strlen($message) > 256) {
            $message = substr($message, 0, 256) . "..."; # remove this to see full error message in the response
        }

        return array(
            "success" => false,
            "message" => wp_kses_post($message),
            "code" => $code
        );
    }

    // =========================================================================================== \\

    /**
     * This function converts the given data to a normalized success response.
     * @param $data array|string The data to return as a success response.
     * @return array|mixed The success response.
     */
    function respond_success($data) {
        if (is_array($data)) {
            $data["success"] = true;
        } else {
            $data = array(
                "success" => true,
                "data" => wp_kses_post($data)
            );
        }

        return $data;
    }

    // =========================================================================================== \\

    /**
     * Return a fully sanitized array, using custom sanitize functions for both keys and values.
     *
     * @param array|string $data The array or value to be sanitized.
     * @param string $sanitize_value_function_name The custom sanitize function for the values (default: sanitize_text_field).
     * @param string $sanitize_key_function_name The custom sanitize function for the keys (default: sanitize_key).
     * @return array|string The sanitized array or value.
     */
    function deep_sanitize($data, string $sanitize_value_function_name = 'sanitize_text_field', string $sanitize_key_function_name = 'sanitize_key') {
        if (is_array($data)) {
            $sanitized_data = array();
            foreach ($data as $key => $value) {
                // Sanitize the key using the key sanitize function
                $sanitized_key = $sanitize_key_function_name($key);

                // Recursively sanitize the value if it's an array, or sanitize the value using the value sanitize function
                if (is_array($value)) {
                    $sanitized_data[$sanitized_key] = ai4seo_deep_sanitize($value, $sanitize_value_function_name, $sanitize_key_function_name);
                } else {
                    if (is_bool($value)) {
                        $sanitized_data[$sanitized_key] = $value;
                    } else {
                        $sanitized_data[$sanitized_key] = $sanitize_value_function_name($value);
                    }
                }
            }
            return $sanitized_data;
        } else {
            if (is_bool($data)) {
                return $data;
            }

            // If it's not an array, sanitize the value directly
            return $sanitize_value_function_name($data);
        }
    }

    // =========================================================================================== \\

    /**
     * Checks if this endpoint is allowed.
     * @param $endpoint string The endpoint to check.
     * @return bool True if the endpoint is allowed, false otherwise.
     */
    function is_endpoint_allowed(string $endpoint): bool {
        return in_array($endpoint, $this->allowed_endpoints);
    }

    // =========================================================================================== \\

    /**
     * Returns the api username if credentials are initialized.
     * @return string The api username or an empty string if credentials are not initialized.
     */
    function get_api_username(): string {
        // Make sure that credentials are initialized
        if (!$this->init_credentials(false)) {
            return "";
        }

        return $this->api_username;
    }

    // =========================================================================================== \\

    /**
     * Returns the api password if credentials are initialized.
     * @return string The api password or an empty string if credentials are not initialized.
     */
    function get_api_password(): string {
        // Make sure that credentials are initialized
        if (!$this->init_credentials(false)) {
            return "";
        }

        return $this->api_password;
    }

    // =========================================================================================== \\

    /**
     * Returns the credits balance of the client.
     * @return int The credits balance of the client.
     */
    function get_credits_balance(): int {
        return (int) $this->read_environmental_variable(self::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE);
    }

    // =========================================================================================== \\

    function is_account_synced(): bool {
        return (bool) $this->read_environmental_variable(self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED);
    }

    // =========================================================================================== \\

    /**
     * Function to unset the last account sync timer to effectively to force sync again
     */
    function reset_last_account_sync(): void {
        $this->update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC, 0);
    }

    // =========================================================================================== \\

    /**
     * Performs an anonymous call to the robhub api to reject the terms of service.
     * @param int $tos_version The version of the terms of service to reject.
     * @return void
     */
    function perform_reject_terms_call(int $tos_version) {
        $this->use_public_client_operation_credentials();

        $reject_terms_parameter = array(
            "timestamp" => time(),
            "tos_version" => AI4SEO_TOS_VERSION_TIMESTAMP
        );

        $this->call("client/reject-terms", $reject_terms_parameter);
    }

    // =========================================================================================== \\

    function perform_lost_licence_call($stripe_email) {
        $this->use_public_client_operation_credentials();

        $endpoint_parameter = array();
        $endpoint_parameter["stripe_email"] = $stripe_email;

        // call robhub api endpoint "client/send-licence-data"
        return $this->call("client/send-licence-data", $endpoint_parameter);
    }

    // =========================================================================================== \\

    function perform_product_deactivated_call() {
        $this->use_public_client_operation_credentials();

        // call robhub api endpoint "client/product-deactivated"
        $this->call("client/product-deactivated");
    }

    // =========================================================================================== \\

    function use_public_client_operation_credentials(): void {
        if (!$this->init_credentials(false)) {
            $random = $this->generate_random_pseudo_api_username();
            $this->api_username = $this->build_api_username($random);
        }

        $this->api_password = $this->public_client_operations_api_password;
    }

    // =========================================================================================== \\

    /**
     * Generates a random api username
     * @return string The generated api username
     */
    function generate_random_pseudo_api_username(): string {
        return $this->product . "-" . rand(1000000, 9999999);
    }


    // ___________________________________________________________________________________________ \\
    // === ROBHUB ENVIRONMENTAL VARIABLES ======================================================== \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    function set_environmental_variables_option_name(string $environmental_variables_option_name): void {
        $this->environmental_variables_option_name = $environmental_variables_option_name;
    }

    // =========================================================================================== \\

    /**
     * Function to retrieve all robhub environmental variables
     * @return array All RobHub environmental variables
     */
    function read_all_environmental_variables(): array {
        if (!isset($this->environmental_variables) || !$this->environmental_variables) {
            $this->environmental_variables = self::DEFAULT_ENVIRONMENTAL_VARIABLES;
        }

        // use cached version
        if ($this->environmental_variables !== self::DEFAULT_ENVIRONMENTAL_VARIABLES) {
            return $this->environmental_variables;
        }

        $current_environmental_variables = ai4seo_get_option($this->environmental_variables_option_name);
        $current_environmental_variables = maybe_unserialize($current_environmental_variables);

        // fallback to existing environmental variables
        if (!is_array($current_environmental_variables) || !$current_environmental_variables) {
            return $this->environmental_variables;
        }

        // go through each environmental variable and check if it is valid
        foreach ($this->environmental_variables as $environmental_variable_name => $default_environmental_variable_value) {
            // set default if not set
            if (!isset($current_environmental_variables[$environmental_variable_name])) {
                $current_environmental_variables[$environmental_variable_name] = self::DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name];
            }

            // validate
            if (!$this->validate_environmental_variable_value($environmental_variable_name, $current_environmental_variables[$environmental_variable_name])) {
                error_log("ROBHUB: Invalid value for environmental variable '" . $environmental_variable_name . "'. #541226225");
                $current_environmental_variables[$environmental_variable_name] = self::DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name];
            }

            $this->environmental_variables[$environmental_variable_name] = $current_environmental_variables[$environmental_variable_name];
        }

        return $this->environmental_variables;
    }

    // =========================================================================================== \\

    /**
     * Function to retrieve a specific robhub environmental variable
     * @param string $environmental_variable_name The name of the robhub environmental variable
     * @return mixed The value of the environmental variable
     */
    function read_environmental_variable(string $environmental_variable_name) {
        // Make sure that $environmental_variable_name-parameter has content
        if (!$environmental_variable_name) {
            error_log("ROBHUB: Environmental variable name is empty. #3515181024");
            return null;
        }

        // check for a default value
        if (!isset(self::DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name])) {
            error_log("ROBHUB: Environmental variable '" . $environmental_variable_name . "' does not exist. #197825");
            return null;
        }

        $current_environmental_variables = $this->read_all_environmental_variables();

        // Check if the $environmental_variable_name-parameter exists in environmental variables-array
        if (isset($current_environmental_variables[$environmental_variable_name])) {
            return $current_environmental_variables[$environmental_variable_name];
        } else {
            return self::DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name];
        }
    }

    // =========================================================================================== \\

    /**
     * Function to update a specific robhub environmental variable
     * @param string $environmental_variable_name The name of the robhub environmental variable
     * @param mixed $new_environmental_variable_value The new value of the robhub environmental variable
     * @return bool True if the robhub environmental variable was updated successfully, false if not
     */
    function update_environmental_variable(string $environmental_variable_name, $new_environmental_variable_value): bool {
        if (!isset(self::DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name])) {
            error_log("ROBHUB: Environmental variable '" . $environmental_variable_name . "' does not exist. #1197825");
            return false;
        }

        // Make sure that the new value of the environmental variable is valid
        if (!$this->validate_environmental_variable_value($environmental_variable_name, $new_environmental_variable_value)) {
            error_log("ROBHUB: Invalid value for environmental variable '" . $environmental_variable_name . "'. #3715181024");
            return false;
        }

        // sanitize
        $new_environmental_variable_value = $this->deep_sanitize($new_environmental_variable_value);

        // overwrite entry in $current_environmental_variables-array
        $current_environmental_variables = $this->read_all_environmental_variables();

        // is same as default value? delete it
        if ($new_environmental_variable_value == self::DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name]) {
            unset($current_environmental_variables[$environmental_variable_name]);
        } else {
            // no change at all?
            if (isset($current_environmental_variables[$environmental_variable_name])
                && $current_environmental_variables[$environmental_variable_name] == $new_environmental_variable_value) {
                return true;
            }

            $current_environmental_variables[$environmental_variable_name] = $new_environmental_variable_value;
        }

        // no changes made
        if($current_environmental_variables == $this->environmental_variables) {
            return true;
        }

        // update the class parameter as well
        $this->environmental_variables = $current_environmental_variables;

        // Save updated environmental variables to database
        return ai4seo_update_option($this->environmental_variables_option_name, $current_environmental_variables, true);
    }

    // =========================================================================================== \\

    /**
     * Bulk update RobHub environmental variables.
     *
     * @param array $environmental_variable_updates Associative array: name => value
     * @return array {
     *     @type bool  $success        True if persisted successfully (or nothing to persist).
     *     @type int   $updated_count  Number of variables changed (added/updated/removed).
     *     @type array $invalid_names  Unknown names skipped.
     *     @type array $invalid_values Names skipped due to invalid values.
     * }
     */
    public function bulk_update_environmental_variables(array $environmental_variable_updates ): array {
        $result = array(
            'success'        => true,
            'updated_count'  => 0,
            'invalid_names'  => array(),
            'invalid_values' => array(),
        );

        // Read current overrides once.
        $current_environmental_variables = $this->read_all_environmental_variables();

        if ( empty( $environmental_variable_updates ) ) {
            return $result;
        }

        foreach ( $environmental_variable_updates as $this_name => $this_value ) {
            // Name must exist in defaults.
            if ( ! isset( self::DEFAULT_ENVIRONMENTAL_VARIABLES[ $this_name ] ) ) {
                $result['invalid_names'][] = $this_name;
                error_log( 'ROBHUB: Environmental variable \'' . $this_name . '\' does not exist. #1197825B' );
                continue;
            }

            // Validate value.
            if ( ! $this->validate_environmental_variable_value( $this_name, $this_value ) ) {
                $result['invalid_values'][] = $this_name;
                error_log( 'ROBHUB: Invalid value for environmental variable \'' . $this_name . '\'. #3715181024B' );
                continue;
            }

            // Sanitize.
            $this_value = $this->deep_sanitize( $this_value );

            // If equals default, remove override if present.
            if ( $this_value == self::DEFAULT_ENVIRONMENTAL_VARIABLES[ $this_name ] ) {
                if ( isset( $current_environmental_variables[ $this_name ] ) ) {
                    unset( $current_environmental_variables[ $this_name ] );
                    $result['updated_count']++;
                }
                continue;
            }

            // Skip if unchanged.
            if ( isset( $current_environmental_variables[ $this_name ] )
                && $current_environmental_variables[ $this_name ] == $this_value ) {
                continue;
            }

            // Apply change.
            $current_environmental_variables[ $this_name ] = $this_value;
            $result['updated_count']++;
        }

        // No effective changes.
        if ( $current_environmental_variables == $this->environmental_variables ) {
            return $result;
        }

        // Update in-memory cache.
        $this->environmental_variables = $current_environmental_variables;

        // Persist once.
        $did_update = ai4seo_update_option( $this->environmental_variables_option_name, $current_environmental_variables, true );
        if ( ! $did_update ) {
            $result['success'] = false;
            error_log( 'ROBHUB: Failed to persist environmental variables in bulk update. #64912045C' );
        }

        return $result;
    }


    // =========================================================================================== \\

    /**
     * Function to delete an robhub environmental variable
     * @param string $environmental_variable_name The name of the robhub environmental variable
     * @return bool True if the robhub environmental variable was deleted successfully, false if not
     */
    function delete_environmental_variable(string $environmental_variable_name): bool {
        // Make sure that $environmental_variable_name-parameter has content
        if (!$environmental_variable_name) {
            error_log("ROBHUB: Environmental variable name is empty. #31319225");
            return false;
        }

        // overwrite entry in $current_environmental_variables-array
        $current_environmental_variables = $this->read_all_environmental_variables();

        if (!isset($current_environmental_variables[$environmental_variable_name])) {
            return false;
        }

        // delete the entry
        unset($current_environmental_variables[$environmental_variable_name]);

        // update the class parameter as well
        $this->environmental_variables = $current_environmental_variables;

        // Save updated environmental variables to database
        return ai4seo_update_option($this->environmental_variables_option_name, $current_environmental_variables, true);
    }

    // =========================================================================================== \\

    /**
     * Deletes all robhub environmental variables
     * @return bool
     */
    function delete_all_environmental_variables(): bool {
        $this->environmental_variables = self::DEFAULT_ENVIRONMENTAL_VARIABLES;
        return ai4seo_delete_option($this->environmental_variables_option_name);
    }

    // =========================================================================================== \\

    /**
     * Validate value of an robhub environmental variable
     * @param string $environmental_variable_name The name of the robhub environmental variable
     * @param mixed $environmental_variable_value The value of the robhub environmental variable
     */
    function validate_environmental_variable_value(string $environmental_variable_name, $environmental_variable_value): bool {
        switch ($environmental_variable_name) {
            case self::ENVIRONMENTAL_VARIABLE_API_USERNAME:
                if ($environmental_variable_value && !preg_match("/^[a-z0-9_\-]{5,48}$/", $environmental_variable_value)) {
                    return false;
                }

                return true;

            case self::ENVIRONMENTAL_VARIABLE_API_PASSWORD:
                if ($environmental_variable_value && !preg_match("/^[a-z0-9_\-]{48}$/", $environmental_variable_value)) {
                    return false;
                }

                return true;

            case self::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE:
            case self::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC:
            case self::ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP:
                // contains only of numbers
                return is_numeric($environmental_variable_value) && $environmental_variable_value >= 0;

            case self::ENVIRONMENTAL_VARIABLE_GROUP:
                // must be one of the allowed groups
                return in_array($environmental_variable_value, array('a', 'b', 'c', 'd', 'e', 'f', 'x', ''));

            case self::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION:
                // must be an array
                if (!is_array($environmental_variable_value)) {
                    return false;
                }

                return true;

            case self::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA:
                // array, contains of two elements, each of them contains only of alphanumeric characters
                if (!is_array($environmental_variable_value)) {
                    return false;
                }

                // empty array is allowed
                if (count($environmental_variable_value) === 0) {
                    return true;
                }

                if (count($environmental_variable_value) !== 2) {
                    return false;
                }

                if (!preg_match("/^[a-z0-9_\-]{5,48}$/", $environmental_variable_value[0])) {
                    return false;
                }

                if (!preg_match("/^[a-z0-9_\-]{48}$/", $environmental_variable_value[1])) {
                    return false;
                }

                return true;

            case self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED:
                return is_bool($environmental_variable_value);

            default:
                return false;
        }
    }

    // =========================================================================================== \\

    /**
     * Function to get the checksum of an API call.
     * @param string $endpoint The endpoint of the API call.
     * @param array $parameters The parameters of the API call.
     * @param string $method The method of the API call.
     * @return int The crc32 checksum of the API call.
     */
    function get_api_call_checksum(string $endpoint, array $parameters, string $method): int {
        return crc32($endpoint . serialize($parameters) . $method);
    }

    // =========================================================================================== \\

    /**
     * Function to get the checksum of an API call endpoint.
     * @param string $endpoint The endpoint of the API call endpoint
     * @return int The crc32 checksum of the API call endpoint
     */
    function get_api_call_endpoint_checksum(string $endpoint): int {
        return crc32($endpoint);
    }

    // =========================================================================================== \\

    /**
     * Function to tidy up all existing api lock transients
     */
    function tidy_up_api_locks(): void {
        foreach (self::ENDPOINT_LOCK_DURATIONS as $endpoint => $duration) {
            $endpoint_checksum = $this->get_api_call_endpoint_checksum($endpoint);
            $transient_name = "robhub_api_lock_" . $endpoint_checksum;
            delete_transient($transient_name);
        }
    }

    // =========================================================================================== \\

    function tidy_up_deprecated_auth_data() {
        if (!self::read_environmental_variable(self::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA)) {
            return true;
        }

        $old_auth_data = self::read_environmental_variable(self::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA);
        $old_api_username = sanitize_text_field($old_auth_data[0] ?? "");
        $old_api_password = sanitize_text_field($old_auth_data[1] ?? "");

        if ($old_api_username) {
            self::update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_API_USERNAME, $old_api_username);
        }

        if ($old_api_password) {
            self::update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_API_PASSWORD, $old_api_password);
        }

        self::delete_environmental_variable(self::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA);
    }


    // ___________________________________________________________________________________________ \\
    // === LOCAL MODE ============================================================================ \\
    // ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

    function init_local_mode(bool $is_local_api_enabled, string $local_api_url): void {
        $this->is_local_api_enabled = $is_local_api_enabled;
        $this->local_api_url = $local_api_url;
    }

    // =========================================================================================== \\

    /**
     * Function to check whether the current environment is a localhost environment
     * @return bool Whether the current environment is a localhost environment
     */
    function are_we_using_local_api(): bool {
        return $this->is_local_api_enabled && $this->are_we_on_a_localhost_system();
    }

    // =========================================================================================== \\

    function are_we_on_a_localhost_system(): bool {
        return (sanitize_text_field($_SERVER["SERVER_NAME"]) === "127.0.0.1" || sanitize_text_field($_SERVER["SERVER_NAME"]) === "localhost" || sanitize_text_field($_SERVER["SERVER_NAME"]) === str_replace("http://", "", $this->local_api_url));
    }
}