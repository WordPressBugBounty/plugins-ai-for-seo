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
    private string $version = "v1";
    private string $api_url = "https://api.robhub.ai";
    private string $api_username;
    private string $api_password;
    private string $public_get_free_account_api_password = "_get-free-account-with-some-credits-to-play-with";
    private string $public_client_operations_api_password = "_this-secret-can-be-used-when-no-client-id-isset";
    private string $product = "robhub";
    private string $product_version = "0.0.0";
    private int $min_credits_balance = 1; # todo: will be replaced by the users settings based on the quality of the ai generations
    private int $credits_balance_cache_lifetime = 86400; // 24 hours
    private bool $does_user_need_to_accept_tos_toc_and_pp = false;
    private bool $is_local_api_enabled = false;
    private string $local_api_url = "http://localhost";
    public int $product_activation_time = 0;

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
    );

    private array $recent_endpoint_responses = array();

    public const ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA = "auth_data";
    public const ENVIRONMENTAL_VARIABLE_API_USERNAME = "api_username";
    public const ENVIRONMENTAL_VARIABLE_API_PASSWORD = "api_password";
    public const ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE = "credits_balance";
    public const ENVIRONMENTAL_VARIABLE_LAST_CREDIT_BALANCE_CHECK = "last_credit_balance_check";
    public string $environmental_variables_option_name = "robhub_environmental_variables";
    public const DEFAULT_ENVIRONMENTAL_VARIABLES = array(
        self::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA => array(),
        self::ENVIRONMENTAL_VARIABLE_API_USERNAME => "",
        self::ENVIRONMENTAL_VARIABLE_API_PASSWORD => "",
        self::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE => 0,
        self::ENVIRONMENTAL_VARIABLE_LAST_CREDIT_BALANCE_CHECK => 0,
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
    );

    private array $no_need_to_accept_tos_endpoints = array(
        "client/reject-terms",
        "client/product-deactivated"
    );

    function __construct() {

    }

    // =========================================================================================== \\

    /**
     * Function to call the API.
     * @param $endpoint string The endpoint to check.
     * @param $parameters array Additional parameters to send to the API.
     * @param $request_method string The request method to use. Can be GET, POST, PUT or DELETE.
     * @return array|mixed|string The response from the API.
     */
    function call(string $endpoint, array $parameters = array(), string $request_method = "GET") {
        // user did not accept terms of service, terms of conditions and privacy policy
        // except for the endpoints in no_need_to_accept_tos_endpoints
        if ($this->does_user_need_to_accept_tos_toc_and_pp && !in_array($endpoint, $this->no_need_to_accept_tos_endpoints)) {
            return $this->respond_error("Terms of Service have to be accepted first.", 2411301024);
        }

        // check if we already have a response for this endpoint and parameters
        $api_call_checksum = $this->get_api_call_checksum($endpoint, $parameters, $request_method);
        $api_call_endpoint_checksum = $this->get_api_call_endpoint_checksum($endpoint);
        $transient_name = "robhub_api_lock_" . $api_call_endpoint_checksum;

        if (isset($this->recent_endpoint_responses[$api_call_checksum])) {
            return $this->recent_endpoint_responses[$api_call_checksum];
        }

        // check if this endpoint/parameter/method combination is locked by an active transient
        $endpoint_lock_duration = self::ENDPOINT_LOCK_DURATIONS[$endpoint] ?? 0;

        if ($endpoint_lock_duration > 0) {
            $last_api_call_checksum = get_transient($transient_name);

            if ($last_api_call_checksum == $api_call_checksum) {
                return $this->respond_error("This endpoint is still locked for " . $endpoint_lock_duration . " seconds.", 521561224);
            }
        }

        # do we use local api?
        if ($this->are_we_using_local_api()) {
            $this->api_url = $this->local_api_url;
        }

        // check if endpoint is allowed
        if (!$this->is_endpoint_allowed($endpoint)) {
            return $this->respond_error("Endpoint " . $endpoint . " is not allowed.", 201313823);
        }

        // check request method
        if (!in_array($request_method, array("GET", "POST", "PUT", "DELETE"))) {
            return $this->respond_error("Request method " . $request_method . " is not allowed.", 211313823);
        }

        // check for proper credentials
        if (!$this->init_credentials()) {
            return $this->respond_error("Missing or corrupt auth credentials", 2113111223);
        }

        // if this is not a free endpoint, check credits balance
        if (!in_array($endpoint, $this->free_endpoints)) {
            $credits_balance = $this->get_credits_balance();

            if ($credits_balance < $this->min_credits_balance) {
                return $this->respond_error("No Credits left. Please buy more Credits.", 1115424);
            }
        }

        $request_method = sanitize_text_field($request_method);

        // separate input parameter
        if (isset($parameters["input"])) {
            $input = $parameters["input"];
            $input = html_entity_decode($input);
            unset($parameters["input"]);
        } else {
            $input = "";
        }

        // add product parameter
        $parameters["product"] = $this->product;
        $parameters["product_version"] = $this->product_version;

        // build url
        if ($parameters) {
            // go through each parameter and sanitize it
            $parameters = $this->deep_sanitize($parameters, 'rawurlencode');

            // Specify the character encoding as UTF-8
            $encoded_parameters = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

            $curl_url = $this->api_url . "/" . $this->version . "/" . $endpoint . "?" . $encoded_parameters;
        } else {
            $curl_url = $this->api_url . "/" . $this->version . "/" . $endpoint;
        }

        // sanitize url
        $curl_url = esc_url_raw($curl_url);

        // validate url
        $curl_url = filter_var($curl_url, FILTER_VALIDATE_URL);

        if (!$curl_url) {
            return $this->respond_error("Invalid URL", 1913111223);
        }

        // Prepare headers for basic auth
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode($this->api_username . ':' . $this->api_password)
        );

        // Create an array of arguments for wp_safe_remote_request
        $args = array(
            'headers'     => $headers,
            'body'        => array("input" => $input),
            'method'      => $request_method,
            'timeout'     => 300  // Timeout in seconds (5 minutes)
        );

        // Make the request
        try {
            $raw_response = wp_safe_remote_request($curl_url, $args);
        } catch(TypeError $e) {
            return $this->respond_error($e->getMessage(), 2313111223);
        } catch(Exception $e) {
            return $this->respond_error($e->getMessage(), 2413111223);
        }

        // Check for WP Error
        if (is_wp_error($raw_response)) {
            $raw_response = $raw_response->get_error_message();
            $http_status = 999;
        } else {
            try {
                $http_status = wp_remote_retrieve_response_code($raw_response);
                $raw_response = wp_remote_retrieve_body($raw_response);
            } catch (Exception $e) {
                return $this->respond_error("Error retrieving response code: " . $e->getMessage(), 31224725);
            }
        }

        // check the response status
        if ($http_status !== 200) {
            return $this->respond_error("API request failed with HTTP status " . $http_status .  " - response: " . $raw_response, 221313823);
        }

        // normalize response
        $normalized_response = $this->normalize_call_response($raw_response);

        // on error
        if (!isset($normalized_response["success"]) || $normalized_response["success"] !== true) {
            // check if response is html
            if (strpos($raw_response, "<html") !== false || strpos($raw_response, "html>") !== false) {
                // if it contains "One moment, please", then the request was blocked by cloudflare
                if (strpos($raw_response, "One moment, please") !== false) {
                    return $this->respond_error("Failed to connect to our servers. It’s possible that your request was blocked by our server provider's security system, which may occur if your IP address has been flagged as suspicious. Please try again later. If this error persists, please contact our support team.", 4314181024);
                } else if (strpos($raw_response, "<title>Maintenance</title>") !== false) {
                    return $this->respond_error("Our servers are currently undergoing maintenance. Please try again later.", 401211124);
                } else {
                    return $this->respond_error("There was an error receiving a proper response from our server. Please try again later.", 4414181024);
                }
            }

            // some errors need more attention
            $this->handle_api_errors($normalized_response);

            // respond with error
            return $this->respond_error($normalized_response["message"] ?? "An unknown API error occurred!", $normalized_response["code"] ?? 571124725);
        }

        // update new credits balance
        if (isset($normalized_response["new-credits-balance"]) && is_numeric($normalized_response["new-credits-balance"])) {
            $this->update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE, $normalized_response["new-credits-balance"]);
        }

        // save the response in the recent_endpoint_responses array
        $this->recent_endpoint_responses[$api_call_checksum] = $normalized_response;

        // set transient
        if ($endpoint_lock_duration > 0) {
            set_transient($transient_name, $api_call_checksum, $endpoint_lock_duration);
        }

        return $normalized_response;
    }

    // =========================================================================================== \\

    function normalize_call_response($call_response): array {
        if (empty($call_response)) {
            return array(
                "success" => false,
                "code" => 271823824,
                "message" => "Could not execute API call: empty response."
            );
        }

        // is json -> decode it
        if (ai4seo_is_json($call_response)) {
            $call_response = json_decode($call_response, true);
        }

        if (!is_array($call_response) || empty($call_response)) {
            return array(
                "success" => false,
                "code" => 281823824,
                "message" => "API call did not return a proper array."
            );
        }

        // normalize success and error values
        if (isset($call_response["success"]) && $call_response["success"] === "true") {
            $call_response["success"] = true;
        }

        if (isset($call_response["success"]) && $call_response["success"] === "false") {
            $call_response["success"] = false;
        }

        if (isset($call_response["error"]) && $call_response["error"] === "true") {
            $call_response["success"] = false;
        }

        if (isset($call_response["error"]) && $call_response["error"] === "false") {
            $call_response["success"] = true;
        }

        if (isset($call_response["error"]) && $call_response["error"] === true) {
            $call_response["success"] = false;
        }

        if (!isset($call_response["success"]) || $call_response["success"] !== true) {
            $call_response["code"] = isset($call_response["code"]) ? (int) $call_response["code"] : 391124725;
            $call_response["message"] = isset($call_response["message"]) ? sanitize_text_field($call_response["message"]) : "API call returned an error without a message.";
            $call_response["message"] = "API-Error #{$call_response["code"]}: " . $call_response["message"];

            return array(
                "success" => false,
                "code" => 311823824,
                "message" => $call_response["message"]
            );
        }

        // check if data is set
        if (!isset($call_response["data"])) {
            return array(
                "success" => false,
                "code" => 331823824,
                "message" => "API call did not return data."
            );
        }

        if (empty($call_response["data"])) {
            return array(
                "success" => false,
                "code" => 321823824,
                "message" => "API call returned an empty data array."
            );
        }

        // check if data is an array
        if (ai4seo_is_json($call_response["data"])) {
            $call_response["data"] = json_decode($call_response["data"], true);
        }

        // sanitize data
        $call_response["data"] = $this->deep_sanitize($call_response["data"], 'ai4seo_wp_kses');

        if (empty($call_response["data"])) {
            return array(
                "success" => false,
                "code" => 341823824,
                "message" => "Could not decode or sanitize API call data."
            );
        }

        // check if credits are set
        if (!isset($call_response["credits-consumed"])) {
            return array(
                "success" => false,
                "code" => 361823824,
                "message" => "API call did not return consumed Credits."
            );
        }

        // sanitize credits
        $call_response["credits-consumed"] = (int) $call_response["credits-consumed"];

        // check if new credits balance is set
        if (!isset($call_response["new-credits-balance"])) {
            return array(
                "success" => false,
                "code" => 371823824,
                "message" => "API call did not return new Credits balance."
            );
        }

        // sanitize new credits balance
        $call_response["new-credits-balance"] = (int) $call_response["new-credits-balance"];

        return $call_response;
    }

    // =========================================================================================== \\

    function was_call_successful($response): bool {
        if (is_array($response) && isset($response["success"]) && $response["success"] === true) {
            return true;
        }

        return false;
    }

    // =========================================================================================== \\

    function handle_api_errors($response) {
        if (isset($response["code"]) && is_numeric($response["code"])) {
            # insufficient credits -> discard cache
            if ($response["code"] == 371816823) {
                $this->reset_last_credit_balance_check();
            }
        }
    }

    // =========================================================================================== \\

    /**
     * Set some product related parameters, during the initialization of the class.
     * @param $product string The product name.
     * @param $product_version string The product version.
     * @param $min_credits_balance int The minimum credits balance to use the API.
     * @param $credits_balance_cache_lifetime int The lifetime of the credits balance cache.
     * @param $product_activation_time int the product activation time
     * @return void
     */
    function set_product_parameters(string $product, string $product_version, int $min_credits_balance, int $credits_balance_cache_lifetime, int $product_activation_time): void {
        $this->product = $product;
        $this->product_version = $product_version;
        $this->min_credits_balance = $min_credits_balance;
        $this->credits_balance_cache_lifetime = $credits_balance_cache_lifetime;
        $this->product_activation_time = $product_activation_time;
    }

    // =========================================================================================== \\

    function set_does_user_need_to_accept_tos_toc_and_pp(bool $does_user_need_to_accept_tos_toc_and_pp): void {
        $this->does_user_need_to_accept_tos_toc_and_pp = $does_user_need_to_accept_tos_toc_and_pp;
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
     * @return bool True if free account was successfully created, false otherwise.
     */
    function init_free_account(): bool {
        // build pseudo api username and password first
        $api_username = $this->build_api_username();

        if (!$this->use_this_credentials($api_username, $this->public_get_free_account_api_password)) {
            return false;
        }

        $parameters = array(
            "product_activation_time" => $this->product_activation_time,
            "users_current_time" => time(),
        );

        // retrieve our real credentials
        $response = $this->call("client/get-free-account", $parameters);

        // check response
        if (!ai4seo_robhub_api()->was_call_successful($response) || !isset($response["data"]["api_username"]) || !isset($response["data"]["api_password"])) {
            $this->api_username = "";
            $this->api_password = "";
            return false;
        }

        // try save new credentials
        if (!$this->use_this_credentials($response["data"]["api_username"], $response["data"]["api_password"], true)) {
            $this->api_username = "";
            $this->api_password = "";
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
        elseif (function_exists('get_option')) {
            $site_url = get_option('siteurl');
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
     * Determines an A/B group (a or b) based on api username
     *
     * @return string 'a' or 'b'
     */
    function get_ab_group($api_username = ''): string {
        // got credentials -> get username
        if (!$api_username && $this->has_credentials()) {
            $api_username = $this->get_api_username();
        }

        // otherwise build it
        if (!$api_username) {
            $api_username = $this->build_api_username();
        }

        // Calculate hash and determine group
        $hash = crc32($api_username);
        return ($hash % 2 === 0) ? 'a' : 'b';
    }

    // =========================================================================================== \\

    function is_group_a($api_username = ''):  bool {
        return $this->get_ab_group($api_username) === 'a';
    }

    // =========================================================================================== \\

    function is_group_b($api_username = ''):  bool {
        return $this->get_ab_group($api_username) === 'b';
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
        if (!$this->init_credentials()) {
            return "";
        }

        // Make sure that api username is not empty
        if (!$this->api_username) {
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
        if (!$this->init_credentials()) {
            return "";
        }

        // Make sure that api password is not empty
        if (!$this->api_password) {
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
        global $ai4seo_synced_robhub_client_data;

        // check _ai4seo_last_credit_balance_check option, if it's empty or older than 24 hours, call the robhub api to get the credits balance
        $last_credits_balance_check_timestamp = (int) $this->read_environmental_variable(self::ENVIRONMENTAL_VARIABLE_LAST_CREDIT_BALANCE_CHECK);

        // if the last credit balance check is older than $creditsBalanceCacheLifetime hours,
        // call the robhub api to get the credits balance
        do if ($last_credits_balance_check_timestamp < time() - $this->credits_balance_cache_lifetime) {
            // not synced robhub account yet?
            if (!$ai4seo_synced_robhub_client_data) {
                ai4seo_sync_robhub_account(true);
            }

            // update ENVIRONMENTAL_VARIABLE_LAST_CREDIT_BALANCE_CHECK
            $this->update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_LAST_CREDIT_BALANCE_CHECK, time());
        } while (false);

        return (int) $this->read_environmental_variable(self::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE);
    }

    // =========================================================================================== \\

    /**
     * Function to unset the last credit balance check option.
     */
    function reset_last_credit_balance_check(): void {
        $this->update_environmental_variable(self::ENVIRONMENTAL_VARIABLE_LAST_CREDIT_BALANCE_CHECK, 0);
    }

    // =========================================================================================== \\

    /**
     * Performs an anonymous call to the robhub api to reject the terms of service.
     * @param int $tos_version The version of the terms of service to reject.
     * @return void
     */
    function perform_reject_terms_call(int $tos_version) {
        $this->set_random_credentials_if_not_set();

        $reject_terms_parameter = array(
            "timestamp" => time(),
            "tos_version" => AI4SEO_TOS_VERSION_TIMESTAMP
        );

        $this->call("client/reject-terms", $reject_terms_parameter, "POST");
    }

    // =========================================================================================== \\

    function perform_product_deactivated_call() {
        $this->set_random_credentials_if_not_set();

        // call robhub api endpoint "client/product-deactivated"
        $this->call("client/product-deactivated", array(), "POST");
    }

    // =========================================================================================== \\

    function set_random_credentials_if_not_set(): void {
        if (!$this->init_credentials(false)) {
            $random = $this->generate_random_pseudo_api_username();
            $this->api_username = $this->build_api_username($random);
            $this->api_password = $this->public_client_operations_api_password;
        }
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

        if ($this->environmental_variables !== self::DEFAULT_ENVIRONMENTAL_VARIABLES) {
            return $this->environmental_variables;
        }

        $current_environmental_variables = get_option($this->environmental_variables_option_name);
        $current_environmental_variables = maybe_unserialize($current_environmental_variables);

        // fallback to existing environmental variables
        if (!is_array($current_environmental_variables) || !$current_environmental_variables) {
            return $this->environmental_variables;
        }

        // go through each environmental variable and check if it is valid
        foreach ($this->environmental_variables as $environmental_variable_name => $default_environmental_variable_value) {
            // if this environmental variable is not known, remove it
            if (!isset(self::DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name])) {
                continue;
            }

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
            return "";
        }

        $current_environmental_variables = $this->read_all_environmental_variables();

        // Check if the $environmental_variable_name-parameter exists in environmental variables-array
        if (!isset($current_environmental_variables[$environmental_variable_name])) {
            // check for a default value
            if (isset(self::DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name])) {
                return self::DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name];
            } else {
                error_log("ROBHUB: Unknown environmental variable name: " . $environmental_variable_name . ". #3615181024");
            }
            return "";
        }

        return $current_environmental_variables[$environmental_variable_name];
    }

    // =========================================================================================== \\

    /**
     * Function to update a specific robhub environmental variable
     * @param string $environmental_variable_name The name of the robhub environmental variable
     * @param mixed $new_environmental_variable_value The new value of the robhub environmental variable
     * @return bool True if the robhub environmental variable was updated successfully, false if not
     */
    function update_environmental_variable(string $environmental_variable_name, $new_environmental_variable_value): bool {
        // Make sure that the new value of the environmental variable is valid
        if (!$this->validate_environmental_variable_value($environmental_variable_name, $new_environmental_variable_value)) {
            error_log("ROBHUB: Invalid value for environmental variable '" . $environmental_variable_name . "'. #3715181024");
            return false;
        }

        // sanitize
        $new_environmental_variable_value = $this->deep_sanitize($new_environmental_variable_value);

        // overwrite entry in $current_environmental_variables-array
        $current_environmental_variables = $this->read_all_environmental_variables();

        // workaround -> if we do not have this variable in our currently stored environmental variables, we add the default value
        if (!isset($current_environmental_variables[$environmental_variable_name])) {
            $current_environmental_variables[$environmental_variable_name] = self::DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name];
        }

        // is same as default value? delete it
        if ($new_environmental_variable_value == self::DEFAULT_ENVIRONMENTAL_VARIABLES[$environmental_variable_name]) {
            unset($current_environmental_variables[$environmental_variable_name]);
        } else {
            // no change at all?
            if ($current_environmental_variables[$environmental_variable_name] == $new_environmental_variable_value) {
                return true;
            }

            $current_environmental_variables[$environmental_variable_name] = $new_environmental_variable_value;
        }

        // update the class parameter as well
        $this->environmental_variables = $current_environmental_variables;

        // Save updated environmental variables to database
        return update_option($this->environmental_variables_option_name, $current_environmental_variables, true);
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
            error_log("ROBHUB: Environmental variable '" . $environmental_variable_name . "' does not exist. #41319225");
            return false;
        }

        // delete the entry
        unset($current_environmental_variables[$environmental_variable_name]);

        // update the class parameter as well
        $this->environmental_variables = $current_environmental_variables;

        // Save updated environmental variables to database
        return update_option($this->environmental_variables_option_name, $current_environmental_variables, true);
    }

    // =========================================================================================== \\

    /**
     * Deletes all robhub environmental variables
     * @return bool
     */
    function delete_all_environmental_variables(): bool {
        $this->environmental_variables = self::DEFAULT_ENVIRONMENTAL_VARIABLES;
        return delete_option($this->environmental_variables_option_name);
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
            case self::ENVIRONMENTAL_VARIABLE_LAST_CREDIT_BALANCE_CHECK:
                // contains only of numbers
                return is_numeric($environmental_variable_value) && $environmental_variable_value >= 0;


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
            ai4seo_robhub_api()->update_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_USERNAME, $old_api_username);
        }

        if ($old_api_password) {
            ai4seo_robhub_api()->update_environmental_variable(ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_PASSWORD, $old_api_password);
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