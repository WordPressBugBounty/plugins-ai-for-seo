<?php
/**
 * The RobHub Api Communicator. Is used to get client data or to use its AI tools
 *
 * @package AI_For_SEO
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Communicates with RobHub API services and manages client account state.
 */
class Ai4Seo_RobHubApiCommunicator {
	/**
	 * Whether the communicator has been initialized.
	 *
	 * @var bool
	 */
	public bool $is_initialized = false;

	/**
	 * RobHub API version path segment.
	 *
	 * @var string
	 */
	private string $version = 'v1';

	/**
	 * Base URL for the public RobHub API.
	 *
	 * @var string
	 */
	private string $api_url = 'https://api.robhub.ai';

	/**
	 * Current API username.
	 *
	 * @var string
	 */
	private string $api_username = '';

	/**
	 * Current API password.
	 *
	 * @var string
	 */
	private string $api_password = '';

	/**
	 * Public credential used only to request a free account.
	 *
	 * @var string
	 */
	private string $public_get_free_account_api_password = '_get-free-account-with-some-credits-to-play-with';

	/**
	 * Public credential used for client operations before a client ID exists.
	 *
	 * @var string
	 */
	private string $public_client_operations_api_password = '_this-secret-can-be-used-when-no-client-id-isset';

	/**
	 * Product identifier sent to RobHub.
	 *
	 * @var string
	 */
	private string $product = 'robhub';

	/**
	 * Product version sent to RobHub.
	 *
	 * @var string
	 */
	private string $product_version = '0.0.0';

	/**
	 * Minimum credits required for paid API operations.
	 *
	 * @var int
	 */
	private int $min_credits_balance = 1; // todo: will be replaced by the users settings based on the quality of the ai generations.

	/**
	 * Whether the current user must accept the legal agreements.
	 *
	 * @var bool
	 */
	private bool $does_user_need_to_accept_tos_toc_and_pp = false;

	/**
	 * Whether API calls should use the configured local endpoint.
	 *
	 * @var bool
	 */
	private bool $is_local_api_enabled = false;

	/**
	 * Base URL for local API calls.
	 *
	 * @var string
	 */
	private string $local_api_url = 'http://localhost';

	/**
	 * Product activation timestamp.
	 *
	 * @var int
	 */
	public int $product_activation_time           = 0;
	public const ACCOUNT_SYNC_INTERVAL            = 3600; // 1 hour in seconds
	public const BACKGROUND_ACCOUNT_SYNC_INTERVAL = 86400; // 24 hours in seconds

	/**
	 * Whether the last account-sync timestamp was reset during this request.
	 *
	 * @var bool
	 */
	private bool $has_reset_last_account_sync = false;

	/**
	 * Whether this request has already attempted to reconcile a complete pending rotation.
	 *
	 * @var bool
	 */
	private bool $has_attempted_api_password_rotation_reconciliation = false;

	/**
	 * Whether the communicator is currently calling the public rotation endpoint.
	 *
	 * @var bool
	 */
	private bool $is_reconciling_api_password_rotation = false;

	/**
	 * Whether the communicator is replaying server-side credential recovery.
	 *
	 * @var bool
	 */
	private bool $is_reconciling_api_password_rotation_recovery = false;

	/**
	 * Whether one Control worker call is using explicit, request-local client credentials.
	 *
	 * @var bool
	 */
	private bool $is_isolated_service_client_call = false;

	/**
	 * Closed result of the most recent claim-based rotation reconciliation.
	 *
	 * @var string
	 */
	private string $last_api_password_rotation_reconciliation_outcome = 'not-attempted';

	/**
	 * Maximum number of transport attempts per API call.
	 *
	 * @var int
	 */
	private int $max_api_attempts = 3;

	/**
	 * Endpoints whose remote side effects must fit within one bounded transport attempt.
	 *
	 * @var string[]
	 */
	private const SINGLE_ATTEMPT_ENDPOINTS = array(
		'client/get-free-account',
		'client/send-licence-data',
		self::ROTATE_API_PASSWORD_ENDPOINT,
		self::RECOVER_API_PASSWORD_ROTATION_ENDPOINT,
	);

	/**
	 * Endpoint-specific HTTP timeouts which do not affect normal 60-second API calls.
	 *
	 * @var array<string, int>
	 */
	private const ENDPOINT_HTTP_TIMEOUTS = array(
		'client/send-licence-data' => 8,
	);

	/**
	 * Endpoints permitted to use request-local Control worker credentials.
	 *
	 * @var string[]
	 */
	private const ISOLATED_SERVICE_CLIENT_ENDPOINTS = array(
		'client/send-licence-data',
	);

	/**
	 * Credential endpoints whose response values must never enter durable diagnostics.
	 *
	 * @var string[]
	 */
	private const SENSITIVE_CREDENTIAL_ENDPOINTS = array(
		'client/get-free-account',
		'client/init-purchase',
		self::PREPARE_SUBSCRIPTION_PRICING_ENDPOINT,
		self::PREPARE_API_PASSWORD_ROTATION_ENDPOINT,
		self::ROTATE_API_PASSWORD_ENDPOINT,
		self::RECOVER_API_PASSWORD_ROTATION_ENDPOINT,
	);

	/**
	 * Endpoints whose complete model-attempt budget is coordinated by RobHub.
	 *
	 * The plugin sends one initial HTTP request so transport retries cannot multiply server-side
	 * model attempts. Attachment source recovery is separately allowed only when RobHub returns a
	 * one-time continuation proving that the URL failed before model work began.
	 *
	 * @var string[]
	 */
	private const COORDINATED_GENERATION_ENDPOINTS = array(
		'ai4seo/generate-all-metadata',
		'ai4seo/generate-all-attachment-attributes',
	);

	/**
	 * API error code returned when coordinated generation cannot be admitted.
	 *
	 * @var int
	 */
	private const INSUFFICIENT_CREDITS_ERROR_CODE = 371816823;

	/**
	 * API error code returned when atomic credit settlement fails.
	 *
	 * @var int
	 */
	private const CREDIT_SETTLEMENT_ERROR_CODE = 26060826;

	/**
	 * Billing failures that should halt coordinated generation for the current request.
	 *
	 * @var int[]
	 */
	private const TERMINAL_BILLING_ERROR_CODES = array(
		self::INSUFFICIENT_CREDITS_ERROR_CODE,
		self::CREDIT_SETTLEMENT_ERROR_CODE,
	);

	/**
	 * Endpoint that creates a signed claim for a future credential rotation.
	 *
	 * @var string
	 */
	private const PREPARE_API_PASSWORD_ROTATION_ENDPOINT = 'client/prepare-api-password-rotation';

	/**
	 * Endpoint that creates authenticated attribution for credit-only customers entering pricing.
	 *
	 * @var string
	 */
	private const PREPARE_SUBSCRIPTION_PRICING_ENDPOINT = 'client/prepare-subscription-pricing';

	/**
	 * Endpoint that confirms and applies a previously approved credential rotation.
	 *
	 * @var string
	 */
	private const ROTATE_API_PASSWORD_ENDPOINT = 'client/rotate-api-password';

	/**
	 * Endpoint that replaces a lost local candidate with a server-generated credential.
	 *
	 * @var string
	 */
	private const RECOVER_API_PASSWORD_ROTATION_ENDPOINT = 'client/recover-api-password-rotation';

	/**
	 * Generic conflict which permits trying an older claim for the same candidate generation.
	 *
	 * @var int
	 */
	private const API_PASSWORD_ROTATION_CONFLICT_ERROR_CODE        = 27082706;
	private const API_PASSWORD_ROTATION_RECONCILIATION_NOT_PENDING = 'not-pending';
	private const API_PASSWORD_ROTATION_RECONCILIATION_CONFIRMED   = 'confirmed';
	private const API_PASSWORD_ROTATION_RECONCILIATION_CONFLICT    = 'exact-conflict';
	private const API_PASSWORD_ROTATION_RECONCILIATION_UNAVAILABLE = 'unavailable';

	/**
	 * Version of the durable pending-rotation schema.
	 *
	 * @var int
	 */
	private const API_PASSWORD_ROTATION_STATE_VERSION = 1;

	/**
	 * Only purpose supported by the version-one signed-claim protocol.
	 *
	 * @var string
	 */
	private const API_PASSWORD_ROTATION_REASON = 'first-purchase';

	/**
	 * Maximum accepted signed-claim length.
	 *
	 * @var int
	 */
	private const API_PASSWORD_ROTATION_CLAIM_MAX_LENGTH = 128;

	/**
	 * Minimum remaining claim lifetime required before opening a Stripe checkout.
	 *
	 * @var int
	 */
	private const API_PASSWORD_ROTATION_CHECKOUT_SAFETY_HORIZON = 3600;

	/**
	 * Bounded storage attempts for exact option compare-and-swap transitions.
	 *
	 * @var int
	 */
	private const API_PASSWORD_ROTATION_STORAGE_MAX_ATTEMPTS = 3;

	/**
	 * Maximum older signed claims retained for late Stripe webhook recovery.
	 *
	 * @var int
	 */
	private const API_PASSWORD_ROTATION_MAX_PRIOR_CLAIMS = 8;

	/**
	 * Minimum interval between signed-claim issuances for one pending candidate.
	 *
	 * @var int
	 */
	private const API_PASSWORD_ROTATION_CLAIM_REFRESH_INTERVAL = 518400;

	/**
	 * Durable reconciliation cadence for abandoned and recently returned checkouts.
	 *
	 * @var int
	 */
	private const API_PASSWORD_ROTATION_RECONCILIATION_INITIAL_DELAY = 300;
	private const API_PASSWORD_ROTATION_RECONCILIATION_FAST_DELAY    = 30;
	private const API_PASSWORD_ROTATION_RECONCILIATION_MEDIUM_DELAY  = 120;
	private const API_PASSWORD_ROTATION_RECONCILIATION_SLOW_DELAY    = 21600;

	/**
	 * Retention horizons for an authenticated account with no durable purchase marker.
	 *
	 * Expired checkout claims remain recoverable long enough for delayed Stripe webhooks. A
	 * candidate whose prepare response was never published has no checkout expiry, so it uses a
	 * longer age bound before the same authenticated absence may retire it.
	 *
	 * @var int
	 */
	private const API_PASSWORD_ROTATION_EXPIRED_CLAIM_GRACE_PERIOD = 100800; // 28 hours.
	private const API_PASSWORD_ROTATION_UNPUBLISHED_RETENTION      = 691200; // 8 days.

	/**
	 * Durable retry cadence for a recovery request whose response may have been lost.
	 *
	 * @var int
	 */
	private const API_PASSWORD_ROTATION_RECOVERY_RETRY_DELAY = 30;

	/**
	 * Dedicated non-autoloaded option holding a recoverable password transition.
	 *
	 * @var string
	 */
	public const PENDING_API_PASSWORD_ROTATION_OPTION_NAME = '_ai4seo_pending_api_password_rotation';

	/**
	 * Non-secret, non-autoloaded replay state for server-generated credential recovery.
	 *
	 * @var string
	 */
	public const API_PASSWORD_ROTATION_RECOVERY_INTENT_OPTION_NAME = '_ai4seo_api_password_rotation_recovery_intent';

	/**
	 * API error codes that should not be retried.
	 *
	 * @var int[]
	 */
	private array $non_retriable_error_codes = array(
		self::INSUFFICIENT_CREDITS_ERROR_CODE, // No more credits.
		self::CREDIT_SETTLEMENT_ERROR_CODE, // Credits settlement failed.
		591716925, // could not send email (send-licence-data).
		41228125, // Please log in using the license data provided (get-free-account).
		25164525, // error while downloading file from url / http status code != 200.
		2113111223, // missing or corrupt auth credentials.
		916101025, // invalid credentials: client not found / access denied.
		461723823, // invalid credentials: auth user is missing.
		351816823, // invalid credentials: invalid api password.
		431319725, // invalid credentials: access denied.
		3619101024, // inappropriate content detected.
		218101024, // blocked from using the service.
		3204525, // cloudflare challenge detected.
		311014824, // file not accessible at given URL.
		391014824, // attachment URL content is empty or invalid.
		71214326, // file too large.
		5810211026, // RobHub API runtime configuration error: PHP cURL unavailable.
		5910211026, // RobHub API server-side runtime error.
		571716925, // could not send email (send-licence-data).
		581317325, // payg_stripe_price_id parameter is invalid.
	);

	/**
	 * API error codes that invalidate stored authentication data.
	 *
	 * @var int[]
	 */
	public array $invalidate_auth_data_error_codes = array(
		41228125,  // Please log in using the license data provided (get-free-account).
		916101025, // invalid credentials: client not found / access denied.
		461723823, // invalid credentials: auth user is missing.
		351816823, // invalid credentials: invalid api password.
		431319725, // invalid credentials: access denied.
		218101024, // blocked from using the service.
	);

	/**
	 * API error codes that are not tied to a specific post.
	 *
	 * @var int[]
	 */
	public array $non_post_related_error_codes = array(
		1115424,   // no credits left.
		1215424,   // no credits left.
		self::INSUFFICIENT_CREDITS_ERROR_CODE, // No credits left (server side).
		self::CREDIT_SETTLEMENT_ERROR_CODE, // Credits settlement failed.
		201313823, // endpoint not allowed.
		211313823, // request method not allowed.
		2113111223, // missing or corrupt auth credentials.
		251118426, // could not initialize credentials.
		521561224, // endpoint locked.
		2313111223, // TypeError while making API call.
		2413111223, // Exception while making API call.
		2411301024, // user did not accept terms of service.
		1913111223, // invalid URL.
		4314181024, // request blocked by server provider.
		401211124,  // server maintenance.
		4414181024, // error receiving proper response from server.
		361823824, // api call did not return consumed credits.
		371823824, // api call did not return new credits balance.
		571716925, // could not send email (send-licence-data).
		5810211026, // RobHub API runtime configuration error: PHP cURL unavailable.
		5910211026, // RobHub API server-side runtime error.
	);

	/**
	 * Maximum encoded API payload size in bytes.
	 *
	 * @var int
	 */
	private int $max_api_payload_size_bytes = 2097152; // 2 MB

	/**
	 * Maximum API response size accepted in bytes.
	 *
	 * @var int
	 */
	private int $max_response_bytes = 1572864; // what we accept from the API: 1.5 MB.

	/**
	 * Delay before the second transport attempt in milliseconds.
	 *
	 * @var int
	 */
	private int $second_attempt_delay_ms = 500; // 500 milliseconds

	/**
	 * Delay before the third transport attempt in milliseconds.
	 *
	 * @var int
	 */
	private int $third_attempt_delay_ms = 2000; // 2 second

	/**
	 * Whether detailed API-call diagnostics are enabled.
	 *
	 * @var bool
	 */
	private bool $show_more_detailed_debug_messages = false; // set to true to enable debug logging for api calls.

	/**
	 * Per-endpoint lock durations in seconds.
	 *
	 * @var array<string, int>
	 */
	private array $endpoint_lock_durations = array(
		'ai4seo/generate-all-metadata'               => 1,
		'ai4seo/generate-all-attachment-attributes'  => 1,
		'client/get-free-account'                    => 5,
		'client/accept-terms'                        => 60,
		'client/reject-terms'                        => 60,
		'client/product-deactivated'                 => 60,
		'client/product-updated'                     => 60,
		'client/changed-api-user'                    => 5,
		'client/payg-settings'                       => 5,
		'client/init-purchase'                       => 5,
		self::PREPARE_SUBSCRIPTION_PRICING_ENDPOINT  => 0,
		self::PREPARE_API_PASSWORD_ROTATION_ENDPOINT => 0,
		self::ROTATE_API_PASSWORD_ENDPOINT           => 0,
		self::RECOVER_API_PASSWORD_ROTATION_ENDPOINT => 0,
		'client/send-licence-data'                   => 61,
		'client/feedback'                            => 5,
	);

	/**
	 * Responses already received for endpoints during the current request.
	 *
	 * @var array<string, mixed>
	 */
	private array $recent_endpoint_responses = array();

	// === ENVIRONMENTAL VARIABLES ================================================================================= \\

	/**
	 * WordPress option name used for communicator environmental variables.
	 *
	 * @var string
	 */
	public string $environmental_variables_option_name = 'robhub_environmental_variables';

	/**
	 * Bounded attempts for every shared environmental-option mutation.
	 *
	 * @var int
	 */
	private const ENVIRONMENTAL_VARIABLE_STORAGE_MAX_ATTEMPTS = 3;

	public const ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA    = 'auth_data';
	public const ENVIRONMENTAL_VARIABLE_API_USERNAME                = 'api_username';
	public const ENVIRONMENTAL_VARIABLE_API_PASSWORD                = 'api_password';
	public const ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE             = 'credits_balance';
	public const ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP = 'next_free_credits';
	public const ENVIRONMENTAL_VARIABLE_SUBSCRIPTION                = 'subscription';
	public const ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC           = 'last_account_sync';
	public const ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED           = 'is_account_synced';
	public const ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED              = 'is_auth_locked';
	public const ENVIRONMENTAL_VARIABLE_GROUP                       = 'group';

	public const DEFAULT_ENVIRONMENTAL_VARIABLES = array(
		self::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA => array(),
		self::ENVIRONMENTAL_VARIABLE_API_USERNAME      => '',
		self::ENVIRONMENTAL_VARIABLE_API_PASSWORD      => '',
		self::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE   => 0,
		self::ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP => 0,
		self::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION      => array(),
		self::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC => 0,
		self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED => false,
		self::ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED    => false,
		self::ENVIRONMENTAL_VARIABLE_GROUP             => 'x',
	);
	/**
	 * In-memory environmental-variable values.
	 *
	 * @var array<string, mixed>
	 */
	private array $environmental_variables = self::DEFAULT_ENVIRONMENTAL_VARIABLES;

	// all allowed endpoints / whitelist.
	/**
	 * Endpoint paths the communicator may call.
	 *
	 * @var string[]
	 */
	private array $allowed_endpoints = array(
		'ai4seo/generate-all-metadata',
		'ai4seo/generate-all-attachment-attributes',

		'client/get-free-account',
		'client/sync',
		'client/accept-terms',
		'client/reject-terms',
		'client/product-deactivated',
		'client/product-updated',
		'client/changed-api-user',
		'client/payg-settings',
		'client/init-purchase',
		self::PREPARE_SUBSCRIPTION_PRICING_ENDPOINT,
		self::PREPARE_API_PASSWORD_ROTATION_ENDPOINT,
		self::ROTATE_API_PASSWORD_ENDPOINT,
		self::RECOVER_API_PASSWORD_ROTATION_ENDPOINT,
		'client/send-licence-data',
		'client/feedback',
	);

	/**
	 * Endpoint paths that perform content generation.
	 *
	 * Later extensions do not inherit the built-in one-call cap.
	 *
	 * @var string[]
	 */
	private array $generation_endpoints = self::COORDINATED_GENERATION_ENDPOINTS;

	/**
	 * Endpoint paths that do not consume credits.
	 *
	 * @var string[]
	 */
	private array $free_endpoints = array(
		'client/get-free-account',
		'client/sync',
		'client/accept-terms',
		'client/reject-terms',
		'client/product-deactivated',
		'client/product-updated',
		'client/changed-api-user',
		'client/payg-settings',
		'client/init-purchase',
		self::PREPARE_SUBSCRIPTION_PRICING_ENDPOINT,
		self::PREPARE_API_PASSWORD_ROTATION_ENDPOINT,
		self::ROTATE_API_PASSWORD_ENDPOINT,
		self::RECOVER_API_PASSWORD_ROTATION_ENDPOINT,
		'client/send-licence-data',
		'client/feedback',
	);

	/**
	 * Endpoint paths allowed before legal agreements are accepted.
	 *
	 * @var string[]
	 */
	private array $no_need_to_accept_tos_endpoints = array(
		'client/reject-terms',
		'client/product-deactivated',
		self::ROTATE_API_PASSWORD_ENDPOINT,
		self::RECOVER_API_PASSWORD_ROTATION_ENDPOINT,
	);

	/**
	 * Payload paths whose values may retain safe HTML.
	 *
	 * @var string[]
	 */
	private array $html_value_paths = array();


	// ___________________________________________________________________________________________ \\
	// === INIT ================================================================================== \\
	// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

	/**
	 * Initialize the communicator.
	 */
	public function __construct() {
	}


	/**
	 * Set some product related parameters, during the initialization of the class.
	 *
	 * @param string $product The product name.
	 * @param string $product_version The product version.
	 * @param int    $product_activation_time the product activation time.
	 * @return void
	 */
	public function set_product_parameters( string $product, string $product_version, int $product_activation_time = 0 ): void {
		if ( ! $product_activation_time ) {
			$product_activation_time = time();
		}

		$this->product                 = $product;
		$this->product_version         = $product_version;
		$this->product_activation_time = $product_activation_time;
	}


	/**
	 * Set whether API calls require acceptance of the legal agreements.
	 *
	 * @param bool $does_user_need_to_accept_tos_toc_and_pp Whether acceptance is required.
	 * @return void
	 */
	public function set_does_user_need_to_accept_tos_toc_and_pp( bool $does_user_need_to_accept_tos_toc_and_pp ): void {
		$this->does_user_need_to_accept_tos_toc_and_pp = $does_user_need_to_accept_tos_toc_and_pp;
	}


	/**
	 * Extend the non-retriable API error code list.
	 *
	 * @param array $error_codes Error codes to append.
	 * @return void
	 */
	public function extend_non_retriable_error_codes( array $error_codes ): void {
		$this->non_retriable_error_codes = $this->merge_integer_list( $this->non_retriable_error_codes, $error_codes );
	}


	/**
	 * Extend the non-post-related API error code list.
	 *
	 * @param array $error_codes Error codes to append.
	 * @return void
	 */
	public function extend_non_post_related_error_codes( array $error_codes ): void {
		$this->non_post_related_error_codes = $this->merge_integer_list( $this->non_post_related_error_codes, $error_codes );
	}


	/**
	 * Extend endpoint lock durations.
	 *
	 * @param array $endpoint_lock_durations Endpoint lock durations keyed by endpoint.
	 * @return void
	 */
	public function extend_endpoint_lock_durations( array $endpoint_lock_durations ): void {
		foreach ( $endpoint_lock_durations as $endpoint => $duration ) {
			$endpoint = $this->normalize_endpoint_identifier( (string) $endpoint );

			if ( ! $endpoint ) {
				continue;
			}

			$this->endpoint_lock_durations[ $endpoint ] = max( 0, (int) $duration );
		}
	}


	/**
	 * Extend the allowed endpoint list.
	 *
	 * @param array $endpoints Endpoints to append.
	 * @return void
	 */
	public function extend_allowed_endpoints( array $endpoints ): void {
		$this->allowed_endpoints = $this->merge_endpoint_list( $this->allowed_endpoints, $endpoints );
	}


	/**
	 * Extend the generation endpoint list.
	 *
	 * @param array $endpoints Endpoints to append.
	 * @return void
	 */
	public function extend_generation_endpoints( array $endpoints ): void {
		$this->generation_endpoints = $this->merge_endpoint_list( $this->generation_endpoints, $endpoints );
	}


	/**
	 * Extend the free endpoint list.
	 *
	 * @param array $endpoints Endpoints to append.
	 * @return void
	 */
	public function extend_free_endpoints( array $endpoints ): void {
		$this->free_endpoints = $this->merge_endpoint_list( $this->free_endpoints, $endpoints );
	}


	/**
	 * Extend the endpoints that do not require accepted terms.
	 *
	 * @param array $endpoints Endpoints to append.
	 * @return void
	 */
	public function extend_no_need_to_accept_tos_endpoints( array $endpoints ): void {
		$this->no_need_to_accept_tos_endpoints = $this->merge_endpoint_list( $this->no_need_to_accept_tos_endpoints, $endpoints );
	}


	/**
	 * Extend payload paths that should preserve safe HTML fragments during request sanitization.
	 *
	 * @param array $paths Payload paths. Use * as a wildcard path segment.
	 * @return void
	 */
	public function extend_html_value_paths( array $paths ): void {
		foreach ( $paths as $path ) {
			if ( ! is_scalar( $path ) ) {
				continue;
			}

			$path = $this->normalize_payload_path( (string) $path );

			if ( ! $path || in_array( $path, $this->html_value_paths, true ) ) {
				continue;
			}

			$this->html_value_paths[] = $path;
		}
	}


	// ___________________________________________________________________________________________ \\
	// === CALL ================================================================================== \\
	// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

	/**
	 * Call one allowlisted worker endpoint with isolated, request-local client credentials.
	 *
	 * This path never reads or mutates the Control site's saved authentication, rotation, or
	 * auth-lock state. Runtime credentials and response memoization are restored in a finally block.
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $parameters Request parameters.
	 * @param string $api_username Exact target-client username.
	 * @param string $api_password Exact target-client password.
	 * @return array|mixed|string Normalized API response.
	 */
	public function call_with_isolated_client_credentials( string $endpoint, array $parameters, string $api_username, string $api_password ) {
		$api_username = sanitize_key( $api_username );
		$api_password = sanitize_key( $api_password );

		// Isolated calls are deliberately non-recursive and limited to the worker's closed endpoint set.
		if ( $this->is_isolated_service_client_call
			|| ! in_array( $endpoint, self::ISOLATED_SERVICE_CLIENT_ENDPOINTS, true )
			|| ! $this->is_non_empty_api_username_valid( $api_username )
			|| ! $this->is_non_empty_api_password_valid( $api_password ) ) {
			return $this->respond_error( 'Invalid isolated service-client request.', 27082801 );
		}

		$original_api_username                 = $this->api_username;
		$original_api_password                 = $this->api_password;
		$original_recent_endpoint_responses    = $this->recent_endpoint_responses;
		$this->api_username                    = $api_username;
		$this->api_password                    = $api_password;
		$this->recent_endpoint_responses       = array();
		$this->is_isolated_service_client_call = true;
		try {
			return $this->call( $endpoint, $parameters, 'POST', false );
		} finally {
			$this->is_isolated_service_client_call = false;
			$this->api_username                    = $original_api_username;
			$this->api_password                    = $original_api_password;
			$this->recent_endpoint_responses       = $original_recent_endpoint_responses;
		}
	}


	/**
	 * Function to call the API.
	 *
	 * Retries up to two times if a failure occurs and the interpreted error code
	 * is not listed in the global non-retriable codes array. Coordinated generation
	 * endpoints and free-account bootstrap are called once because repeating either
	 * can multiply irreversible server-side work.
	 *
	 * @param string $endpoint        The endpoint to check.
	 * @param array  $parameters      Additional parameters to send to the API.
	 * @param string $request_method  The request method to use. Can be GET, POST, PUT or DELETE.
	 * @param bool   $fallback_to_public_client_operation_credentials Whether to use the public client operations credentials (for endpoints that can be called without a client-specific account, e.g. get-free-account).
	 * @return array|mixed|string     The response from the API.
	 */
	public function call( string $endpoint, array $parameters = array(), string $request_method = 'POST', bool $fallback_to_public_client_operation_credentials = false ) {
		// A recovery request may already have committed while its HTTP response was lost. Replay that
		// durable intent before any ordinary client-authenticated request can lock or reuse the old pair.
		if ( ! $this->is_isolated_service_client_call && ! $fallback_to_public_client_operation_credentials && self::RECOVER_API_PASSWORD_ROTATION_ENDPOINT !== $endpoint
			&& 'client/changed-api-user' !== $endpoint
			&& ! $this->is_reconciling_api_password_rotation_recovery
			&& $this->has_api_password_rotation_recovery_intent() ) {
			$recovery_reconciled = $this->reconcile_api_password_rotation_recovery_intent();
			$can_resume_request  = $recovery_reconciled
				&& ! $this->has_api_password_rotation_recovery_intent()
				&& ! $this->is_auth_data_locked()
				&& $this->check_credentials();

			if ( ! $can_resume_request ) {
				return $this->respond_error(
					'Credential recovery is awaiting secure confirmation. Please check your licence email before reconnecting.',
					27082721
				);
			}
		}

		// Recover a server-confirmed credential transition before selecting Basic authentication
		// for any ordinary client-specific request. The public rotation call itself bypasses this
		// branch to prevent recursion.
		if ( ! $this->is_isolated_service_client_call && ! $fallback_to_public_client_operation_credentials && self::ROTATE_API_PASSWORD_ENDPOINT !== $endpoint && ! $this->is_reconciling_api_password_rotation && $this->should_automatically_reconcile_api_password_rotation() ) {
			$this->reconcile_pending_api_password_rotation();
			// Rotation intentionally returns the same conflict for a missing/unpaid checkout and a
			// genuine marker mismatch. Before any ordinary operation continues, authenticate sync's
			// non-secret state so only `none` may resume and `required|completed|conflict` stay closed.
			if ( self::API_PASSWORD_ROTATION_RECONCILIATION_CONFLICT === $this->last_api_password_rotation_reconciliation_outcome && ! in_array( $endpoint, array( 'client/sync', 'client/changed-api-user' ), true ) ) {
				$rotation_state_response = $this->sync_account( 'rotation-conflict-verification' );
				if ( ! $this->was_call_successful( $rotation_state_response ) ) {
					return $rotation_state_response;
				}
			}
		}

		// Malformed or temporarily unreadable secret state owns the transition until an
		// authenticated sync can classify server truth. Ordinary calls must not use or erase it.
		if ( ! $this->is_isolated_service_client_call && ! $fallback_to_public_client_operation_credentials && ! in_array( $endpoint, array( 'client/sync', 'client/changed-api-user' ), true ) ) {
			$pending_inspection = $this->inspect_pending_api_password_rotation();
			if ( 'repairable' === $pending_inspection['state'] ) {
				$this->read_pending_api_password_rotation();
				$pending_inspection = $this->inspect_pending_api_password_rotation();
			}

			if ( in_array( $pending_inspection['state'], array( 'invalid', 'unavailable', 'repairable' ), true ) ) {
				return $this->respond_error( 'API password rotation state requires authenticated recovery.', 27082706 );
			}
		}

		if ( ! $this->is_isolated_service_client_call && $this->is_auth_data_locked() && ! $fallback_to_public_client_operation_credentials ) {
			return $this->respond_error( 'Authentication data is locked due to previous errors. Please update your API credentials to unlock.', 581715426 );
		}

		$api_call_checksum = $this->prepare_call( $endpoint, $parameters, $request_method, $fallback_to_public_client_operation_credentials );

		// on error.
		if ( ! is_numeric( $api_call_checksum ) ) {
			return $api_call_checksum;
		}

		// Build arguments once; this shared builder also fails closed when credentials are absent.
		$api_arguments = $this->build_api_arguments( $parameters, $request_method, $endpoint, $fallback_to_public_client_operation_credentials );

		// on error.
		if ( is_array( $api_arguments ) && isset( $api_arguments['success'] ) && false === $api_arguments['success'] ) {
			return $api_arguments;
		}

		if ( isset( self::ENDPOINT_HTTP_TIMEOUTS[ $endpoint ] ) ) {
			$api_arguments['timeout'] = self::ENDPOINT_HTTP_TIMEOUTS[ $endpoint ];
		}

		// build URL (without query string).
		$api_url = $this->build_api_url( $endpoint );

		// on error.
		if ( is_array( $api_url ) && isset( $api_url['success'] ) && false === $api_url['success'] ) {
			return $api_url;
		}

		// Keep irreversible server-side work within one transport request.
		$attempt = 0;

		$max_api_attempts_for_endpoint = $this->max_api_attempts;

		if ( in_array( $endpoint, self::SINGLE_ATTEMPT_ENDPOINTS, true )
			|| in_array( $endpoint, self::COORDINATED_GENERATION_ENDPOINTS, true ) ) {
			$max_api_attempts_for_endpoint = 1;
		}

		$normalized_response = null;

		while ( $attempt < $max_api_attempts_for_endpoint ) {
			++$attempt;

			try {
				$raw_response = wp_safe_remote_request( $api_url, $api_arguments );

				// check and normalize response.
				$raw_response        = $this->check_raw_response( $raw_response, $api_url, $endpoint );
				$normalized_response = $this->normalize_response( $raw_response, $endpoint );

			} catch ( TypeError $e ) {
				if ( $this->is_sensitive_credential_endpoint( $endpoint ) ) {
					unset( $e );
					ai4seo_debug_message( 834181462, 'TypeError while making a secure credential API call to endpoint ' . $endpoint . '.', true );
					$normalized_response = $this->respond_error( 'Secure credential operation failed locally.', 2313111223 );
				} else {
					ai4seo_debug_message( 834181462, 'TypeError while making API call to ' . $api_url . ': ' . $e->getMessage(), true );
					$normalized_response = $this->respond_error( 'TypeError while making API call: ' . $e->getMessage(), 2313111223 );
				}
			} catch ( Exception $e ) {
				if ( $this->is_sensitive_credential_endpoint( $endpoint ) ) {
					unset( $e );
					ai4seo_debug_message( 675323313, 'Exception while making a secure credential API call to endpoint ' . $endpoint . '.', true );
					$normalized_response = $this->respond_error( 'Secure credential operation failed locally.', 2413111223 );
				} else {
					ai4seo_debug_message( 675323313, 'Exception while making API call to ' . $api_url . ': ' . $e->getMessage(), true );
					$normalized_response = $this->respond_error( 'Exception while making API call: ' . $e->getMessage(), 2413111223 );
				}
			}

			// success.
			if ( isset( $normalized_response['success'] ) && true === $normalized_response['success'] ) {
				if ( $this->show_more_detailed_debug_messages ) {
					// Preserve response-shape diagnostics without recording API payload values.
					$response_data_keys = isset( $normalized_response['data'] ) && is_array( $normalized_response['data'] )
						? ai4seo_get_debug_array_key_summary( $normalized_response['data'] )
						: 'none';
					ai4seo_debug_message(
						142668668,
						'API call to ' . $api_url . ' was successful on attempt #' . $attempt .
						'. Response keys: ' . ai4seo_get_debug_array_key_summary( $normalized_response ) .
						'. Data keys: ' . $response_data_keys . '.'
					);
				}

				// save the response in the recent_endpoint_responses array.
				$this->recent_endpoint_responses[ $api_call_checksum ] = $normalized_response;

				// update new credits balance.
				if ( isset( $normalized_response['new-credits-balance'] ) && is_numeric( $normalized_response['new-credits-balance'] ) ) {
					$this->update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE, $normalized_response['new-credits-balance'] );
				}

				// The protected account form verifies replacement credentials through changed-api-user.
				// Only that authenticated success may retire a confirmed check-email recovery notice.
				if ( ! $this->is_isolated_service_client_call && 'client/changed-api-user' === $endpoint ) {
					$recovery_inspection = $this->inspect_api_password_rotation_recovery_intent();

					if ( 'missing' !== $recovery_inspection['state'] && ! $this->delete_api_password_rotation_recovery_intent( $recovery_inspection ) ) {
						return $this->respond_error( 'Could not finalize credential recovery locally.', 27082723 );
					}

					if ( ! $this->finalize_pending_rotation_after_authenticated_reconnect() ) {
						return $this->respond_error( 'Could not finalize the prior credential transition locally.', 27082724 );
					}
				}

				// Retire recovery state only after the replacement pair has authenticated an ordinary request.
				if ( ! $this->is_isolated_service_client_call && ! $fallback_to_public_client_operation_credentials && ! $this->maybe_finalize_api_password_rotation_after_authenticated_success() ) {
					ai4seo_debug_message( 613008261, 'Could not remove verified API-password rotation recovery state.', true );
				}

				return $normalized_response;
			}

			// failure: decide whether to retry.
			$this_error_code = $normalized_response['code'] ?? null;

			// During a pending transition any client-auth failure can describe the old generation
			// after the server activated the replacement. Preserve both local generations so the
			// public reconciliation endpoint can finish recovery next time.
			$is_auth_lock_error_code     = null !== $this_error_code
				&& $this->is_auth_lock_error_code( $this_error_code );
			$pending_rotation_inspection = $is_auth_lock_error_code ? $this->inspect_pending_api_password_rotation() : array(
				'state' => 'missing',
				'value' => array(),
			);
			if ( 'repairable' === ( $pending_rotation_inspection['state'] ?? '' ) ) {
				$this->read_pending_api_password_rotation();
				$pending_rotation_inspection = $this->inspect_pending_api_password_rotation();
			}

			$pending_rotation                    = 'valid' === ( $pending_rotation_inspection['state'] ?? '' ) ? $pending_rotation_inspection['value'] : array();
			$is_unresolved_pending_rotation      = in_array( $pending_rotation_inspection['state'] ?? '', array( 'invalid', 'unavailable', 'repairable' ), true );
			$is_pending_rotation_auth_transition = $pending_rotation && $this->does_pending_api_password_rotation_match_runtime_credentials( $pending_rotation );
			$is_invalidate_auth_data_error       = $is_auth_lock_error_code && ! $this->is_isolated_service_client_call && ! $this->is_reconciling_api_password_rotation_recovery && ! $is_unresolved_pending_rotation && ! $is_pending_rotation_auth_transition && $this->do_runtime_credentials_match_fresh_persisted_credentials();

			if ( $is_pending_rotation_auth_transition ) {
				// The rotation request may have committed server-side even when its response was lost.
				// Make the replay immediately due so the next ordinary operation repairs the local pair.
				$this->accelerate_matching_pending_api_password_rotation_reconciliation( $pending_rotation );
			}

			// Auth-locking errors must never retry, even if a future code list changes.
			$is_non_retriable  = $is_auth_lock_error_code || ( null !== $this_error_code && in_array( $this_error_code, $this->non_retriable_error_codes, true ) );
			$has_more_attempts = ( $attempt < $max_api_attempts_for_endpoint );

			ai4seo_debug_message(
				2117126,
				'API call to ' . $api_url .
				' failed on attempt #' . $attempt .
				' with error: ' . ( $normalized_response['message'] ?? 'Unknown error' ) .
				' (Code: ' . ( null !== $this_error_code ? $this_error_code : 'n/a' ) . ').' .
				' Non-retriable=' . ( $is_non_retriable ? 'yes' : 'no' ) .
				', WillRetry=' . ( $has_more_attempts && ! $is_non_retriable ? 'yes' : 'no' ) .
				', InvalidateAuth=' . ( $is_invalidate_auth_data_error ? 'yes' : 'no' ),
				true
			);

			// These auth errors mean the saved credentials should not auto-bootstrap a fresh free account.
			// Instead, we lock further RobHub calls until the user manually enters valid credentials again.
			if ( $is_invalidate_auth_data_error ) {
				$this->invalidate_and_lock_auth_data();
			}

			if ( ! $has_more_attempts || $is_non_retriable ) {
				// stop retry loop.
				break;
			}

			// small exponential backoff between retries to reduce burst failures
			// $this->second_attempt_delay_ms, then ~$this->third_attempt_delay_ms.
			if ( 1 === $attempt ) {
				usleep( $this->second_attempt_delay_ms * 1000 );
			} elseif ( 2 === $attempt ) {
				usleep( $this->third_attempt_delay_ms * 1000 );
			}

			// reset for next attempt.
			$raw_response        = null;
			$normalized_response = null;
		}

		// final failure logging.
		$final_code    = $normalized_response['code'] ?? 'n/a';
		$final_message = $normalized_response['message'] ?? 'Unknown error';
		ai4seo_debug_message( 255887492, 'API call to ' . $api_url . ' failed after ' . $attempt . ' attempts. Final error: ' . $final_message . ' (Code: ' . $final_code . ')', true );

		// some errors need more attention.
		$this->try_handle_special_api_errors( $normalized_response );

		return $normalized_response;
	}


	/**
	 * Validate an API call and acquire its endpoint lock.
	 *
	 * @param string $endpoint Endpoint path.
	 * @param array  $parameters Request parameters.
	 * @param string $request_method HTTP request method.
	 * @param bool   $fallback_to_public_client_operation_credentials Whether public client credentials may be used.
	 * @return int|array Request checksum on success or an error response.
	 */
	public function prepare_call( $endpoint, $parameters, $request_method, $fallback_to_public_client_operation_credentials = false ) {
		// Enforce accepted legal terms except for endpoints explicitly normalized into the bypass list.
		if (
			$this->does_user_need_to_accept_tos_toc_and_pp
			&& ! in_array( $endpoint, $this->no_need_to_accept_tos_endpoints, true )
		) {
			ai4seo_debug_message( 385474374, 'User did not accept Terms of Service, Terms of Conditions and Privacy Policy. Endpoint: ' . $endpoint );
			return $this->respond_error( 'Terms of Service have to be accepted first.', 2411301024 );
		}

		// check if we already have a response for this endpoint and parameters.
		$api_call_checksum          = $this->get_api_call_checksum( $endpoint, $parameters, $request_method );
		$api_call_endpoint_checksum = $this->get_api_call_endpoint_checksum( $endpoint );
		$transient_name             = 'robhub_api_lock_' . $api_call_endpoint_checksum;

		if ( isset( $this->recent_endpoint_responses[ $api_call_checksum ] ) ) {
			if ( $this->show_more_detailed_debug_messages ) {
				// Identify the cached request shape without recording potentially private parameter values.
				ai4seo_debug_message(
					302187848,
					'Returning cached response for endpoint: ' . $endpoint .
					'. Parameter keys: ' . ai4seo_get_debug_array_key_summary( $parameters ) . '.'
				);
			}
			return $this->recent_endpoint_responses[ $api_call_checksum ];
		}

		// check if this endpoint/parameter/method combination is locked by an active transient.
		$endpoint_lock_duration = $this->endpoint_lock_durations[ $endpoint ] ?? 0;

		if ( $endpoint_lock_duration > 0 ) {
			$last_api_call_checksum = get_transient( $transient_name );

			if ( $last_api_call_checksum === $api_call_checksum ) {
				ai4seo_debug_message( 116644117, 'Endpoint ' . $endpoint . ' is locked for ' . $endpoint_lock_duration . ' seconds. Last API call checksum: ' . $last_api_call_checksum );
				return $this->respond_error( 'This endpoint is still locked for ' . $endpoint_lock_duration . ' seconds.', 521561224 );
			}
		}

		// check if endpoint is allowed.
		if ( ! $this->is_endpoint_allowed( $endpoint ) ) {
			ai4seo_debug_message( 919071686, 'Endpoint ' . $endpoint . ' is not allowed. Allowed endpoints: ' . implode( ', ', $this->allowed_endpoints ), true );
			return $this->respond_error( 'Endpoint ' . $endpoint . ' is not allowed.', 201313823 );
		}

		// Reject methods outside the fixed uppercase transport allowlist before preparing the request.
		$request_method = sanitize_text_field( $request_method );

		if ( ! in_array( $request_method, array( 'GET', 'POST', 'PUT', 'DELETE' ), true ) ) {
			ai4seo_debug_message( 817248407, 'Request method ' . $request_method . ' is not allowed for endpoint ' . $endpoint . '. Allowed methods: GET, POST, PUT, DELETE.', true );
			return $this->respond_error( 'Request method ' . $request_method . ' is not allowed.', 211313823 );
		}

		// Public client operation fallbacks may continue without stored credentials or an unlocked auth state.
		// In that case build_api_arguments() swaps in the public credentials later in the request pipeline.
		if ( ! $fallback_to_public_client_operation_credentials && ! $this->is_isolated_service_client_call && ! $this->check_credentials() && 'client/get-free-account' !== $endpoint ) {
			$api_response = $this->init_free_account();

			if ( ! $this->was_call_successful( $api_response ) ) {
				$api_response_error_message = $api_response['message'] ?? esc_html__( 'Please try to reconnect account', 'ai-for-seo' );
				return $this->respond_error( "Could not initialize credentials: $api_response_error_message", 251118426 );
			}
		}

		if ( $this->is_isolated_service_client_call && ( ! $this->is_non_empty_api_username_valid( $this->api_username ) || ! $this->is_non_empty_api_password_valid( $this->api_password ) ) ) {
			return $this->respond_error( 'Isolated service-client credentials are unavailable.', 27082802 );
		}

		// Require a positive credit balance only for endpoints outside the normalized free-operation list.
		if ( ! in_array( $endpoint, $this->free_endpoints, true ) ) {
			$credits_balance = $this->get_credits_balance();

			if ( $credits_balance < 1 ) {
				ai4seo_debug_message( 746394278, 'No credits left for endpoint ' . $endpoint . '. Current balance: ' . $credits_balance, true );
				return $this->respond_error( 'No Credits left. Please get more Credits.', 1115424 );
			}

			// if we have the approximate cost parameter, we use this here for comparison.
			if ( isset( $parameters['approximate_cost'] ) && is_numeric( $parameters['approximate_cost'] ) ) {
				$approximate_cost = (int) $parameters['approximate_cost'];

				if ( $credits_balance < $approximate_cost ) {
					ai4seo_debug_message( 442448289, 'Not enough credits to call endpoint ' . $endpoint . '. Required: ' . $approximate_cost . ', available: ' . $credits_balance, true );
					return $this->respond_error( 'Not enough Credits left. Please get more Credits.', 1215424 );
				}
			}
		}

		// set transient.
		if ( $endpoint_lock_duration > 0 ) {
			set_transient( $transient_name, $api_call_checksum, $endpoint_lock_duration );
		}

		return $api_call_checksum;
	}


	/**
	 * Build and validate the API URL for an endpoint.
	 *
	 * @param string $endpoint Endpoint path.
	 * @return string|array API URL or an error response.
	 */
	public function build_api_url( $endpoint ) {
		// do we use local api?
		if ( $this->are_we_using_local_api() ) {
			$this->api_url = $this->local_api_url;
		}

		// build URL (without query string).
		$api_url = $this->api_url . '/' . $this->version . '/' . $endpoint;
		$api_url = esc_url_raw( $api_url );
		$api_url = filter_var( $api_url, FILTER_VALIDATE_URL );

		if ( ! $api_url ) {
			ai4seo_debug_message( 344171208, 'Invalid URL for endpoint ' . $endpoint . ': ' . $api_url, true );
			return $this->respond_error( 'Invalid URL', 1913111223 );
		}

		return $api_url;
	}


	/**
	 * Build WordPress HTTP API arguments for a RobHub request.
	 *
	 * @param array  $parameters Request parameters.
	 * @param string $request_method HTTP request method.
	 * @param string $endpoint Endpoint path.
	 * @param bool   $fallback_to_public_client_operation_credentials Whether public client credentials may be used.
	 * @return array HTTP arguments or an error response.
	 */
	public function build_api_arguments( $parameters, $request_method, $endpoint, $fallback_to_public_client_operation_credentials = false ) {
		// prepare headers.
		$api_username = $this->api_username;
		$api_password = $this->api_password;

		if ( $fallback_to_public_client_operation_credentials ) {
			if ( ( ! $api_username || ! $api_password ) && $this->check_credentials() ) {
				$api_username = $this->api_username;
				$api_password = $this->api_password;
			}

			if ( ! $api_username || ! $api_password ) {
				$api_username = $this->generate_random_pseudo_api_username();
				$api_password = $this->public_client_operations_api_password;
			}
		}

		if ( ! $api_username || ! $api_password ) {
			return $this->respond_error( 'API credentials are missing. Please connect your account first.', 172125426 );
		}

		// prepare headers.
		$headers = array(
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HTTP Basic authentication requires base64 encoding.
			'Authorization' => 'Basic ' . base64_encode( $api_username . ':' . $api_password ),
			'Content-Type'  => 'application/json; charset=utf-8',
		);

		// Attach prompt controls only to generation endpoints so other API payload contracts remain unchanged.
		if ( in_array( $endpoint, $this->generation_endpoints, true ) ) {
			// Send staged prompt controls directly; the RobHub API still accepts old booleans from older plugin versions.
			$generation_settings = array();

			foreach ( array_keys( AI4SEO_PROMPT_SLIDER_SETTING_STAGE_COUNTS ) as $this_prompt_slider_setting_name ) {
				// Length controls transmit the entitlement-aware stage while preserving their stored paid selection locally.
				if ( ai4seo_is_generation_length_slider_setting( $this_prompt_slider_setting_name ) ) {
					$generation_settings[ $this_prompt_slider_setting_name ] = ai4seo_get_effective_generation_length_stage( $this_prompt_slider_setting_name );
					continue;
				}

				$generation_settings[ $this_prompt_slider_setting_name ] = (string) ai4seo_get_setting( $this_prompt_slider_setting_name );
			}

			// Celebrity recognition remains a separate plan-gated setting, not part of the prompt slider map.
			$generation_settings['enable_enhanced_celebrity_recognition'] = (bool) ai4seo_get_setting( AI4SEO_SETTING_ENABLE_ENHANCED_CELEBRITY_RECOGNITION );

			$parameters['generation_settings'] = $generation_settings;
		}

		// add product parameter.
		$parameters['product']         = $this->product;
		$parameters['product_version'] = $this->product_version;
		$parameters['credits_balance'] = $this->get_credits_balance();

		// add website context.
		$website_url = get_bloginfo( 'url' );

		if ( $website_url ) {
			$parameters['website_url'] = $website_url;
		}

		// sanitize and encode parameters.
		$parameters = $this->deep_sanitize_for_endpoint( $parameters, $endpoint );
		$parameters = $this->deep_sanitize_for_endpoint( $parameters, $endpoint, 'html_entity_decode' ); // necessary?

		$api_arguments = $this->compress_api_call_parameters( $parameters, $headers );

		if ( ! $api_arguments || ! is_array( $api_arguments ) ) {
			return $this->respond_error( 'Request payload too large. Please reduce input size.', 3811211 );
		}

		$api_arguments += array(
			'method'              => $request_method,
			'timeout'             => 60,
			'limit_response_size' => $this->max_response_bytes,
		);

		if ( $this->show_more_detailed_debug_messages ) {
			// Log transport shape only because bodies and header values can contain content or credentials.
			$api_body_byte_count = isset( $api_arguments['body'] ) && is_string( $api_arguments['body'] )
				? strlen( $api_arguments['body'] )
				: 0;
			$api_header_keys     = isset( $api_arguments['headers'] ) && is_array( $api_arguments['headers'] )
				? ai4seo_get_debug_array_key_summary( $api_arguments['headers'] )
				: 'none';
			ai4seo_debug_message(
				923566643,
				'API arguments for endpoint ' . sanitize_text_field( $endpoint ) . ': method=' . sanitize_key( $request_method ) .
				', body bytes=' . $api_body_byte_count . ', header keys=' . $api_header_keys . '.'
			);
		}

		return $api_arguments;
	}


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
	public function compress_api_call_parameters( array $parameters, array $headers = array() ) {
		// Sanity fallback.
		if ( empty( $parameters ) ) {
			$parameters = array();
		}

		// JSON encode with safe flags.
		$json = wp_json_encode(
			$parameters,
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
		);

		if ( false === $json ) {
			// Malformed or unencodable data.
			ai4seo_debug_message( 205987913, 'Failed to JSON encode API call parameters.', true );
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
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Compression failure is handled by the strict false check below.
			$compressed_parameters = @gzencode( $json, 5 );

			if ( false !== $compressed_parameters ) {
				$compressed_parameters_length = strlen( $compressed_parameters );

				if ( $compressed_parameters_length <= $this->max_api_payload_size_bytes ) {
					// Add standard and custom headers so the API can detect compression.
					// Backwards compatibility: these headers are only present when compressed.
					$headers['Content-Encoding']         = 'gzip';
					$headers['X-AI4SEO-Body-Compressed'] = 'gzip';
					$headers['X-AI4SEO-Original-Size']   = (string) $json_length;
					$headers['X-AI4SEO-Compressed-Size'] = (string) $compressed_parameters_length;
					$headers['Content-Type']             = 'application/json; charset=utf-8';

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


	/**
	 * Validate and normalize a WP HTTP response.
	 *
	 * Enforces a maximum response size to avoid multi-MB JSON parsing.
	 *
	 * @param mixed  $raw_response The WP HTTP raw response.
	 * @param string $api_url      The requested API URL.
	 * @param string $endpoint     The endpoint that was requested.
	 * @return array               Normalized array or ai4seo error array.
	 */
	public function check_raw_response( $raw_response, string $api_url, string $endpoint = '' ): array {
		// === CHECK FOR WP AND NETWORK ERRORS ================================================ \\

		if ( is_wp_error( $raw_response ) ) {
			if ( $this->is_sensitive_credential_endpoint( $endpoint ) ) {
				ai4seo_debug_message( 133891855, 'WordPress transport error while making a secure credential API call to endpoint ' . $endpoint . '.', true );
				return $this->respond_error( 'Secure credential operation could not reach RobHub.', 5416211025 );
			}

			ai4seo_debug_message( 133891855, 'WP Error while making API call to ' . $api_url . ': ' . $raw_response->get_error_message(), true );
			return $this->respond_error( 'WP Error while making API call: ' . $raw_response->get_error_message(), 5416211025 );
		}

		// Pre-flight size check via Content-Length header (may be absent or compressed).
		try {
			$http_status    = wp_remote_retrieve_response_code( $raw_response );
			$content_length = wp_remote_retrieve_header( $raw_response, 'content-length' );
			$content_length = is_numeric( $content_length ) ? (int) $content_length : 0;

			if ( $content_length > 0 && $content_length > $this->max_response_bytes ) {
				ai4seo_debug_message( 900557894, 'Aborting API call to ' . $api_url . ' due to excessive Content-Length ' . $content_length . ' bytes.', true );
				return $this->respond_error( 'API response too large. Please try again later.', 4517211025 );
			}

			// Body retrieval (may be empty if request used limit_response_size and hit the cap).
			$raw_response_body = wp_remote_retrieve_body( $raw_response );
		} catch ( Exception $e ) {
			if ( $this->is_sensitive_credential_endpoint( $endpoint ) ) {
				unset( $e );
				ai4seo_debug_message( 426958979, 'Could not read the secure credential API response envelope for endpoint ' . $endpoint . '.', true );
				return $this->respond_error( 'Could not read the secure credential response.', 31224725 );
			}

			ai4seo_debug_message( 426958979, 'Exception while retrieving response code/body from ' . $api_url . ': ' . $e->getMessage(), true );
			return $this->respond_error( 'Error retrieving response status and body: ' . $e->getMessage(), 31224725 );
		}

		// === STATUS CHECK =================================================================== \\

		if ( 200 !== (int) $http_status ) {
			if ( $this->is_sensitive_credential_endpoint( $endpoint ) ) {
				$response_keys       = 'none';
				$decoded_error_shape = is_string( $raw_response_body ) ? json_decode( $raw_response_body, true ) : null;
				if ( is_array( $decoded_error_shape ) ) {
					$response_keys = ai4seo_get_debug_array_key_summary( $decoded_error_shape );
				}

				ai4seo_debug_message( 104498924, 'Secure credential API request failed with HTTP status ' . $http_status . ' for endpoint ' . $endpoint . '. Response keys: ' . $response_keys . '.', true );
			} else {
				// Do not log multi-MB bodies; log only a prefix.
				$log_snippet = is_string( $raw_response_body ) ? substr( $raw_response_body, 0, 2048 ) : '';
				ai4seo_debug_message( 104498924, 'API request failed with HTTP status ' . $http_status . ' for api url ' . $api_url . '. Response (first 2KB): ' . $log_snippet, true );
			}

			$raw_response_body  = is_string( $raw_response_body ) ? $raw_response_body : '';
			$api_error_response = $this->get_api_error_from_response_body( $raw_response_body );

			if ( $api_error_response ) {
				return $api_error_response;
			}

			return $this->get_http_status_error_response( (int) $http_status, $api_url, $raw_response_body, $endpoint );
		}

		// === BODY CHECKS ==================================================================== \\

		if ( empty( $raw_response_body ) ) {
			return $this->respond_error( 'Could not execute API call: empty response.', 271823824 );
		}

		// Enforce decoded body size cap.
		if ( strlen( $raw_response_body ) > $this->max_response_bytes ) {
			ai4seo_debug_message( 455748121, 'Aborting API call to ' . $api_url . ' due to oversized body ' . strlen( $raw_response_body ) . ' bytes post-decode.', true );
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
			// Check for HTML error responses.
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


	/**
	 * Return a RobHub API error object from a non-200 response body, if available.
	 *
	 * @param string $raw_response_body The raw response body.
	 * @return array                    The decoded API error response or an empty array.
	 */
	public function get_api_error_from_response_body( string $raw_response_body ): array {
		$raw_response_body = trim( $raw_response_body );

		if ( '' === $raw_response_body || ! $this->is_json( $raw_response_body ) ) {
			return array();
		}

		$raw_response_array = json_decode( $raw_response_body, true );

		if ( ! is_array( $raw_response_array ) || empty( $raw_response_array ) ) {
			return array();
		}

		$has_error_flag = (
			( isset( $raw_response_array['error'] ) && ( true === $raw_response_array['error'] || 'true' === $raw_response_array['error'] ) )
			|| ( isset( $raw_response_array['success'] ) && ( false === $raw_response_array['success'] || 'false' === $raw_response_array['success'] ) )
		);

		if ( ! $has_error_flag ) {
			return array();
		}

		return $raw_response_array;
	}


	/**
	 * Build a clearer error response for non-200 HTTP statuses.
	 *
	 * @param int    $http_status       The HTTP status returned by the API.
	 * @param string $api_url           The requested API URL.
	 * @param string $raw_response_body The raw response body.
	 * @param string $endpoint          The endpoint that was requested.
	 * @return array                    The ai4seo error response.
	 */
	public function get_http_status_error_response( int $http_status, string $api_url, string $raw_response_body, string $endpoint = '' ): array {
		$is_generation_endpoint = in_array( $endpoint, $this->generation_endpoints, true );

		if ( stripos( $raw_response_body, 'curl_init' ) !== false ) {
			return $this->respond_error(
				'The RobHub API could not call the AI service because a required server component is unavailable. Please try again later. Technical detail: PHP cURL is unavailable in the API environment.',
				5810211026
			);
		}

		if ( stripos( $raw_response_body, 'Fatal error' ) !== false || stripos( $raw_response_body, 'Uncaught Error' ) !== false ) {
			return $this->respond_error(
				'The RobHub API encountered a server-side runtime error and could not complete the request. Please try again later. If the issue persists, contact support.',
				5910211026
			);
		}

		if ( $http_status >= 500 ) {
			$error_message = 'The RobHub API returned HTTP status ' . $http_status . '. Please try again later. If the issue persists, contact support.';

			if ( $is_generation_endpoint ) {
				$error_message = 'The RobHub API returned HTTP status ' . $http_status . ' while generating AI content. Please try again later. If the issue persists, contact support.';
			}

			return $this->respond_error( $error_message, 261823824 );
		}

		return $this->respond_error( 'API request failed with HTTP status ' . $http_status . '. Please try again later.', 261823824 );
	}



	/**
	 * Normalize and sanitize a decoded RobHub API response.
	 *
	 * @param array  $raw_response Decoded API response.
	 * @param string $endpoint Endpoint path used for response sanitization.
	 * @return array Normalized success or error response.
	 */
	public function normalize_response( array $raw_response, string $endpoint = '' ): array {
		$normalized_response = array();

		// === CHECK SUCCESS PARAMETER ============================================================================ \\

		if ( isset( $raw_response['success'] ) && 'true' === $raw_response['success'] ) {
			$raw_response['success'] = true;
		}

		if ( isset( $raw_response['success'] ) && 'false' === $raw_response['success'] ) {
			$raw_response['success'] = false;
		}

		if ( isset( $raw_response['error'] ) && 'true' === $raw_response['error'] ) {
			$raw_response['success'] = false;
		}

		if ( isset( $raw_response['error'] ) && 'false' === $raw_response['error'] ) {
			$raw_response['success'] = true;
		}

		if ( isset( $raw_response['error'] ) && true === $raw_response['error'] ) {
			$raw_response['success'] = false;
		}

		// === ALREADY AN RAW OR NORMALIZED ERROR -> MAKE SURE TO NORMALIZE IT PROPERLY! ========================== \\

		if ( ! isset( $raw_response['success'] ) || true !== $raw_response['success'] ) {
			$normalized_error_code   = isset( $raw_response['code'] )
				? $this->normalize_api_error_code( $raw_response['code'] )
				: null;
			$raw_response['code']    = null !== $normalized_error_code ? (int) $normalized_error_code : 391124725;
			$raw_response['message'] = $this->is_sensitive_credential_endpoint( $endpoint ) ? 'Secure credential operation was not confirmed.' : ( isset( $raw_response['message'] ) ? sanitize_text_field( $raw_response['message'] ) : 'API call returned an error without a message.' );
			$raw_response['message'] = "API-Error #{$raw_response['code']}: " . $raw_response['message'];

			// Build the established error shape first; recovery remains opt-in metadata rather than
			// changing the behavior of every existing API error consumer.
			$normalized_error = $this->respond_error( $raw_response['message'], $raw_response['code'] );

			// Keep only the narrowly-defined server-guided source-recovery contract. This is
			// intentionally not a generic error passthrough: the attachment wrapper alone
			// decides whether it can make one local base64 continuation.
			if ( ! $this->is_terminal_billing_error_code( $raw_response['code'] )
				&& isset( $raw_response['recovery'] )
				&& is_array( $raw_response['recovery'] )
			) {
				$recovery_token = $raw_response['recovery']['continuation_token'] ?? '';
				$expires_at     = $raw_response['recovery']['expires_at'] ?? 0;

				if ( 'base64' === ( $raw_response['recovery']['method'] ?? '' )
					&& is_string( $recovery_token )
					&& preg_match( '/^[A-Za-z0-9_-]{32,128}$/', $recovery_token )
					&& is_numeric( $expires_at ) ) {
					$normalized_error['recovery'] = array(
						'method'             => 'base64',
						'continuation_token' => $recovery_token,
						'expires_at'         => (int) $expires_at,
					);
				}
			}

			return $normalized_error;
		}

		// === CHECK PAYLOAD COMPLETENESS ======================================================================== \\

		// check if data is set.
		if ( ! isset( $raw_response['data'] ) ) {
			return $this->respond_error( 'API call did not return any data.', 331823824 );
		}

		if ( empty( $raw_response['data'] ) ) {
			return $this->respond_error( 'API call did not return any data.', 341823824 );
		}

		// check if data is an array.
		if ( $this->is_json( $raw_response['data'] ) ) {
			$raw_response['data'] = json_decode( $raw_response['data'], true );
		}

		// Validate typed endpoint contracts before the generic sanitizer converts integers to strings.
		// Canonical values are restored afterward; malformed input remains for the owning wrapper to reject.
		$validated_sync_rotation_state       = array();
		$validated_subscription_pricing_data = array();

		if ( 'client/sync' === $endpoint
			&& is_array( $raw_response['data'] )
			&& array_key_exists( 'api_password_rotation', $raw_response['data'] ) ) {
			$validated_sync_rotation_state = $this->validate_synced_api_password_rotation_state(
				$raw_response['data']['api_password_rotation']
			);
		}

		if ( self::PREPARE_SUBSCRIPTION_PRICING_ENDPOINT === $endpoint ) {
			$validated_subscription_pricing_data = $this->validate_subscription_pricing_response_data(
				$raw_response['data']
			);
		}

		// sanitize data.
		$raw_response['data'] = $this->deep_sanitize_for_endpoint( $raw_response['data'], $endpoint, 'ai4seo_wp_kses' );

		if ( $validated_sync_rotation_state ) {
			$raw_response['data']['api_password_rotation'] = $validated_sync_rotation_state;
		}

		if ( $validated_subscription_pricing_data ) {
			$raw_response['data'] = $validated_subscription_pricing_data;
		}

		if ( empty( $raw_response['data'] ) ) {
			return $this->respond_error( 'Could not decode or sanitize API call data.', 341823824 );
		}

		// check if credits are set (mandatory for all calls).
		if ( ! isset( $raw_response['credits-consumed'] ) ) {
			$raw_response['credits-consumed'] = 0;
		}

		$normalized_response['success']          = (bool) $raw_response['success'];
		$normalized_response['data']             = $raw_response['data'];
		$normalized_response['credits-consumed'] = (int) $raw_response['credits-consumed'];

		// check if new credits balance is set (mandatory for all calls).
		if ( isset( $raw_response['new-credits-balance'] ) && is_numeric( $raw_response['new-credits-balance'] ) ) {
			$normalized_response['new-credits-balance'] = (int) $raw_response['new-credits-balance'];
		}

		return $normalized_response;
	}


	/**
	 * Return weather the given string is a valid json
	 *
	 * @param mixed $string The string value.
	 * @return bool
	 */
	public function is_json( $string ): bool { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound -- Preserve the public parameter name for named-argument compatibility.
		if ( ! is_string( $string ) ) {
			return false;
		}

		// check if string starts with { or [.
		if ( '{' !== $string[0] && '[' !== $string[0] ) {
			return false;
		}

		json_decode( $string );

		return ( json_last_error() === JSON_ERROR_NONE );
	}


	/**
	 * Determine whether a normalized API response represents success.
	 *
	 * @param mixed $response API response.
	 * @return bool Whether the call succeeded.
	 */
	public function was_call_successful( $response ): bool {
		if ( is_array( $response ) && isset( $response['success'] ) && true === $response['success'] ) {
			return true;
		}

		return false;
	}


	/**
	 * Normalize an exact integer representation for API error-code comparison.
	 *
	 * This method intentionally has no dependency on the plugin's core helpers so legacy callers
	 * can continue loading the communicator directly.
	 *
	 * @param mixed $value Candidate integer representation.
	 * @return string|null Canonical signed integer, or null for another value domain.
	 */
	private function normalize_api_error_code( $value ): ?string {
		if ( is_int( $value ) ) {
			return (string) $value;
		}

		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );

		if ( ! preg_match( '/^[+-]?[0-9]+$/', $value ) ) {
			return null;
		}

		$is_negative = '-' === $value[0];
		$digits      = ltrim( $value, '+-' );
		$digits      = ltrim( $digits, '0' );

		if ( '' === $digits ) {
			return '0';
		}

		return $is_negative ? '-' . $digits : $digits;
	}


	/**
	 * Determine whether an API error code represents a terminal billing failure.
	 *
	 * @param mixed $error_code Candidate API error code.
	 * @return bool Whether the code should halt coordinated generation.
	 */
	public function is_terminal_billing_error_code( $error_code ): bool {
		// Canonicalize integer strings before comparing them with the shared integer registry.
		$error_code = $this->normalize_api_error_code( $error_code );

		return null !== $error_code
			&& in_array( $error_code, array_map( 'strval', self::TERMINAL_BILLING_ERROR_CODES ), true );
	}


	/**
	 * Decide whether legacy callers may switch an image request to base64 after a failed URL request.
	 *
	 * Coordinated generation no longer invokes this unrestricted fallback. The public helper remains
	 * for compatibility; the shared attachment wrapper instead requires a server-issued recovery token.
	 *
	 * @param mixed $response Normalized API response.
	 * @return bool
	 */
	public function can_retry_generation_with_base64( $response ): bool {
		// Only exact integer representations participate in non-retry decisions; other code domains retain the fallback.
		if ( isset( $response['code'] ) ) {
			$error_code = $this->normalize_api_error_code( $response['code'] );

			// Unsupported code shapes retain the permissive fallback instead of being coerced into an API error.
			if ( null === $error_code ) {
				return true;
			}

			// Compare canonical strings so integer and persisted integer-string codes share one strict membership domain.
			$non_post_related_error_codes = array_map( 'strval', $this->non_post_related_error_codes );

			// Non-post-related failures are terminal for both URL and base64 generation attempts.
			if ( in_array( $error_code, $non_post_related_error_codes, true ) ) {
				return false;
			}

			// Inappropriate-content failures cannot be recovered by changing the image transport.
			if ( '3619101024' === $error_code ) {
				return false;
			}

			// Oversized media is marked as failed locally; retrying with base64 would only resend the same large source.
			if ( '71214326' === $error_code ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Apply local recovery actions for selected API errors.
	 *
	 * @param mixed $response Normalized API response.
	 * @return void
	 */
	public function try_handle_special_api_errors( $response ) {
		// Leave unrelated failures to their existing endpoint-specific recovery paths.
		if ( ! isset( $response['code'] ) || ! $this->is_terminal_billing_error_code( $response['code'] ) ) {
			return;
		}

		// Only reset and sync the account once per request.
		if ( $this->has_reset_last_account_sync ) {
			return;
		}

		$this->has_reset_last_account_sync = true;

		// Fetch the current credits balance by syncing the account.
		$this->reset_last_account_sync();
		$this->sync_account( 'insufficient-credits' );
	}


	/**
	 * Function to either get user credentials from wp_options or to create a free account and save the credentials.
	 *
	 * @return bool True if credentials are valid, false otherwise.
	 */
	public function check_credentials(): bool {
		// Once we have seen an auth failure, keep all RobHub communication blocked until the
		// user manually enters replacement credentials in Account settings.
		if ( $this->is_auth_data_locked() ) {
			return false;
		}

		// credentials already saved previously? -> skip.
		if ( $this->has_credentials() ) {
			return true;
		}

		// read robhub auth data from json data in wp_options.
		$auth_data = $this->read_auth_data();

		// otherwise, try to use the saved credentials.
		$auth_data = $this->deep_sanitize( $auth_data );

		if ( isset( $auth_data[0] ) && isset( $auth_data[1] ) ) {
			return $this->use_this_credentials( $auth_data[0], $auth_data[1] );
		}

		return false;
	}


	/**
	 * Function to create a free account and save the credentials.
	 *
	 * @param string $base_username The base username to use for the free account (optional).
	 * @param bool   $update_to_database Whether to save the new credentials to the database.
	 * @return array The API response from the free account creation call, or an error array if the process failed.
	 */
	public function init_free_account( string $base_username = '', bool $update_to_database = true ): array {
		// build pseudo api username and password first.
		$new_api_username = $this->build_api_username( $base_username );

		if ( ! $this->use_this_credentials( $new_api_username, $this->public_get_free_account_api_password ) ) {
			ai4seo_debug_message( 193648888, 'Could not build api for free account creation.', true );
			return array();
		}

		$parameters = $this->build_client_context_parameters();

		// retrieve our real credentials.
		$response = $this->call( 'client/get-free-account', $parameters );

		// check response.
		if ( ! $this->was_call_successful( $response ) || ! isset( $response['data']['api_username'] ) || ! isset( $response['data']['api_password'] ) ) {
			$this->api_username = '';
			$this->api_password = '';
			$response_keys      = is_array( $response )
				? ai4seo_get_debug_array_key_summary( $response )
				: 'none';
			$data_keys          = isset( $response['data'] ) && is_array( $response['data'] )
				? ai4seo_get_debug_array_key_summary( $response['data'] )
				: 'none';
			ai4seo_debug_message(
				704894316,
				'Could not create free account. Response keys: ' . $response_keys . '. Data keys: ' . $data_keys . '.',
				true
			);
			return $response;
		}

		// try save new credentials.
		if ( ! $this->use_this_credentials( $response['data']['api_username'], $response['data']['api_password'], $update_to_database ) ) {
			$this->api_username = '';
			$this->api_password = '';
			ai4seo_debug_message( 802042473, 'Could not save free account credentials.', true );
			return $this->respond_error( 'Could not save free account credentials locally.', 261118426 );
		}

		// everything went fine.
		return $response;
	}


	/**
	 * Build shared client context parameters for account-related API calls.
	 *
	 * @return array Client context parameters.
	 */
	public function build_client_context_parameters(): array {
		return array(
			'product_activation_time' => $this->product_activation_time,
			'users_current_time'      => time(),
			'website_url'             => sanitize_text_field( get_site_url() ),
			'website_name'            => sanitize_text_field( get_bloginfo( 'name' ) ),
			'admin_email_address'     => sanitize_email( ai4seo_get_option( 'admin_email' ) ),
			'client_ip_address'       => ai4seo_get_client_ip(),
			'user_agent'              => ai4seo_get_client_user_agent(),
		);
	}


	/**
	 * Build AI4SEO opportunity report parameters for account sync.
	 *
	 * @return array Sync report parameters.
	 */
	public function build_client_sync_report_parameters(): array {
		$generation_status_summary          = ai4seo_read_generation_status_summary( true, true );
		$enabled_bulk_generation_post_types = ai4seo_get_enabled_bulk_generation_post_types();

		return array(
			'generation_status_summary'          => $generation_status_summary,
			'costs_per_post'                     => ai4seo_calculate_metadata_credits_cost_per_post(),
			'costs_per_attachment'               => ai4seo_calculate_attachment_attributes_credits_cost_per_attachment_post(),
			'enabled_bulk_generation_post_types' => $enabled_bulk_generation_post_types,
		);
	}


	/**
	 * Checks if we got valid credentials already
	 *
	 * @return bool True if credentials are set, false otherwise.
	 */
	public function has_credentials(): bool {
		return isset( $this->api_username ) && $this->api_username && isset( $this->api_password ) && $this->api_password;
	}


	/**
	 * Saves the given credentials to the corresponding variables.
	 *
	 * @param string $api_username The api username to save.
	 * @param string $api_password The api password to save.
	 * @param bool   $update_in_database If true, the credentials will be saved in the database.
	 * @return bool True if credentials are valid and saved, false otherwise.
	 */
	public function use_this_credentials( string $api_username, string $api_password, bool $update_in_database = false ): bool {
		$api_username = sanitize_key( $api_username );
		$api_password = sanitize_key( $api_password );

		// Require the same non-empty credential contract used by durable environmental storage.
		if ( ! $this->is_non_empty_api_username_valid( $api_username ) ) {
			return false;
		}

		if ( ! $this->is_non_empty_api_password_valid( $api_password ) ) {
			return false;
		}

		if ( $update_in_database ) {
			$pending_rotation    = $this->read_pending_api_password_rotation();
			$recovery_inspection = $this->inspect_api_password_rotation_recovery_intent();
			$update_result       = $this->bulk_update_environmental_variables(
				array(
					self::ENVIRONMENTAL_VARIABLE_API_USERNAME => $api_username,
					self::ENVIRONMENTAL_VARIABLE_API_PASSWORD => $api_password,
				)
			);

			$this->synchronize_runtime_credentials_from_environmental_variables();

			if ( ! $update_result['success'] ) {
				return false;
			}

			$fresh_environmental_variables = $this->read_fresh_environmental_variables();

			if ( ! $fresh_environmental_variables
				|| ! hash_equals(
					$api_username,
					(string) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ]
				)
				|| ! hash_equals(
					$api_password,
					(string) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ]
				) ) {
				return false;
			}

			$this->environmental_variables = $fresh_environmental_variables;
			$this->synchronize_runtime_credentials_from_environmental_variables();

			$is_pending_rotation_generation = $pending_rotation
				&& hash_equals( $pending_rotation['api_username'], $api_username )
				&& ( hash_equals( $pending_rotation['current_api_password'], $api_password )
					|| hash_equals( $pending_rotation['new_api_password'], $api_password ) );

			// Explicit reconnects supersede a transition tied to another credential pair. Remove
			// that stale recovery state only after the replacement pair was read back durably.
			if ( $pending_rotation
				&& ! $is_pending_rotation_generation
				&& ! $this->delete_pending_api_password_rotation( $pending_rotation ) ) {
				return false;
			}

			// A successful explicit reconnect is the only user-driven action that retires the
			// check-email recovery notice. Exact deletion prevents a stale request removing a newer intent.
			if ( 'missing' !== ( $recovery_inspection['state'] ?? '' )
				&& ! $this->delete_api_password_rotation_recovery_intent( $recovery_inspection ) ) {
				return false;
			}

			return true;
		}

		$this->api_username = $api_username;
		$this->api_password = $api_password;

		return true;
	}


	/**
	 * Return the validated durable pending API-password rotation.
	 *
	 * Recovery material has its own non-autoloaded option so it is loaded only while a transition
	 * is actually being reconciled. WordPress' recognized non-autoload encodings are equivalent;
	 * a structurally valid row that became autoloaded is exact-CAS repaired before any secret is used.
	 *
	 * @return array Valid pending state, or an empty array when none exists.
	 */
	public function read_pending_api_password_rotation(): array {
		$inspection = $this->inspect_pending_api_password_rotation();

		if ( 'repairable' === $inspection['state'] ) {
			$inspection = $this->repair_pending_api_password_rotation_autoload( $inspection );
		}

		if ( 'valid' !== $inspection['state'] ) {
			return array();
		}

		return $inspection['value'];
	}


	/**
	 * Classify the exact pending-option row without conflating absence, poison, or read failure.
	 *
	 * The snapshot stays internal because malformed rows may contain attacker-controlled secrets.
	 *
	 * @return array{state:string,value:array,snapshot:array|null}
	 */
	private function inspect_pending_api_password_rotation(): array {
		$option_snapshot = ai4seo_get_raw_option_snapshot( self::PENDING_API_PASSWORD_ROTATION_OPTION_NAME );

		if ( ! is_array( $option_snapshot ) ) {
			return array(
				'state'    => 'unavailable',
				'value'    => array(),
				'snapshot' => null,
			);
		}

		if ( ! $option_snapshot['exists'] ) {
			return array(
				'state'    => 'missing',
				'value'    => array(),
				'snapshot' => $option_snapshot,
			);
		}

		$is_valid_value = is_array( $option_snapshot['value'] )
			&& array() !== $option_snapshot['value']
			&& $this->is_pending_api_password_rotation_state_valid( $option_snapshot['value'] );

		if ( ! $is_valid_value ) {
			return array(
				'state'    => 'invalid',
				'value'    => array(),
				'snapshot' => $option_snapshot,
			);
		}

		if ( $this->is_non_autoload_option_value( $option_snapshot['autoload'] ) ) {
			return array(
				'state'    => 'valid',
				'value'    => $option_snapshot['value'],
				'snapshot' => $option_snapshot,
			);
		}

		if ( $this->is_autoload_option_value( $option_snapshot['autoload'] ) ) {
			return array(
				'state'    => 'repairable',
				'value'    => $option_snapshot['value'],
				'snapshot' => $option_snapshot,
			);
		}

		return array(
			'state'    => 'invalid',
			'value'    => array(),
			'snapshot' => $option_snapshot,
		);
	}


	/**
	 * Return whether an options-table autoload token is explicitly non-autoloaded.
	 *
	 * @param mixed $autoload Raw autoload token.
	 * @return bool Whether WordPress keeps the row out of alloptions.
	 */
	private function is_non_autoload_option_value( $autoload ): bool {
		return is_string( $autoload ) && in_array( $autoload, array( 'no', 'off', 'auto-off' ), true );
	}


	/**
	 * Return whether an options-table token is recognized as autoloaded by WordPress.
	 *
	 * @param mixed $autoload Raw autoload token.
	 * @return bool Whether WordPress includes the row in alloptions.
	 */
	private function is_autoload_option_value( $autoload ): bool {
		$autoload_values = function_exists( 'wp_autoload_values_to_autoload' )
			? wp_autoload_values_to_autoload()
			: array( 'yes', 'on', 'auto-on', 'auto' );

		return is_string( $autoload ) && in_array( $autoload, $autoload_values, true );
	}


	/**
	 * Move one structurally valid secret row out of autoload storage using its exact raw snapshot.
	 *
	 * @param array $inspection Repairable inspection result.
	 * @return array Updated inspection result.
	 */
	private function repair_pending_api_password_rotation_autoload( array $inspection ): array {
		$option_snapshot  = $inspection['snapshot'] ?? null;
		$pending_rotation = $inspection['value'] ?? array();

		if ( 'repairable' !== ( $inspection['state'] ?? '' )
			|| ! is_array( $option_snapshot )
			|| ! is_array( $pending_rotation )
			|| ! $this->is_pending_api_password_rotation_state_valid( $pending_rotation ) ) {
			return array(
				'state'    => 'invalid',
				'value'    => array(),
				'snapshot' => is_array( $option_snapshot ) ? $option_snapshot : null,
			);
		}

		// Reinspect authoritative storage after any CAS outcome so a racing writer remains visible.
		ai4seo_compare_and_swap_option_snapshot(
			self::PENDING_API_PASSWORD_ROTATION_OPTION_NAME,
			$option_snapshot,
			$pending_rotation,
			false
		);

		return $this->inspect_pending_api_password_rotation();
	}


	/**
	 * Replace one exact pending-rotation generation and verify its durable bytes.
	 *
	 * @param array $pending_rotation Replacement state.
	 * @param array $expected_pending_rotation Exact prior state, or an empty array for a missing row.
	 * @return bool Whether the non-autoloaded replacement was durably verified.
	 */
	private function write_pending_api_password_rotation( array $pending_rotation, array $expected_pending_rotation = array() ): bool {
		// Reject malformed generations before they can participate in exact raw-option comparisons.
		if ( array() === $pending_rotation
			|| ! $this->is_pending_api_password_rotation_state_valid( $pending_rotation )
			|| ( array() !== $expected_pending_rotation
				&& ! $this->is_pending_api_password_rotation_state_valid( $expected_pending_rotation ) ) ) {
			return false;
		}

		// Retry only compare-and-swap races; schema and storage failures remain closed.
		for ( $storage_attempt = 0; $storage_attempt < self::API_PASSWORD_ROTATION_STORAGE_MAX_ATTEMPTS; ++$storage_attempt ) {
			$option_snapshot = ai4seo_get_raw_option_snapshot( self::PENDING_API_PASSWORD_ROTATION_OPTION_NAME );

			if ( ! is_array( $option_snapshot ) ) {
				return false;
			}

			if ( $option_snapshot['exists'] ) {
				if ( ! $this->is_non_autoload_option_value( $option_snapshot['autoload'] )
					|| ! is_array( $option_snapshot['value'] ) ) {
					return false;
				}

				if ( $pending_rotation === $option_snapshot['value'] ) {
					return true;
				}

				if ( $expected_pending_rotation !== $option_snapshot['value'] ) {
					return false;
				}
			} elseif ( array() !== $expected_pending_rotation ) {
				return false;
			}

			$write_result = ai4seo_compare_and_swap_option_snapshot(
				self::PENDING_API_PASSWORD_ROTATION_OPTION_NAME,
				$option_snapshot,
				$pending_rotation,
				false
			);

			if ( null === $write_result ) {
				return false;
			}

			// Read back exact serialized bytes so a successful CAS cannot be confused with a later writer.
			$verified_snapshot = ai4seo_get_raw_option_snapshot( self::PENDING_API_PASSWORD_ROTATION_OPTION_NAME );

			if ( is_array( $verified_snapshot )
				&& $verified_snapshot['exists']
				&& 'no' === $verified_snapshot['autoload']
				&& hash_equals( (string) maybe_serialize( $pending_rotation ), $verified_snapshot['raw_value'] )
				&& $pending_rotation === $verified_snapshot['value'] ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Delete one exact confirmed transition and verify that no recovery material remains.
	 *
	 * @param array $expected_pending_rotation Exact confirmed state to remove.
	 * @return bool Whether the option is absent after the deletion attempt.
	 */
	private function delete_pending_api_password_rotation( array $expected_pending_rotation ): bool {
		// Exact deletion is available only to callers holding a complete validated generation.
		if ( array() === $expected_pending_rotation
			|| ! $this->is_pending_api_password_rotation_state_valid( $expected_pending_rotation ) ) {
			return false;
		}

		// Retry only when another request won the raw-option race before this exact deletion.
		for ( $storage_attempt = 0; $storage_attempt < self::API_PASSWORD_ROTATION_STORAGE_MAX_ATTEMPTS; ++$storage_attempt ) {
			$option_snapshot = ai4seo_get_raw_option_snapshot( self::PENDING_API_PASSWORD_ROTATION_OPTION_NAME );

			if ( ! is_array( $option_snapshot ) ) {
				return false;
			}

			if ( ! $option_snapshot['exists'] ) {
				return true;
			}

			if ( ! $this->is_non_autoload_option_value( $option_snapshot['autoload'] )
				|| ! is_array( $option_snapshot['value'] )
				|| $expected_pending_rotation !== $option_snapshot['value'] ) {
				return false;
			}

			$delete_result = ai4seo_compare_and_delete_option_snapshot(
				self::PENDING_API_PASSWORD_ROTATION_OPTION_NAME,
				$option_snapshot
			);

			if ( null === $delete_result ) {
				return false;
			}

			// Confirm absence from authoritative storage before reporting the recovery material removed.
			$verified_snapshot = ai4seo_get_raw_option_snapshot( self::PENDING_API_PASSWORD_ROTATION_OPTION_NAME );

			if ( is_array( $verified_snapshot ) && ! $verified_snapshot['exists'] ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Delete one exact malformed pending row after authenticated server state makes it disposable.
	 *
	 * No value from the snapshot is logged, copied, or interpreted by this path.
	 *
	 * @param array $expected_snapshot Exact raw options-table snapshot.
	 * @return bool Whether that exact row is now absent.
	 */
	private function delete_pending_api_password_rotation_snapshot( array $expected_snapshot ): bool {
		if ( empty( $expected_snapshot['exists'] )
			|| (string) self::PENDING_API_PASSWORD_ROTATION_OPTION_NAME !== (string) ( $expected_snapshot['option_name'] ?? '' ) ) {
			return false;
		}

		$delete_result = ai4seo_compare_and_delete_option_snapshot(
			self::PENDING_API_PASSWORD_ROTATION_OPTION_NAME,
			$expected_snapshot
		);

		if ( true !== $delete_result ) {
			return false;
		}

		$verified_snapshot = ai4seo_get_raw_option_snapshot( self::PENDING_API_PASSWORD_ROTATION_OPTION_NAME );

		return is_array( $verified_snapshot ) && ! $verified_snapshot['exists'];
	}


	/**
	 * Inspect the non-secret server-recovery replay intent without collapsing storage failures.
	 *
	 * @return array{state:string,value:array,snapshot:array|null}
	 */
	private function inspect_api_password_rotation_recovery_intent(): array {
		$option_snapshot = ai4seo_get_raw_option_snapshot( self::API_PASSWORD_ROTATION_RECOVERY_INTENT_OPTION_NAME );

		if ( ! is_array( $option_snapshot ) ) {
			return array(
				'state'    => 'unavailable',
				'value'    => array(),
				'snapshot' => null,
			);
		}

		if ( ! $option_snapshot['exists'] ) {
			return array(
				'state'    => 'missing',
				'value'    => array(),
				'snapshot' => $option_snapshot,
			);
		}

		$intent = is_array( $option_snapshot['value'] ) ? $option_snapshot['value'] : array();

		if ( ! $this->is_api_password_rotation_recovery_intent_valid( $intent ) ) {
			return array(
				'state'    => 'invalid',
				'value'    => array(),
				'snapshot' => $option_snapshot,
			);
		}

		if ( $this->is_non_autoload_option_value( $option_snapshot['autoload'] ) ) {
			return array(
				'state'    => 'valid',
				'value'    => $intent,
				'snapshot' => $option_snapshot,
			);
		}

		if ( $this->is_autoload_option_value( $option_snapshot['autoload'] ) ) {
			$write_result = ai4seo_compare_and_swap_option_snapshot(
				self::API_PASSWORD_ROTATION_RECOVERY_INTENT_OPTION_NAME,
				$option_snapshot,
				$intent,
				false
			);

			// Both a successful rewrite and a lost race require a fresh authoritative classification.
			if ( is_bool( $write_result ) ) {
				return $this->inspect_api_password_rotation_recovery_intent();
			}

			return array(
				'state'    => 'unavailable',
				'value'    => array(),
				'snapshot' => null,
			);
		}

		return array(
			'state'    => 'invalid',
			'value'    => array(),
			'snapshot' => $option_snapshot,
		);
	}


	/**
	 * Validate the closed, non-secret recovery-intent schema.
	 *
	 * @param mixed $intent Candidate intent.
	 * @return bool Whether every field is canonical.
	 */
	private function is_api_password_rotation_recovery_intent_valid( $intent ): bool {
		if ( ! is_array( $intent )
			|| array_keys( $intent ) !== array(
				'version',
				'api_username',
				'credential_fingerprint',
				'status',
				'credential_email_status',
				'next_reconciliation_at',
				'reconciliation_attempts',
				'created_at',
			) ) {
			return false;
		}

		$status       = $intent['status'];
		$email_status = $intent['credential_email_status'];

		return '1' === $intent['version']
			&& is_string( $intent['api_username'] )
			&& $this->is_non_empty_api_username_valid( $intent['api_username'] )
			&& is_string( $intent['credential_fingerprint'] )
			&& 1 === preg_match( '/^r1:[a-f0-9]{64}$/', $intent['credential_fingerprint'] )
			&& in_array( $status, array( 'pending', 'confirmed' ), true )
			&& in_array( $email_status, array( 'not-applicable', 'pending', 'failed', 'sent' ), true )
			&& ( ( 'pending' === $status && 'not-applicable' === $email_status )
				|| ( 'confirmed' === $status && 'not-applicable' !== $email_status ) )
			&& $this->is_canonical_non_negative_integer_string( $intent['next_reconciliation_at'] )
			&& $this->is_canonical_non_negative_integer_string( $intent['reconciliation_attempts'] )
			&& $this->is_canonical_positive_integer_string( $intent['created_at'] );
	}


	/**
	 * Validate a canonical decimal string including zero.
	 *
	 * @param mixed $value Candidate value.
	 * @return bool Whether the value is canonical.
	 */
	private function is_canonical_non_negative_integer_string( $value ): bool {
		return is_string( $value ) && preg_match( '/^(?:0|[1-9][0-9]*)$/', $value ) === 1;
	}


	/**
	 * Validate a canonical positive decimal string.
	 *
	 * @param mixed $value Candidate value.
	 * @return bool Whether the value is canonical.
	 */
	private function is_canonical_positive_integer_string( $value ): bool {
		return is_string( $value ) && preg_match( '/^[1-9][0-9]*$/', $value ) === 1;
	}


	/**
	 * Determine whether an untrusted map contains exactly the allowed response keys.
	 *
	 * API response order is intentionally irrelevant, while unknown fields fail closed.
	 *
	 * @param mixed $candidate Candidate response map.
	 * @param array $expected_keys Closed key allowlist.
	 * @return bool Whether the candidate contains each expected key and no others.
	 */
	private function has_exact_array_keys( $candidate, array $expected_keys ): bool {
		return is_array( $candidate )
			&& count( $candidate ) === count( $expected_keys )
			&& array() === array_diff( array_keys( $candidate ), $expected_keys );
	}


	/**
	 * Persist one exact recovery-intent generation as a non-autoloaded option.
	 *
	 * @param array $intent Replacement intent.
	 * @param array $expected_inspection Exact prior inspection.
	 * @return bool Whether the replacement was durably verified.
	 */
	private function write_api_password_rotation_recovery_intent( array $intent, array $expected_inspection ): bool {
		$expected_snapshot = $expected_inspection['snapshot'] ?? null;

		if ( ! $this->is_api_password_rotation_recovery_intent_valid( $intent )
			|| ! is_array( $expected_snapshot )
			|| ! in_array( $expected_inspection['state'] ?? '', array( 'missing', 'valid' ), true ) ) {
			return false;
		}

		if ( 'valid' === $expected_inspection['state']
			&& ( $expected_inspection['value'] ?? array() ) === $intent
			&& $this->is_non_autoload_option_value( $expected_snapshot['autoload'] ?? '' ) ) {
			return true;
		}

		$write_result = ai4seo_compare_and_swap_option_snapshot(
			self::API_PASSWORD_ROTATION_RECOVERY_INTENT_OPTION_NAME,
			$expected_snapshot,
			$intent,
			false
		);

		if ( true !== $write_result ) {
			return false;
		}

		$verified = $this->inspect_api_password_rotation_recovery_intent();

		return 'valid' === $verified['state'] && $intent === $verified['value'];
	}


	/**
	 * Delete the exact recovery-intent row after a manual credential reconnect.
	 *
	 * @param array $inspection Exact valid inspection.
	 * @return bool Whether the option is absent.
	 */
	private function delete_api_password_rotation_recovery_intent( array $inspection ): bool {
		$expected_snapshot = $inspection['snapshot'] ?? null;

		if ( 'missing' === ( $inspection['state'] ?? '' ) ) {
			return true;
		}

		if ( ! in_array( $inspection['state'] ?? '', array( 'valid', 'invalid' ), true )
			|| ! is_array( $expected_snapshot ) ) {
			return false;
		}

		if ( true !== ai4seo_compare_and_delete_option_snapshot(
			self::API_PASSWORD_ROTATION_RECOVERY_INTENT_OPTION_NAME,
			$expected_snapshot
		) ) {
			return false;
		}

		$verified_snapshot = ai4seo_get_raw_option_snapshot( self::API_PASSWORD_ROTATION_RECOVERY_INTENT_OPTION_NAME );

		return is_array( $verified_snapshot ) && ! $verified_snapshot['exists'];
	}


	/**
	 * Return whether any non-missing recovery intent must block ordinary API calls.
	 *
	 * Invalid and unreadable states intentionally block rather than silently reusing old credentials.
	 *
	 * @return bool Whether recovery owns the credential transition.
	 */
	public function has_api_password_rotation_recovery_intent(): bool {
		return 'missing' !== $this->inspect_api_password_rotation_recovery_intent()['state'];
	}


	/**
	 * Return the non-secret recovery notice state for the Account UI.
	 *
	 * @return array{status:string,credential_email_status:string}
	 */
	public function get_api_password_rotation_recovery_notice_state(): array {
		$inspection = $this->inspect_api_password_rotation_recovery_intent();

		if ( 'valid' !== $inspection['state'] ) {
			return array(
				'status'                  => 'none',
				'credential_email_status' => 'not-applicable',
			);
		}

		return array(
			'status'                  => $inspection['value']['status'],
			'credential_email_status' => $inspection['value']['credential_email_status'],
		);
	}


	/**
	 * Create one durable server-recovery intent before any irreversible request is made.
	 *
	 * @return bool Whether a pending or confirmed intent exists for the current username.
	 */
	private function ensure_api_password_rotation_recovery_intent(): bool {
		$inspection = $this->inspect_api_password_rotation_recovery_intent();

		if ( 'valid' === $inspection['state'] ) {
			return hash_equals(
				(string) $inspection['value']['api_username'],
				(string) $this->api_username
			);
		}

		if ( 'missing' !== $inspection['state']
			|| ! $this->is_non_empty_api_username_valid( $this->api_username ) ) {
			return false;
		}

		$credential_fingerprint = $this->build_api_password_rotation_recovery_credential_fingerprint( $this->api_username, $this->api_password );
		if ( '' === $credential_fingerprint ) {
			return false;
		}

		$intent = array(
			'version'                 => '1',
			'api_username'            => $this->api_username,
			'credential_fingerprint'  => $credential_fingerprint,
			'status'                  => 'pending',
			'credential_email_status' => 'not-applicable',
			'next_reconciliation_at'  => '0',
			'reconciliation_attempts' => '0',
			'created_at'              => (string) time(),
		);

		return $this->write_api_password_rotation_recovery_intent( $intent, $inspection );
	}


	/**
	 * Build a keyed, non-reversible fingerprint for one local credential generation.
	 *
	 * @param string $api_username Credential username.
	 * @param string $api_password Credential password.
	 * @return string Keyed fingerprint, or an empty string when no local key is available.
	 */
	private function build_api_password_rotation_recovery_credential_fingerprint( string $api_username, string $api_password ): string {
		if ( ! function_exists( 'wp_salt' )
			|| ! $this->is_non_empty_api_username_valid( $api_username )
			|| ! $this->is_non_empty_api_password_valid( $api_password ) ) {
			return '';
		}

		$key = wp_salt( 'auth' );
		if ( ! is_string( $key ) || strlen( $key ) < 32 ) {
			return '';
		}

		return 'r1:' . hash_hmac( 'sha256', $this->product . "\0" . $api_username . "\0" . $api_password, $key );
	}


	/**
	 * Validate the closed confirmation returned by server-generated credential recovery.
	 *
	 * @param mixed $response Normalized API response.
	 * @return bool Whether the exact non-secret confirmation contract was returned.
	 */
	private function is_api_password_rotation_recovery_confirmation( $response ): bool {
		return $this->was_call_successful( $response )
			&& isset( $response['data'] )
			&& $this->has_exact_array_keys(
				$response['data'],
				array( 'rotation_confirmed', 'credential_email_status' )
			)
			&& true === ( $response['data']['rotation_confirmed'] ?? null )
			&& in_array( $response['data']['credential_email_status'] ?? '', array( 'pending', 'failed', 'sent' ), true );
	}


	/**
	 * Resolve a confirmed intent after the local fingerprint key changed.
	 *
	 * The server first gets an exact old-generation replay opportunity. Only its explicit conflict
	 * permits an authenticated current-credential probe; transport or storage uncertainty preserves
	 * the intent. This makes WordPress salt rotation recoverable without weakening local CAS safety.
	 *
	 * @param array $intent Confirmed recovery intent.
	 * @param array $inspection Exact option inspection for the intent.
	 * @return bool Whether server truth safely resolved the intent.
	 */
	private function reconcile_confirmed_recovery_after_fingerprint_change( array $intent, array $inspection ): bool {
		if ( ! hash_equals( $intent['api_username'], $this->api_username )
			|| ! $this->is_non_empty_api_password_valid( $this->api_password )
			|| $this->is_reconciling_api_password_rotation_recovery ) {
			return false;
		}

		$submitted_api_username                              = $this->api_username;
		$submitted_api_password                              = $this->api_password;
		$this->is_reconciling_api_password_rotation_recovery = true;

		try {
			$recovery_response = $this->call(
				self::RECOVER_API_PASSWORD_ROTATION_ENDPOINT,
				array(
					'api_username'         => $submitted_api_username,
					'current_api_password' => $submitted_api_password,
				),
				'POST',
				true
			);
			if ( $this->is_api_password_rotation_recovery_confirmation( $recovery_response ) ) {
				return $this->conditionally_invalidate_recovered_auth_data( $submitted_api_username, $submitted_api_password );
			}

			if ( self::API_PASSWORD_ROTATION_CONFLICT_ERROR_CODE !== (int) ( $recovery_response['code'] ?? 0 ) ) {
				return false;
			}

			$current_credential_response = $this->call(
				'client/sync',
				array(
					'reason'             => 'rotation-recovery-intent-verification',
					'wordpress_language' => sanitize_text_field( get_locale() ),
				),
				'POST'
			);
			if ( ! $this->was_call_successful( $current_credential_response )
				|| ! hash_equals( $submitted_api_username, $this->api_username )
				|| ! hash_equals( $submitted_api_password, $this->api_password ) ) {
				return false;
			}

			return $this->delete_api_password_rotation_recovery_intent( $inspection );
		} finally {
			$this->is_reconciling_api_password_rotation_recovery = false;
		}
	}


	/**
	 * Replay one server-generated recovery using the still-current old credential pair.
	 *
	 * @return bool Whether strict server confirmation was persisted and local auth was locked.
	 */
	public function reconcile_api_password_rotation_recovery_intent(): bool {
		$inspection = $this->inspect_api_password_rotation_recovery_intent();

		if ( 'valid' !== $inspection['state'] ) {
			return 'missing' === $inspection['state'];
		}

		$intent = $inspection['value'];

		if ( 'confirmed' === $intent['status'] ) {
			// Confirmation is durable before the local credential pair is cleared. If that
			// second write was interrupted, finish it on the next reconciliation instead of
			// treating the old password as usable merely because the response was recorded.
			if ( $this->is_auth_data_locked() && '' === $this->api_username && '' === $this->api_password ) {
				return true;
			}

			if ( ! hash_equals( $intent['credential_fingerprint'], $this->build_api_password_rotation_recovery_credential_fingerprint( $this->api_username, $this->api_password ) ) ) {
				return $this->reconcile_confirmed_recovery_after_fingerprint_change( $intent, $inspection );
			}

			return $this->conditionally_invalidate_recovered_auth_data( $this->api_username, $this->api_password );
		}

		if ( $this->is_reconciling_api_password_rotation_recovery
		|| (int) $intent['next_reconciliation_at'] > time()
		|| ! hash_equals( $intent['api_username'], $this->api_username )
		|| ! $this->is_non_empty_api_password_valid( $this->api_password ) ) {
			return false;
		}

		$claimed_intent                            = $intent;
		$claimed_intent['reconciliation_attempts'] = (string) min(
			1000000,
			(int) $intent['reconciliation_attempts'] + 1
		);
		$claimed_intent['next_reconciliation_at']  = (string) (
		time() + self::API_PASSWORD_ROTATION_RECOVERY_RETRY_DELAY
		);

		if ( ! $this->write_api_password_rotation_recovery_intent( $claimed_intent, $inspection ) ) {
			return false;
		}

		$submitted_api_password                              = $this->api_password;
		$this->is_reconciling_api_password_rotation_recovery = true;

		try {
			$response = $this->call(
				self::RECOVER_API_PASSWORD_ROTATION_ENDPOINT,
				array(
					'api_username'         => $intent['api_username'],
					'current_api_password' => $submitted_api_password,
				),
				'POST',
				true
			);
		} finally {
			$this->is_reconciling_api_password_rotation_recovery = false;
		}

		if ( ! $this->is_api_password_rotation_recovery_confirmation( $response ) ) {
			return false;
		}

		$fresh_inspection = $this->inspect_api_password_rotation_recovery_intent();

		if ( 'valid' !== $fresh_inspection['state']
		|| 'pending' !== $fresh_inspection['value']['status']
		|| ! hash_equals( $intent['api_username'], $fresh_inspection['value']['api_username'] ) ) {
			return false;
		}

		$confirmed_intent                            = $fresh_inspection['value'];
		$confirmed_intent['status']                  = 'confirmed';
		$confirmed_intent['credential_email_status'] = $response['data']['credential_email_status'];
		$confirmed_intent['next_reconciliation_at']  = '0';

		if ( ! $this->write_api_password_rotation_recovery_intent( $confirmed_intent, $fresh_inspection ) ) {
			return false;
		}

		return $this->conditionally_invalidate_recovered_auth_data( $intent['api_username'], $submitted_api_password );
	}


	/**
	 * Clear only the exact old credential generation authorized by recovery.
	 *
	 * @param string $expected_api_username Username used by the recovery request.
	 * @param string $expected_api_password Password used by the recovery request.
	 * @return bool Whether the old generation is locked or a different complete pair won.
	 */
	private function conditionally_invalidate_recovered_auth_data( string $expected_api_username, string $expected_api_password ): bool {
		for ( $storage_attempt = 0; $storage_attempt < self::API_PASSWORD_ROTATION_STORAGE_MAX_ATTEMPTS; ++$storage_attempt ) {
			$environmental_snapshot = ai4seo_get_raw_option_snapshot( $this->environmental_variables_option_name );

			if ( ! is_array( $environmental_snapshot )
				|| ! $environmental_snapshot['exists']
				|| ! is_array( $environmental_snapshot['value'] ) ) {
				return false;
			}

			$current_environmental_variables = $this->normalize_environmental_variables( $environmental_snapshot['value'] );
			$current_api_username            = (string) $current_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ];
			$current_api_password            = (string) $current_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ];

			// A prior replay may already have cleared and locked this exact local generation.
			if ( '' === $current_api_username
				&& '' === $current_api_password
				&& (bool) $current_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED ] ) {
				$this->environmental_variables = $current_environmental_variables;
				$this->synchronize_runtime_credentials_from_environmental_variables();
				return true;
			}

			if ( ! hash_equals( $expected_api_username, $current_api_username )
				|| ! hash_equals( $expected_api_password, $current_api_password ) ) {
				// Never overwrite a complete pair installed by an overlapping Account reconnect.
				if ( $this->is_non_empty_api_username_valid( $current_api_username )
					&& $this->is_non_empty_api_password_valid( $current_api_password ) ) {
					$this->environmental_variables = $current_environmental_variables;
					$this->synchronize_runtime_credentials_from_environmental_variables();
					return true;
				}

				return false;
			}

			$invalidated_environmental_variables = $current_environmental_variables;
			$invalidated_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ]      = '';
			$invalidated_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ]      = '';
			$invalidated_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED ]    = true;
			$invalidated_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC ] = 0;
			$invalidated_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED ] = false;
			$invalidated_overrides = $this->build_environmental_variable_overrides( $invalidated_environmental_variables );
			$write_result          = $this->compare_and_swap_environmental_variables_snapshot( $environmental_snapshot, $invalidated_overrides );

			if ( null === $write_result ) {
				return false;
			}

			if ( false === $write_result ) {
				continue;
			}

			$verified_environmental_variables = $this->read_fresh_environmental_variables();

			if ( ! $verified_environmental_variables
				|| '' !== (string) $verified_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ]
				|| '' !== (string) $verified_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ]
				|| ! (bool) $verified_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED ] ) {
				continue;
			}

			$this->environmental_variables = $verified_environmental_variables;
			$this->synchronize_runtime_credentials_from_environmental_variables();
			return true;
		}

		return false;
	}


	/**
	 * Determine whether a durable API-password rotation is pending.
	 *
	 * @return bool Whether valid pending state exists.
	 */
	public function has_pending_api_password_rotation(): bool {
		$this->read_pending_api_password_rotation();
		return 'missing' !== $this->inspect_pending_api_password_rotation()['state'];
	}


	/**
	 * Determine whether durable recovery applies to the credentials selected in this instance.
	 *
	 * @return bool Whether the runtime pair belongs to the pending old/new generation.
	 */
	public function has_applicable_pending_api_password_rotation(): bool {
		$pending_rotation = $this->read_pending_api_password_rotation();

		if ( ! $pending_rotation ) {
			return false;
		}

		if ( $this->has_credentials() || $this->check_credentials() ) {
			return $this->does_pending_api_password_rotation_match_runtime_credentials( $pending_rotation );
		}

		// A reset/partial restore can remove the shared environmental option while preserving the
		// dedicated recovery record. Only an entirely empty runtime may use that record; temporary
		// Control credentials and conflicting partial pairs remain authoritative for their request.
		return '' === $this->api_username && '' === $this->api_password;
	}


	/**
	 * Prepare and durably retain a signed purchase claim for an API-password rotation.
	 *
	 * Repeated calls reuse the same locally persisted replacement credential. This makes an
	 * interrupted prepare response safe to retry. Future self-service rotation can reuse the
	 * recovery and promotion primitives, but must use a distinct claim version and purpose.
	 *
	 * @return string Exact signed claim returned by RobHub, or an empty string on failure.
	 */
	public function prepare_api_password_rotation_claim(): string {
		if ( $this->has_api_password_rotation_recovery_intent() ) {
			return '';
		}

		// A fresh request-scoped communicator has not loaded its durable credentials yet. The
		// purchase CTA can be its first API workflow, so initialize the runtime pair before
		// comparing it with the authoritative storage generation below.
		if ( ! $this->check_credentials() ) {
			return '';
		}

		// Reuse one durable candidate generation across retries so response loss cannot orphan a secret.
		$pending_rotation = $this->read_pending_api_password_rotation();

		if ( $pending_rotation ) {
			if ( self::API_PASSWORD_ROTATION_REASON !== $pending_rotation['rotation_reason']
			|| ! $this->does_pending_api_password_rotation_match_persisted_credentials( $pending_rotation ) ) {
				return '';
			}

			if ( '' !== $pending_rotation['rotation_claim_token'] ) {
				if ( (int) $pending_rotation['expires_at'] > time() + self::API_PASSWORD_ROTATION_CHECKOUT_SAFETY_HORIZON ) {
					return $this->does_pending_api_password_rotation_match_persisted_credentials( $pending_rotation ) ? $pending_rotation['rotation_claim_token'] : '';
				}

				// Give every retained claim one chance to recover a late paid webhook before issuing
				// another. Claim creation is rate-limited so abandoned checkouts converge without
				// permitting unbounded signed-token churn.
				if ( $this->reconcile_pending_api_password_rotation( true ) ) {
					return '';
				}

				// The failed attempt durably advanced its backoff. Refresh from that exact generation
				// before preserving the old claim and compare-and-swapping a replacement token.
				$reconciled_pending_rotation = $this->read_pending_api_password_rotation();

				if ( ! $reconciled_pending_rotation
				|| ! hash_equals(
					$pending_rotation['new_api_password'],
					$reconciled_pending_rotation['new_api_password']
				)
				|| ! hash_equals(
					$pending_rotation['rotation_claim_token'],
					$reconciled_pending_rotation['rotation_claim_token']
				) ) {
					return '';
				}

				$pending_rotation = $reconciled_pending_rotation;

				if ( (int) $pending_rotation['claim_created_at'] + self::API_PASSWORD_ROTATION_CLAIM_REFRESH_INTERVAL > time() ) {
					return '';
				}
			}
		} else {
			// Create a candidate only from a fresh, complete pair that still matches this request's
			// runtime generation. A concurrent Account save wins and aborts this checkout attempt.
			$fresh_environmental_variables = $this->read_fresh_environmental_variables();

			if ( ! $fresh_environmental_variables ) {
				return '';
			}

			$api_username         = (string) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ];
			$current_api_password = (string) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ];

			if ( ! $this->is_non_empty_api_username_valid( $api_username )
				|| ! $this->is_non_empty_api_password_valid( $current_api_password )
				|| ! hash_equals( $api_username, $this->api_username )
				|| ! hash_equals( $current_api_password, $this->api_password ) ) {
				return '';
			}

			try {
				$new_api_password = bin2hex( random_bytes( 24 ) );
			} catch ( Exception $exception ) {
				unset( $exception );
				ai4seo_debug_message( 372708261, 'Could not generate a cryptographically secure API-password rotation candidate.', true );
				return '';
			}

			$pending_rotation = array(
				'version'                 => (string) self::API_PASSWORD_ROTATION_STATE_VERSION,
				'api_username'            => $api_username,
				'current_api_password'    => $current_api_password,
				'new_api_password'        => $new_api_password,
				'rotation_reason'         => self::API_PASSWORD_ROTATION_REASON,
				'rotation_claim_token'    => '',
				'expires_at'              => '0',
				'claim_created_at'        => '0',
				'prior_rotation_claims'   => array(),
				'next_reconciliation_at'  => '0',
				'reconciliation_attempts' => '0',
				'created_at'              => (string) time(),
			);

			// Persist the candidate before contacting RobHub. If the response is lost, the same
			// digest is used on the next preparation attempt instead of creating an orphaned secret.
			if ( ! $this->write_pending_api_password_rotation( $pending_rotation ) ) {
				return '';
			}

			$persisted_pending_rotation = $this->read_pending_api_password_rotation();

			if ( ! $persisted_pending_rotation
			|| ! hash_equals( $new_api_password, $persisted_pending_rotation['new_api_password'] ) ) {
				return '';
			}

			$pending_rotation = $persisted_pending_rotation;
		}

		if ( ! $this->does_pending_api_password_rotation_match_persisted_credentials( $pending_rotation ) ) {
			return '';
		}

		// Send RobHub only the candidate digest; the recoverable replacement remains local until rotation.
		$api_response = $this->call(
			self::PREPARE_API_PASSWORD_ROTATION_ENDPOINT,
			array(
				'new_api_password_digest' => hash( 'sha256', $pending_rotation['new_api_password'] ),
				'rotation_reason'         => self::API_PASSWORD_ROTATION_REASON,
			)
		);

		// Accept only the closed response shape needed to persist one transport-safe signed reference.
		if ( ! $this->was_call_successful( $api_response )
			|| ! isset( $api_response['data'] )
			|| ! $this->has_exact_array_keys(
				$api_response['data'],
				array( 'rotation_claim_token', 'expires_at' )
			) ) {
			return '';
		}

		$rotation_claim_token = $api_response['data']['rotation_claim_token'] ?? '';
		$expires_at           = $api_response['data']['expires_at'] ?? 0;

		$expires_at = is_int( $expires_at ) ? (string) $expires_at : $expires_at;
		if ( ! $this->is_api_password_rotation_claim_token_valid( $rotation_claim_token )
			|| ! $this->is_canonical_positive_integer_string( $expires_at )
			|| (string) (int) $expires_at !== $expires_at
			|| (int) $expires_at <= time() + self::API_PASSWORD_ROTATION_CHECKOUT_SAFETY_HORIZON ) {
			return '';
		}

		// A reconciliation may have completed immediately before the prepare request. Never
		// resurrect state that is no longer the authoritative pending generation.
		$current_pending_rotation = $this->read_pending_api_password_rotation();

		if ( ! $current_pending_rotation
			|| ! hash_equals( $pending_rotation['new_api_password'], $current_pending_rotation['new_api_password'] )
			|| ! $this->does_pending_api_password_rotation_match_persisted_credentials( $current_pending_rotation ) ) {
			return '';
		}

		$current_pending_rotation['rotation_claim_token']    = $rotation_claim_token;
		$current_pending_rotation['expires_at']              = (string) (int) $expires_at;
		$current_pending_rotation['claim_created_at']        = (string) time();
		$current_pending_rotation['next_reconciliation_at']  = (string) (
		time() + self::API_PASSWORD_ROTATION_RECONCILIATION_INITIAL_DELAY
		);
		$current_pending_rotation['reconciliation_attempts'] = '0';

		// Retain a bounded history because a late webhook may complete an older checkout generation.
		if ( '' !== $pending_rotation['rotation_claim_token']
		&& ! hash_equals( $pending_rotation['rotation_claim_token'], $rotation_claim_token ) ) {
			$prior_rotation_claims = $pending_rotation['prior_rotation_claims'];
			array_unshift(
				$prior_rotation_claims,
				array(
					'rotation_claim_token' => $pending_rotation['rotation_claim_token'],
					'expires_at'           => $pending_rotation['expires_at'],
					'claim_created_at'     => $pending_rotation['claim_created_at'],
				)
			);
			$current_pending_rotation['prior_rotation_claims'] = array_slice(
				$prior_rotation_claims,
				0,
				self::API_PASSWORD_ROTATION_MAX_PRIOR_CLAIMS
			);
		}

		// Publish the claim only by replacing the exact candidate generation used for its digest.
		if ( ! $this->write_pending_api_password_rotation( $current_pending_rotation, $pending_rotation ) ) {
			return '';
		}

		$persisted_pending_rotation = $this->read_pending_api_password_rotation();

		if ( ! isset( $persisted_pending_rotation['rotation_claim_token'] )
			|| ! hash_equals( $rotation_claim_token, $persisted_pending_rotation['rotation_claim_token'] )
			|| ! $this->does_pending_api_password_rotation_match_persisted_credentials( $persisted_pending_rotation ) ) {
			return '';
		}

		return $rotation_claim_token;
	}


	/**
	 * Prepare authenticated Stripe attribution for a paid client without a local subscription.
	 *
	 * @return string Exact signed attribution reference, or an empty string on failure.
	 */
	public function prepare_subscription_pricing_client_reference(): string {
		$api_response = $this->call(
			self::PREPARE_SUBSCRIPTION_PRICING_ENDPOINT,
			array(),
			'POST'
		);

		if ( ! $this->was_call_successful( $api_response )
			|| ! isset( $api_response['data'] ) ) {
			return '';
		}

		$validated_response_data = $this->validate_subscription_pricing_response_data(
			$api_response['data']
		);

		return (string) ( $validated_response_data['client_reference_id'] ?? '' );
	}


	/**
	 * Validate the closed authenticated subscription-pricing response contract.
	 *
	 * This runs before generic response sanitization so integer types remain authoritative.
	 *
	 * @param mixed $response_data Candidate response data.
	 * @return array Exact validated response or an empty array.
	 */
	private function validate_subscription_pricing_response_data( $response_data ): array {
		if ( ! $this->has_exact_array_keys(
			$response_data,
			array( 'version', 'client_reference_id', 'expires_at' )
		) ) {
			return array();
		}

		$client_reference_id = $response_data['client_reference_id'];
		$expires_at          = $response_data['expires_at'];

		if ( 1 !== $response_data['version']
			|| ! is_int( $expires_at )
			|| $expires_at <= time() + self::API_PASSWORD_ROTATION_CHECKOUT_SAFETY_HORIZON
			|| ! self::is_purchase_client_reference_valid( $client_reference_id )
			|| 1 !== preg_match( '/\Ar1a[A-Za-z0-9_-]+\z/D', $client_reference_id ) ) {
			return array();
		}

		return array(
			'version'             => 1,
			'client_reference_id' => $client_reference_id,
			'expires_at'          => $expires_at,
		);
	}


	/**
	 * Reconcile a pending API-password rotation through the public transition endpoint.
	 *
	 * The endpoint is idempotent and authenticates the transition from its explicit body. The
	 * current local password remains authoritative until RobHub strictly confirms the rotation
	 * and the replacement credential is durably promoted and verified.
	 *
	 * @param bool $force Whether a verified purchase signal may bypass the durable due time.
	 * @return bool Whether no complete transition was pending or reconciliation completed.
	 */
	public function reconcile_pending_api_password_rotation( bool $force = false ): bool {
		$pending_rotation = $this->read_pending_api_password_rotation();
		if ( ! $pending_rotation || '' === $pending_rotation['rotation_claim_token'] ) {
			$this->last_api_password_rotation_reconciliation_outcome = self::API_PASSWORD_ROTATION_RECONCILIATION_NOT_PENDING;
			return true;
		}

		if ( $this->has_attempted_api_password_rotation_reconciliation || $this->is_reconciling_api_password_rotation ) {
			return false;
		}

		$this->last_api_password_rotation_reconciliation_outcome = self::API_PASSWORD_ROTATION_RECONCILIATION_UNAVAILABLE;

		if ( ! $force
		&& (int) $pending_rotation['next_reconciliation_at'] > time()
		&& ! $this->are_api_password_rotation_credentials_entirely_missing() ) {
			return false;
		}

		$this->has_attempted_api_password_rotation_reconciliation = true;

		// Claim the network attempt with one exact durable transition. Concurrent PHP requests that
		// read the same due record cannot both probe all retained claims, and a lost response remains
		// retryable after the newly persisted cadence.
		$claimed_pending_rotation                            = $pending_rotation;
		$claimed_pending_rotation['reconciliation_attempts'] = (string) min(
			1000000,
			(int) $pending_rotation['reconciliation_attempts'] + 1
		);
		$claimed_pending_rotation['next_reconciliation_at']  = (string) (
		time() + $this->get_api_password_rotation_reconciliation_delay()
		);

		if ( ! $this->write_pending_api_password_rotation( $claimed_pending_rotation, $pending_rotation ) ) {
			return false;
		}

		$pending_rotation                           = $claimed_pending_rotation;
		$this->is_reconciling_api_password_rotation = true;
		$rotation_claim_tokens                      = array( $pending_rotation['rotation_claim_token'] );

		foreach ( $pending_rotation['prior_rotation_claims'] as $prior_rotation_claim ) {
			$rotation_claim_tokens[] = $prior_rotation_claim['rotation_claim_token'];
		}

		$is_rotation_confirmed = false;
		$all_claims_conflicted = true;

		try {
			foreach ( $rotation_claim_tokens as $rotation_claim_token ) {
				$api_response = $this->call(
					self::ROTATE_API_PASSWORD_ENDPOINT,
					array(
						'api_username'         => $pending_rotation['api_username'],
						'current_api_password' => $pending_rotation['current_api_password'],
						'new_api_password'     => $pending_rotation['new_api_password'],
						'rotation_claim_token' => $rotation_claim_token,
					),
					'POST',
					true
				);

				if ( $this->was_call_successful( $api_response )
					&& isset( $api_response['data'] )
					&& is_array( $api_response['data'] )
					&& isset( $api_response['data']['rotation_confirmed'] )
					&& true === $api_response['data']['rotation_confirmed'] ) {
					$is_rotation_confirmed = true;
					break;
				}

				// The server deliberately collapses a missing/unpaid checkout and a marker mismatch into
				// one generic conflict. Older retained claims may still find a completed purchase, but
				// only a later authenticated sync may authorize server-generated recovery.
				if ( ! isset( $api_response['code'] ) || self::API_PASSWORD_ROTATION_CONFLICT_ERROR_CODE !== (int) $api_response['code'] ) {
					$all_claims_conflicted = false;
					break;
				}
			}
		} finally {
			$this->is_reconciling_api_password_rotation = false;
		}

		if ( ! $is_rotation_confirmed ) {
			$this->last_api_password_rotation_reconciliation_outcome = $all_claims_conflicted ? self::API_PASSWORD_ROTATION_RECONCILIATION_CONFLICT : self::API_PASSWORD_ROTATION_RECONCILIATION_UNAVAILABLE;
			return false;
		}

		if ( $this->promote_confirmed_api_password_rotation( $pending_rotation ) ) {
			$this->last_api_password_rotation_reconciliation_outcome = self::API_PASSWORD_ROTATION_RECONCILIATION_CONFIRMED;
			return true;
		}

		// The server has already invalidated the old credential. A transient local CAS/read-back
		// failure must retry immediately instead of leaving ordinary requests on a six-hour cadence.
		$this->accelerate_pending_api_password_rotation_reconciliation();

		return false;
	}


	/**
	 * Make the next reconciliation immediately due after a verified local purchase action/return.
	 *
	 * @return bool Whether no pending claim exists or its exact due time was accelerated.
	 */
	public function accelerate_pending_api_password_rotation_reconciliation(): bool {
		return $this->accelerate_matching_pending_api_password_rotation_reconciliation();
	}


	/**
	 * Make one still-matching transition generation immediately due using exact option CAS.
	 *
	 * @param array $expected_generation Optional transition generation observed by the caller.
	 * @return bool Whether no pending claim exists or its exact due time was accelerated.
	 */
	private function accelerate_matching_pending_api_password_rotation_reconciliation(
		array $expected_generation = array()
	): bool {
		for ( $storage_attempt = 0; $storage_attempt < self::API_PASSWORD_ROTATION_STORAGE_MAX_ATTEMPTS; ++$storage_attempt ) {
			$pending_rotation = $this->read_pending_api_password_rotation();

			if ( ! $pending_rotation || '' === $pending_rotation['rotation_claim_token'] ) {
				return true;
			}

			if ( $expected_generation
			&& ! $this->are_pending_api_password_rotations_same_generation(
				$expected_generation,
				$pending_rotation
			) ) {
				return false;
			}

			if ( 0 === (int) $pending_rotation['next_reconciliation_at'] ) {
				return true;
			}

			$accelerated_rotation                            = $pending_rotation;
			$accelerated_rotation['next_reconciliation_at']  = '0';
			$accelerated_rotation['reconciliation_attempts'] = '0';

			if ( $this->write_pending_api_password_rotation( $accelerated_rotation, $pending_rotation ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Compare the immutable credential transition independently from claim/backoff refreshes.
	 *
	 * @param array $first_pending_rotation First valid pending state.
	 * @param array $second_pending_rotation Second valid pending state.
	 * @return bool Whether both records describe the same old-to-new credential generation.
	 */
	private function are_pending_api_password_rotations_same_generation(
		array $first_pending_rotation,
		array $second_pending_rotation
	): bool {
		return hash_equals( $first_pending_rotation['api_username'], $second_pending_rotation['api_username'] )
		&& hash_equals(
			$first_pending_rotation['current_api_password'],
			$second_pending_rotation['current_api_password']
		)
		&& hash_equals( $first_pending_rotation['new_api_password'], $second_pending_rotation['new_api_password'] )
		&& hash_equals( $first_pending_rotation['rotation_reason'], $second_pending_rotation['rotation_reason'] );
	}


	/**
	 * Select a bounded retry cadence from the existing recent-purchase signal.
	 *
	 * @return int Delay in seconds before another public rotation probe.
	 */
	private function get_api_password_rotation_reconciliation_delay(): int {
		$recent_purchase_at = 0;

		if ( defined( 'AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME' ) ) {
			$recent_purchase_at = (int) $this->read_environmental_variable(
				AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME
			);
		}

		if ( $recent_purchase_at > time() - 600 ) {
			return self::API_PASSWORD_ROTATION_RECONCILIATION_FAST_DELAY;
		}

		if ( $recent_purchase_at > time() - 7200 ) {
			return self::API_PASSWORD_ROTATION_RECONCILIATION_MEDIUM_DELAY;
		}

		return self::API_PASSWORD_ROTATION_RECONCILIATION_SLOW_DELAY;
	}


	/**
	 * Determine whether pending recovery belongs to the credentials selected for this call.
	 *
	 * RobHub Control sometimes selects a temporary client pair without persisting it. That pair
	 * must remain authoritative for its one call even if this WordPress installation has its own
	 * pending rotation. Empty runtime credentials may be initialized from local storage first.
	 *
	 * @return bool Whether automatic reconciliation applies to the current runtime pair.
	 */
	private function should_automatically_reconcile_api_password_rotation(): bool {
		$pending_rotation = $this->read_pending_api_password_rotation();

		if ( ! $pending_rotation || ! $this->has_applicable_pending_api_password_rotation() ) {
			return false;
		}

		return (int) $pending_rotation['next_reconciliation_at'] <= time()
		|| $this->are_api_password_rotation_credentials_entirely_missing();
	}


	/**
	 * Detect a restore that kept recovery state but lost the shared credential option.
	 *
	 * A durable retry delay protects an intact old credential from probing an abandoned checkout
	 * on every request. It cannot delay repair when there is no credential with which the ordinary
	 * request could proceed, so this check deliberately uses a fresh raw option snapshot.
	 *
	 * @return bool Whether both runtime and durable API credentials are entirely absent.
	 */
	private function are_api_password_rotation_credentials_entirely_missing(): bool {
		if ( '' !== $this->api_username || '' !== $this->api_password ) {
			return false;
		}

		$environmental_snapshot = ai4seo_get_raw_option_snapshot( $this->environmental_variables_option_name );

		if ( ! is_array( $environmental_snapshot ) ) {
			return false;
		}

		if ( ! $environmental_snapshot['exists'] ) {
			return true;
		}

		if ( ! is_array( $environmental_snapshot['value'] ) ) {
			return false;
		}

		$environmental_variables = $this->normalize_environmental_variables( $environmental_snapshot['value'] );

		return '' === (string) $environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ]
		&& '' === (string) $environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ];
	}


	/**
	 * Match valid recovery state to the pair currently selected for transport.
	 *
	 * @param array $pending_rotation Valid pending state.
	 * @return bool Whether the runtime pair is one recoverable generation.
	 */
	private function does_pending_api_password_rotation_match_runtime_credentials( array $pending_rotation ): bool {
		return hash_equals( $pending_rotation['api_username'], $this->api_username )
		&& ( hash_equals( $pending_rotation['current_api_password'], $this->api_password )
			|| hash_equals( $pending_rotation['new_api_password'], $this->api_password ) );
	}


	/**
	 * Verify that the pair which received an auth failure is still the persisted generation.
	 *
	 * Another request may have promoted a confirmed rotation after this request sent its old
	 * Authorization header. Such a stale failure must not clear the newly verified credential.
	 *
	 * @return bool Whether runtime and fresh persistent credentials match exactly.
	 */
	private function do_runtime_credentials_match_fresh_persisted_credentials(): bool {
		$fresh_environmental_variables = $this->read_fresh_environmental_variables();

		return $fresh_environmental_variables
		&& hash_equals(
			$this->api_username,
			(string) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ]
		)
		&& hash_equals(
			$this->api_password,
			(string) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ]
		);
	}


	/**
	 * Verify that pending state is associated with the currently persisted credential pair.
	 *
	 * @param array $pending_rotation Valid pending state.
	 * @return bool Whether the stored credential is the old or already-promoted generation.
	 */
	private function does_pending_api_password_rotation_match_persisted_credentials( array $pending_rotation ): bool {
		$fresh_environmental_variables = $this->read_fresh_environmental_variables();

		if ( ! $fresh_environmental_variables ) {
			return false;
		}

		$api_username = (string) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ];
		$api_password = (string) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ];

		return $this->is_non_empty_api_username_valid( $api_username )
			&& $this->is_non_empty_api_password_valid( $api_password )
			&& hash_equals( $pending_rotation['api_username'], $api_username )
			&& ( hash_equals( $pending_rotation['current_api_password'], $api_password )
				|| hash_equals( $pending_rotation['new_api_password'], $api_password ) );
	}


	/**
	 * Promote a server-confirmed replacement credential with an exact shared-option merge.
	 *
	 * @param array $pending_rotation Confirmed pending state.
	 * @return bool Whether the replacement credential was durably promoted and verified.
	 */
	private function promote_confirmed_api_password_rotation( array $pending_rotation ): bool {
		for ( $storage_attempt = 0; $storage_attempt < self::API_PASSWORD_ROTATION_STORAGE_MAX_ATTEMPTS; ++$storage_attempt ) {
			$environmental_snapshot = ai4seo_get_raw_option_snapshot( $this->environmental_variables_option_name );

			if ( ! is_array( $environmental_snapshot )
				|| ( $environmental_snapshot['exists'] && ! is_array( $environmental_snapshot['value'] ) ) ) {
				return false;
			}

			$current_environmental_variables = $this->normalize_environmental_variables(
				$environmental_snapshot['exists'] ? $environmental_snapshot['value'] : array()
			);
			$current_api_username            = (string) $current_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ];
			$current_api_password            = (string) $current_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ];

			$environmental_credentials_are_missing = '' === $current_api_username
				&& '' === $current_api_password;

			if ( ! $environmental_credentials_are_missing
				&& ( ! hash_equals( $pending_rotation['api_username'], $current_api_username )
				|| ( ! hash_equals( $pending_rotation['current_api_password'], $current_api_password )
					&& ! hash_equals( $pending_rotation['new_api_password'], $current_api_password ) ) ) ) {
				return false;
			}

			$promoted_environmental_variables = $current_environmental_variables;
			$promoted_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ]      = $pending_rotation['api_username'];
			$promoted_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ]      = $pending_rotation['new_api_password'];
			$promoted_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED ]    = false;
			$promoted_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC ] = 0;
			$promoted_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED ] = false;
			$promoted_environmental_overrides = $this->build_environmental_variable_overrides( $promoted_environmental_variables );

			if ( 'no' === $environmental_snapshot['autoload']
				&& hash_equals(
					(string) maybe_serialize( $promoted_environmental_overrides ),
					$environmental_snapshot['raw_value']
				) ) {
				$verified_snapshot = $environmental_snapshot;
			} else {
				$write_result = $this->compare_and_swap_environmental_variables_snapshot(
					$environmental_snapshot,
					$promoted_environmental_overrides
				);

				if ( null === $write_result ) {
					return false;
				}

				$verified_snapshot = ai4seo_get_raw_option_snapshot( $this->environmental_variables_option_name );
			}

			if ( ! is_array( $verified_snapshot )
				|| ! $verified_snapshot['exists']
				|| 'no' !== $verified_snapshot['autoload']
				|| ! hash_equals(
					(string) maybe_serialize( $promoted_environmental_overrides ),
					$verified_snapshot['raw_value']
				)
				|| $promoted_environmental_overrides !== $verified_snapshot['value'] ) {
				continue;
			}

			$this->environmental_variables = $this->normalize_environmental_variables( $verified_snapshot['value'] );
			$this->synchronize_runtime_credentials_from_environmental_variables();

			return hash_equals( $pending_rotation['api_username'], $this->api_username )
				&& hash_equals( $pending_rotation['new_api_password'], $this->api_password )
				&& ! (bool) $this->environmental_variables[ self::ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED ];
		}

		return false;
	}


	/**
	 * Exact environmental-option write seam used by credential promotion.
	 *
	 * @param array $expected_snapshot Exact raw environmental-option snapshot.
	 * @param array $replacement_environmental_variables Non-default values with promoted credentials merged in.
	 * @return bool|null True on success, false on a lost race, or null on storage failure.
	 */
	protected function compare_and_swap_environmental_variables_snapshot(
		array $expected_snapshot,
		array $replacement_environmental_variables
	): ?bool {
		return ai4seo_compare_and_swap_option_snapshot(
			$this->environmental_variables_option_name,
			$expected_snapshot,
			$replacement_environmental_variables,
			false
		);
	}


	/**
	 * Read complete environmental values from a fresh exact non-autoloaded snapshot.
	 *
	 * @return array Complete validated values, or an empty array on storage/schema failure.
	 */
	private function read_fresh_environmental_variables(): array {
		$environmental_snapshot = ai4seo_get_raw_option_snapshot( $this->environmental_variables_option_name );

		if ( ! is_array( $environmental_snapshot )
			|| ! $environmental_snapshot['exists']
			|| ! $this->is_non_autoload_option_value( $environmental_snapshot['autoload'] )
			|| ! is_array( $environmental_snapshot['value'] ) ) {
			return array();
		}

		return $this->normalize_environmental_variables( $environmental_snapshot['value'] );
	}


	/**
	 * Remove confirmed recovery material only after a real request authenticated with the new pair.
	 *
	 * This method is called exclusively after a non-public API request succeeds. Runtime credentials
	 * identify the pair used to build that request; a fresh option snapshot then prevents cached reads
	 * from authorizing deletion after a stale environmental write.
	 *
	 * @return bool Whether no eligible cleanup was needed or exact cleanup succeeded.
	 */
	private function maybe_finalize_api_password_rotation_after_authenticated_success(): bool {
		$pending_rotation = $this->read_pending_api_password_rotation();

		if ( ! $pending_rotation
			|| ! hash_equals( $pending_rotation['api_username'], $this->api_username )
			|| ! hash_equals( $pending_rotation['new_api_password'], $this->api_password ) ) {
			return true;
		}

		$fresh_environmental_variables = $this->read_fresh_environmental_variables();

		if ( ! $fresh_environmental_variables
			|| ! hash_equals(
				$pending_rotation['api_username'],
				(string) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ]
			)
			|| ! hash_equals(
				$pending_rotation['new_api_password'],
				(string) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ]
			)
			|| (bool) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED ] ) {
			return false;
		}

		return $this->delete_pending_api_password_rotation( $pending_rotation );
	}


	/**
	 * Retire a stale transition only after an explicit replacement pair authenticated.
	 *
	 * The fresh option read proves the pair used by changed-api-user is still authoritative;
	 * a concurrent reconnect wins without being overwritten or losing its own pending state.
	 *
	 * @return bool Whether no stale generation remained or exact cleanup succeeded.
	 */
	private function finalize_pending_rotation_after_authenticated_reconnect(): bool {
		$fresh_environmental_variables = $this->read_fresh_environmental_variables();

		if ( ! $fresh_environmental_variables
			|| ! hash_equals(
				$this->api_username,
				(string) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ]
			)
			|| ! hash_equals(
				$this->api_password,
				(string) $fresh_environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ]
			) ) {
			return false;
		}

		// Repair a structurally valid autoloaded row before classifying the remaining transition.
		$this->read_pending_api_password_rotation();
		$inspection = $this->inspect_pending_api_password_rotation();

		if ( 'missing' === $inspection['state'] ) {
			return true;
		}

		if ( 'valid' !== $inspection['state'] ) {
			return false;
		}

		$pending_rotation      = $inspection['value'];
		$is_pending_generation = hash_equals( $pending_rotation['api_username'], $this->api_username )
			&& ( hash_equals( $pending_rotation['current_api_password'], $this->api_password )
				|| hash_equals( $pending_rotation['new_api_password'], $this->api_password ) );

		return $is_pending_generation || $this->delete_pending_api_password_rotation( $pending_rotation );
	}


	/**
	 * Validate a non-empty API username through the canonical environmental-value contract.
	 *
	 * @param mixed $api_username Candidate API username.
	 * @return bool Whether the username is a complete credential component.
	 */
	private function is_non_empty_api_username_valid( $api_username ): bool {
		return is_string( $api_username )
			&& '' !== $api_username
			&& $this->validate_environmental_variable_value(
				self::ENVIRONMENTAL_VARIABLE_API_USERNAME,
				$api_username
			);
	}


	/**
	 * Validate a non-empty API password through the canonical environmental-value contract.
	 *
	 * @param mixed $api_password Candidate API password.
	 * @return bool Whether the password is a complete credential component.
	 */
	private function is_non_empty_api_password_valid( $api_password ): bool {
		return is_string( $api_password )
			&& '' !== $api_password
			&& $this->validate_environmental_variable_value(
				self::ENVIRONMENTAL_VARIABLE_API_PASSWORD,
				$api_password
			);
	}


	/**
	 * Validate a locally generated hexadecimal replacement password.
	 *
	 * The general API-password contract deliberately accepts legacy underscore and dash characters;
	 * a generated candidate remains hex-only so its random-byte encoding is independently verifiable.
	 *
	 * @param mixed $api_password Candidate replacement password.
	 * @return bool Whether the value is an exact 24-byte hexadecimal encoding.
	 */
	private function is_api_password_rotation_candidate_valid( $api_password ): bool {
		return is_string( $api_password )
			&& 1 === preg_match( '/^[a-f0-9]{48}$/', $api_password );
	}


	/**
	 * Validate an opaque reference before transporting it through pricing and Stripe checkout.
	 *
	 * The same byte contract accepts signed first-purchase claims and legacy client usernames.
	 * Empty legacy attribution is handled by the caller and is not a valid transported reference.
	 *
	 * @param mixed $purchase_client_reference Candidate purchase attribution reference.
	 * @return bool Whether the reference can be transported without normalization.
	 */
	public static function is_purchase_client_reference_valid( $purchase_client_reference ): bool {
		return is_string( $purchase_client_reference )
			&& '' !== $purchase_client_reference
			&& strlen( $purchase_client_reference ) <= self::API_PASSWORD_ROTATION_CLAIM_MAX_LENGTH
			&& sanitize_text_field( $purchase_client_reference ) === $purchase_client_reference
			&& 1 === preg_match( '/\A[A-Za-z0-9_-]+\z/D', $purchase_client_reference );
	}


	/**
	 * Validate one signed API-password rotation claim without normalizing it.
	 *
	 * @param mixed $rotation_claim_token Candidate claim.
	 * @return bool Whether the claim is an exact printable ASCII token.
	 */
	private function is_api_password_rotation_claim_token_valid( $rotation_claim_token ): bool {
		return self::is_purchase_client_reference_valid( $rotation_claim_token );
	}


	/**
	 * Validate the exact durable pending API-password rotation schema.
	 *
	 * @param mixed $pending_rotation Candidate state.
	 * @return bool Whether the state is empty or an exact valid transition record.
	 */
	private function is_pending_api_password_rotation_state_valid( $pending_rotation ): bool {
		// The empty array is the only valid absence sentinel; all durable generations are exact maps.
		if ( array() === $pending_rotation ) {
			return true;
		}

		if ( ! is_array( $pending_rotation ) ) {
			return false;
		}

		// Refuse schema drift so partially written or future-version state can never authorize recovery.
		$expected_keys = array(
			'version',
			'api_username',
			'current_api_password',
			'new_api_password',
			'rotation_reason',
			'rotation_claim_token',
			'expires_at',
			'claim_created_at',
			'prior_rotation_claims',
			'next_reconciliation_at',
			'reconciliation_attempts',
			'created_at',
		);
		$actual_keys   = array_keys( $pending_rotation );
		sort( $expected_keys );
		sort( $actual_keys );

		// Validate immutable credentials and bounded timestamps before interpreting claim lifecycle state.
		if ( $expected_keys !== $actual_keys
			|| ! is_string( $pending_rotation['version'] )
			|| (string) self::API_PASSWORD_ROTATION_STATE_VERSION !== $pending_rotation['version']
			|| ! $this->is_non_empty_api_username_valid( $pending_rotation['api_username'] )
			|| ! $this->is_non_empty_api_password_valid( $pending_rotation['current_api_password'] )
			|| ! $this->is_api_password_rotation_candidate_valid( $pending_rotation['new_api_password'] )
			|| hash_equals( $pending_rotation['current_api_password'], $pending_rotation['new_api_password'] )
			|| self::API_PASSWORD_ROTATION_REASON !== $pending_rotation['rotation_reason']
			|| ! is_string( $pending_rotation['rotation_claim_token'] )
			|| ! is_string( $pending_rotation['expires_at'] )
			|| ! ctype_digit( $pending_rotation['expires_at'] )
			|| ! is_string( $pending_rotation['claim_created_at'] )
			|| ! ctype_digit( $pending_rotation['claim_created_at'] )
			|| ! is_array( $pending_rotation['prior_rotation_claims'] )
			|| count( $pending_rotation['prior_rotation_claims'] ) > self::API_PASSWORD_ROTATION_MAX_PRIOR_CLAIMS
			|| ! is_string( $pending_rotation['next_reconciliation_at'] )
			|| ! ctype_digit( $pending_rotation['next_reconciliation_at'] )
			|| ! is_string( $pending_rotation['reconciliation_attempts'] )
			|| ! ctype_digit( $pending_rotation['reconciliation_attempts'] )
			|| (int) $pending_rotation['reconciliation_attempts'] > 1000000
			|| ! is_string( $pending_rotation['created_at'] )
			|| ! ctype_digit( $pending_rotation['created_at'] )
			|| (int) $pending_rotation['created_at'] <= 0 ) {
			return false;
		}

		// Candidate persistence precedes claim issuance, so its claim-specific fields must remain empty.
		if ( '' === $pending_rotation['rotation_claim_token'] ) {
			return 0 === (int) $pending_rotation['expires_at']
				&& 0 === (int) $pending_rotation['claim_created_at']
				&& array() === $pending_rotation['prior_rotation_claims']
				&& 0 === (int) $pending_rotation['next_reconciliation_at']
				&& 0 === (int) $pending_rotation['reconciliation_attempts'];
		}

		// A complete generation requires one current transport-safe claim with positive issuance metadata.
		if ( (int) $pending_rotation['expires_at'] <= 0
			|| (int) $pending_rotation['claim_created_at'] <= 0
			|| ! $this->is_api_password_rotation_claim_token_valid( $pending_rotation['rotation_claim_token'] ) ) {
			return false;
		}

		$seen_rotation_claim_tokens = array( $pending_rotation['rotation_claim_token'] => true );

		// Retained claims must be unique, exact historical records for the same credential generation.
		foreach ( $pending_rotation['prior_rotation_claims'] as $prior_rotation_claim ) {
			if ( ! is_array( $prior_rotation_claim )
				|| array_keys( $prior_rotation_claim ) !== array( 'rotation_claim_token', 'expires_at', 'claim_created_at' )
				|| ! $this->is_api_password_rotation_claim_token_valid( $prior_rotation_claim['rotation_claim_token'] )
				|| isset( $seen_rotation_claim_tokens[ $prior_rotation_claim['rotation_claim_token'] ] )
				|| ! is_string( $prior_rotation_claim['expires_at'] )
				|| ! ctype_digit( $prior_rotation_claim['expires_at'] )
				|| (int) $prior_rotation_claim['expires_at'] <= 0
				|| ! is_string( $prior_rotation_claim['claim_created_at'] )
				|| ! ctype_digit( $prior_rotation_claim['claim_created_at'] )
				|| (int) $prior_rotation_claim['claim_created_at'] <= 0 ) {
				return false;
			}

			$seen_rotation_claim_tokens[ $prior_rotation_claim['rotation_claim_token'] ] = true;
		}

		return true;
	}


	/**
	 * Function to read the auth data from the environmental variables.
	 *
	 * @return array The auth data.
	 */
	public function read_auth_data(): array {
		$api_username = $this->read_environmental_variable( self::ENVIRONMENTAL_VARIABLE_API_USERNAME );
		$api_password = $this->read_environmental_variable( self::ENVIRONMENTAL_VARIABLE_API_PASSWORD );

		return array( $api_username, $api_password );
	}


	/**
	 * Determine whether authentication state changes are locked.
	 *
	 * @return bool Whether authentication data is locked.
	 */
	public function is_auth_data_locked(): bool {
		return (bool) $this->read_environmental_variable( self::ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED );
	}


	/**
	 * Store the authentication-data lock state.
	 *
	 * @param bool $is_auth_locked Whether authentication data is locked.
	 * @return bool Whether the value was stored successfully.
	 */
	public function set_auth_data_locked( bool $is_auth_locked ): bool {
		return $this->update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED, $is_auth_locked );
	}


	/**
	 * We build an api username for a free account based on the domain name.
	 *
	 * @param string $base_username The base username to use (optional).
	 * @return string The potential new api username
	 */
	public function build_api_username( string $base_username = '' ): string {
		if ( ! $base_username ) {
			// WordPress Playground stores a UUID in this option through the official
			// Blueprint. Prefer it over the shared Playground server name so every
			// disposable Playground installation receives its own free account.
			$playground_instance_id = get_option( 'ai4seo_playground_demo_instance_id', '' );

			if ( is_string( $playground_instance_id ) && wp_is_uuid( strtolower( $playground_instance_id ) ) ) {
				// Keep the readable UUID prefix while hashing the complete ID before the shared 32-character truncation.
				$normalized_playground_instance_id = strtolower( $playground_instance_id );
				$playground_instance_hash          = hash( 'sha256', $normalized_playground_instance_id );
				$base_username                     = 'playground-' . substr( $normalized_playground_instance_id, 0, 8 ) . '-' . substr( $playground_instance_hash, 0, 12 );
			} else {
				$base_username = $this->get_server_identity();
			}
		}

		// remove schema.
		$base_username = str_replace( 'http://', '', $base_username );
		$base_username = str_replace( 'https://', '', $base_username );
		// remove port.
		$base_username = explode( ':', $base_username )[0];
		// remove www.
		$base_username = str_replace( 'www.', '', $base_username );

		// replace dots with dashes.
		$base_username = str_replace( '.', '-', $base_username );

		// replace duplicate dashes with a single dash.
		$base_username = preg_replace( '/-+/', '-', $base_username );

		// remove leading and trailing dashes.
		$base_username = trim( $base_username, '-' );

		// remove all non-alphanumeric characters.
		$base_username = preg_replace( '/[^a-zA-Z0-9\-]/', '', $base_username );

		// fallback: use random if still empty or shorter than 3 -> generate a random pseudo api username.
		if ( ! $base_username || strlen( $base_username ) <= 3 ) {
			$base_username = $this->generate_random_pseudo_api_username();
		}

		// lowercase.
		$base_username = strtolower( $base_username );

		// use the first 32 chars of the base_username and then add 6 chars of the md5 hash.
		$base_username = substr( $base_username, 0, 32 );
		$md5_hash      = md5( $base_username . $this->product );

		// use the first 6 chars of the md5 hash.
		$base_username .= '-' . substr( $md5_hash, 0, 6 );

		// generate md5 hash.
		return $base_username;
	}


	/**
	 * Build a stable identity for the current server.
	 *
	 * @return string A reliable server identity based on the server name, address, hostname or site URL.
	 */
	public function get_server_identity(): string {
		$identifier = '';
		// Normalize server-provided identity candidates once before applying the fallback order.
		$server_name = '';
		$server_addr = '';

		if ( isset( $_SERVER['SERVER_NAME'] ) && is_string( $_SERVER['SERVER_NAME'] ) ) {
			$server_name = sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) );
		}

		if ( isset( $_SERVER['SERVER_ADDR'] ) && is_string( $_SERVER['SERVER_ADDR'] ) ) {
			$server_addr = sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) );
		}

		// Check SERVER_NAME.
		if ( '' !== $server_name && strlen( $server_name ) >= 3 ) {
			$identifier = $server_name;
		} elseif ( '' !== $server_addr && strlen( $server_addr ) >= 3 ) {
			// Fallback: SERVER_ADDR.
			$identifier = $server_addr;
		} elseif ( function_exists( 'gethostname' ) && is_string( gethostname() ) && strlen( gethostname() ) >= 3 ) {
			// Fallback: gethostname().
			$identifier = gethostname();
		} else {
			// Fallback: WordPress site URL.
			$site_url = ai4seo_get_option( 'siteurl' );
			if ( is_string( $site_url ) && strlen( $site_url ) >= 3 ) {
				$identifier = $site_url;
			}
		}

		// Final fallback.
		if ( empty( $identifier ) ) {
			$identifier = '';
		}

		// Calculate hash and determine group.
		return $identifier;
	}


	/**
	 * Function to sync with client's RobHub Account
	 *
	 * @param string $sync_reason The reason for the sync (optional).
	 * @return array $api_response if the RobHub Account was synced, false on error
	 */
	public function sync_account( string $sync_reason = 'unknown' ): array {
		$sync_reason = sanitize_key( $sync_reason );

		// Send WordPress' native underscore locale so RobHub can resolve exact regional mappings.
		$parameters = array(
			'reason'             => $sync_reason,
			'wordpress_language' => sanitize_text_field( get_locale() ),
		);

		// Preserve the established override order for general context and the detailed sync report.
		$client_context_parameters     = $this->build_client_context_parameters();
		$client_sync_report_parameters = $this->build_client_sync_report_parameters();
		$parameters                    = array_merge( $parameters, $client_context_parameters, $client_sync_report_parameters );

		// Submit the complete sync context through the communicator's established request path.
		$api_response = self::call( 'client/sync', $parameters );

		// Interpret response & check data payload.
		if ( ! self::was_call_successful( $api_response ) || ! isset( $api_response['data'] ) || ! is_array( $api_response['data'] ) || ! $api_response['data'] ) {
			$is_auth_lock_response = is_array( $api_response )
			&& isset( $api_response['code'] )
			&& $this->is_auth_lock_error_code( $api_response['code'] );

			// Authentication failures already reset sync state in their atomic credential-and-lock update.
			if ( ! $is_auth_lock_response ) {
				self::update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED, false );
			}

			return is_array( $api_response ) ? $api_response : array(
				'success' => false,
				'message' => 'Failed to sync account: ' . ( is_string( $api_response ) ? $api_response : 'Unknown error' ),
				'code'    => 461220825,
			);
		}

		$synced_account_data = $api_response['data'];
		$rotation_state      = $this->validate_synced_api_password_rotation_state(
			$synced_account_data['api_password_rotation'] ?? null
		);

		if ( ! $rotation_state ) {
			self::update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED, false );
			return $this->respond_error( 'API password rotation state is unavailable or invalid.', 27082720 );
		}

		$rotation_reconciliation_error = $this->reconcile_synced_api_password_rotation_state( $rotation_state );

		if ( $rotation_reconciliation_error ) {
			self::update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED, false );
			return $rotation_reconciliation_error;
		}

		// next free credits.
		$next_free_credits_countdown = (int) ( $synced_account_data['next_free_credits_countdown'] ?? 0 );
		self::update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP, time() + $next_free_credits_countdown );

		// Persist only canonical string groups so loosely equivalent server values cannot select a cohort.
		if (
		isset( $synced_account_data['group'] )
		&& is_string( $synced_account_data['group'] )
		&& in_array( $synced_account_data['group'], array( 'a', 'b', 'c', 'd', 'e', 'f' ), true )
		) {
			$group = $synced_account_data['group'];
		}

		self::update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_GROUP, $group ?? 'x' );

		// subscription.
		if ( isset( $synced_account_data['plan'] ) && 'free' !== $synced_account_data['plan'] ) {
			// build subscription array, base on.
			$subscription = array(
				'plan'                 => $synced_account_data['plan'],
				'subscription_start'   => $synced_account_data['subscription_start'] ?? '',
				'subscription_end'     => $synced_account_data['subscription_end'] ?? '',
				'next_credits_refresh' => $synced_account_data['next_credits_refresh'] ?? '',
				'do_renew'             => (bool) ( $synced_account_data['do_renew'] ?? false ),
				'renew_frequency'      => $synced_account_data['renew_frequency'] ?? 'monthly',
			);

			self::update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION, $subscription );
		} else {
			self::delete_environmental_variable( self::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION );
		}

		// set the last account sync time.
		self::update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC, time() );
		self::update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED, true );

		return $api_response;
	}


	/**
	 * Determine whether every local purchase reference has passed its late-webhook safety horizon.
	 *
	 * @param array $pending_rotation Valid pending state.
	 * @return bool Whether authenticated server absence may retire this exact generation.
	 */
	private function has_pending_api_password_rotation_abandonment_horizon_passed(
		array $pending_rotation
	): bool {
		if ( ! $this->is_pending_api_password_rotation_state_valid( $pending_rotation )
		|| array() === $pending_rotation ) {
			return false;
		}

		$latest_claim_expiry = (int) $pending_rotation['expires_at'];

		foreach ( $pending_rotation['prior_rotation_claims'] as $prior_rotation_claim ) {
			$latest_claim_expiry = max( $latest_claim_expiry, (int) $prior_rotation_claim['expires_at'] );
		}

		if ( $latest_claim_expiry > 0 ) {
			return $latest_claim_expiry <= time() - self::API_PASSWORD_ROTATION_EXPIRED_CLAIM_GRACE_PERIOD;
		}

		return (int) $pending_rotation['created_at']
		<= time() - self::API_PASSWORD_ROTATION_UNPUBLISHED_RETENTION;
	}


	/**
	 * Validate the closed non-secret rotation state returned by account sync.
	 *
	 * @param mixed $rotation_state Untrusted sync field.
	 * @return array Valid state, or an empty array.
	 */
	private function validate_synced_api_password_rotation_state( $rotation_state ): array {
		if ( ! $this->has_exact_array_keys(
			$rotation_state,
			array( 'version', 'state', 'email_status' )
		)
			|| 1 !== ( $rotation_state['version'] ?? null )
			|| ! is_string( $rotation_state['state'] ?? null )
			|| ! is_string( $rotation_state['email_status'] ?? null ) ) {
			return array();
		}

		$state              = $rotation_state['state'];
		$email_status       = $rotation_state['email_status'];
		$valid_combinations = array(
			'none'      => array( 'not-applicable' ),
			'required'  => array( 'pending' ),
			'completed' => array( 'pending', 'failed', 'sent' ),
			'conflict'  => array( 'not-applicable' ),
		);

		if ( ! isset( $valid_combinations[ $state ] )
			|| ! in_array( $email_status, $valid_combinations[ $state ], true ) ) {
			return array();
		}

		return array(
			'version'      => 1,
			'state'        => $state,
			'email_status' => $email_status,
		);
	}


	/**
	 * Reconcile local secret state against authenticated non-secret server state.
	 *
	 * @param array $rotation_state Validated server state.
	 * @return array Empty on safe continuation, otherwise a normalized error response.
	 */
	private function reconcile_synced_api_password_rotation_state( array $rotation_state ): array {
		$server_state = $rotation_state['state'];

		if ( 'conflict' === $server_state ) {
			return $this->respond_error( 'api password rotation conflict', 27082706 );
		}

		// This read also exact-CAS repairs a structurally valid row that WordPress autoloaded.
		$this->read_pending_api_password_rotation();
		$inspection = $this->inspect_pending_api_password_rotation();

		if ( in_array( $inspection['state'], array( 'unavailable', 'repairable' ), true ) ) {
			return $this->respond_error( 'api password rotation conflict', 27082706 );
		}

		if ( 'none' === $server_state ) {
			if ( 'invalid' === $inspection['state']
				&& ! $this->delete_pending_api_password_rotation_snapshot( $inspection['snapshot'] ) ) {
				return $this->respond_error( 'api password rotation conflict', 27082706 );
			}

			// Authenticated absence is safe to act on only after every checkout reference has also
			// crossed the local late-webhook horizon. Exact deletion prevents a stale sync from
			// removing a refreshed candidate generation written by another request.
			if ( 'valid' === $inspection['state']
				&& $this->has_pending_api_password_rotation_abandonment_horizon_passed( $inspection['value'] )
				&& ! $this->delete_pending_api_password_rotation( $inspection['value'] ) ) {
				return $this->respond_error( 'api password rotation conflict', 27082706 );
			}

			return array();
		}

		if ( 'completed' === $server_state ) {
			if ( 'invalid' === $inspection['state'] ) {
				return $this->delete_pending_api_password_rotation_snapshot( $inspection['snapshot'] )
				? array()
				: $this->respond_error( 'api password rotation conflict', 27082706 );
			}

			if ( 'valid' === $inspection['state'] ) {
				// This sync authenticated the runtime pair. A completed server state is consistent with
				// the retained local transition only when that pair is its replacement generation.
				if ( ! hash_equals( $inspection['value']['api_username'], $this->api_username )
				|| ! hash_equals( $inspection['value']['new_api_password'], $this->api_password )
				|| ! $this->delete_pending_api_password_rotation( $inspection['value'] ) ) {
					return $this->respond_error( 'api password rotation conflict', 27082706 );
				}
			}

			return array();
		}

		if ( 'required' !== $server_state ) {
			return $this->respond_error( 'api password rotation conflict', 27082706 );
		}

		if ( 'valid' === $inspection['state'] ) {
			if ( '' !== $inspection['value']['rotation_claim_token'] && $this->reconcile_pending_api_password_rotation( true ) ) {
				// The replacement credential was promoted after this sync authenticated with the old pair.
				// A fresh sync must verify it before ordinary account state is marked current.
				return $this->respond_error( 'Credential rotation completed. Please refresh licence status.', 27082722 );
			}

			$claim_reconciliation_outcome = '' === $inspection['value']['rotation_claim_token'] ? self::API_PASSWORD_ROTATION_RECONCILIATION_NOT_PENDING : $this->last_api_password_rotation_reconciliation_outcome;
			// Transport, storage, and malformed-response failures retain every local secret and
			// claim. The authenticated required marker is what permits a generic all-claim conflict
			// to fall back to server-generated recovery.
			if ( ! in_array( $claim_reconciliation_outcome, array( self::API_PASSWORD_ROTATION_RECONCILIATION_CONFLICT, self::API_PASSWORD_ROTATION_RECONCILIATION_NOT_PENDING ), true ) ) {
				return $this->respond_error( 'Credential rotation is awaiting secure reconciliation.', 27082721 );
			}

			if ( ! $this->transition_pending_rotation_to_server_recovery( $inspection['value'] ) ) {
				return $this->respond_error( 'Credential recovery is awaiting secure confirmation. Please check your licence email before reconnecting.', 27082721 );
			}

			return $this->respond_error( 'Your replacement licence key is being delivered to the verified checkout email. Please reconnect from Account settings.', 27082721 );
		}

		if ( 'invalid' === $inspection['state']
			&& ! $this->delete_pending_api_password_rotation_snapshot( $inspection['snapshot'] ) ) {
			return $this->respond_error( 'api password rotation conflict', 27082706 );
		}

		if ( ! in_array( $inspection['state'], array( 'missing', 'invalid' ), true )
			|| ! $this->ensure_api_password_rotation_recovery_intent() ) {
			return $this->respond_error( 'api password rotation conflict', 27082706 );
		}

		if ( ! $this->reconcile_api_password_rotation_recovery_intent() ) {
			return $this->respond_error(
				'Credential recovery is awaiting secure confirmation. Please check your licence email before reconnecting.',
				27082721
			);
		}

		return $this->respond_error( 'Your replacement licence key is being delivered to the verified checkout email. Please reconnect from Account settings.', 27082721 );
	}


	/**
	 * Convert one exact stale candidate into durable server-generated recovery.
	 *
	 * The non-secret intent is committed first. The secret-bearing candidate is then retired by
	 * exact CAS, so a concurrent replacement generation cannot be silently deleted.
	 *
	 * @param array $pending_rotation Exact stale pending generation.
	 * @return bool Whether recovery was confirmed and its local lock safely applied.
	 */
	private function transition_pending_rotation_to_server_recovery( array $pending_rotation ): bool {
		if ( ! $this->is_pending_api_password_rotation_state_valid( $pending_rotation ) || array() === $pending_rotation || ! $this->ensure_api_password_rotation_recovery_intent() || ! $this->delete_pending_api_password_rotation( $pending_rotation ) ) {
			return false;
		}

		return $this->reconcile_api_password_rotation_recovery_intent();
	}


	/**
	 * Remove stored credentials and optionally initialize a replacement free account.
	 *
	 * @param bool $init_free_account Whether to initialize a free account afterward.
	 * @return void
	 */
	public function invalidate_auth_data( $init_free_account = false ) {
		$update_result = $this->update_invalidated_auth_data();

		if ( $init_free_account && $update_result['success'] ) {
			$this->init_free_account();
		}

		// reset last account sync, so we can sync its details again.
		$this->reset_last_account_sync();
	}


	/**
	 * Clear and lock authentication data through one authoritative option update.
	 *
	 * @return void
	 */
	private function invalidate_and_lock_auth_data(): void {
		$this->update_invalidated_auth_data( true, true );
	}


	/**
	 * Persist invalidated credentials and optionally set their lock state atomically.
	 *
	 * @param bool|null $is_auth_locked Lock state to persist, or null to preserve it.
	 * @param bool      $reset_account_sync Whether to reset synchronization state in the same update.
	 * @return array Bulk environmental-variable update result.
	 */
	private function update_invalidated_auth_data( ?bool $is_auth_locked = null, bool $reset_account_sync = false ): array {
		$environmental_variable_updates = array(
			self::ENVIRONMENTAL_VARIABLE_API_USERNAME => '',
			self::ENVIRONMENTAL_VARIABLE_API_PASSWORD => '',
		);

		if ( null !== $is_auth_locked ) {
			$environmental_variable_updates[ self::ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED ] = $is_auth_locked;
		}

		if ( $reset_account_sync ) {
			$environmental_variable_updates[ self::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC ] = 0;
			$environmental_variable_updates[ self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED ] = false;
		}

		$update_result = $this->bulk_update_environmental_variables( $environmental_variable_updates );

		$this->synchronize_runtime_credentials_from_environmental_variables();

		return $update_result;
	}


	/**
	 * Determines an A-F group
	 *
	 * @return string 'a' to 'f' or 'x' if not determined
	 */
	public function get_ab_group(): string {
		$group = $this->read_environmental_variable( self::ENVIRONMENTAL_VARIABLE_GROUP );

		// Preserve the legacy falsey fallback while returning truthy stored values unchanged.
		return $group ? $group : 'x';
	}


	/**
	 * Determine whether the client belongs to a specific experiment group.
	 *
	 * @param mixed $group Group identifier to compare.
	 * @return bool Whether the group matches.
	 */
	public function is_group( $group ): bool {
		return $this->get_ab_group() === $group;
	}


	/**
	 * This creates and error array response with message and code
	 *
	 * @param string $message The message to return as an error.
	 * @param mixed  $code The error code to return. Keep this mixed to prevent int overflow errors on large numbers.
	 * @return array The error response.
	 */
	public function respond_error( string $message, $code ): array {
		if ( strlen( $message ) > 256 ) {
			$message = substr( $message, 0, 256 ) . '...'; // remove this to see full error message in the response.
		}

		return array(
			'success' => false,
			'message' => wp_kses_post( $message ),
			'code'    => $code,
		);
	}


	/**
	 * Determine whether an endpoint exchanges password-rotation secrets.
	 *
	 * @param string $endpoint Endpoint path.
	 * @return bool Whether response values require closed diagnostic handling.
	 */
	private function is_sensitive_credential_endpoint( string $endpoint ): bool {
		return in_array( $endpoint, self::SENSITIVE_CREDENTIAL_ENDPOINTS, true );
	}


	/**
	 * Determine whether an API error code should lock authentication data.
	 *
	 * @param mixed $error_code API error code.
	 * @return bool Whether the code requires an authentication lock.
	 */
	public function is_auth_lock_error_code( $error_code ): bool {
		return in_array( (int) $error_code, $this->invalidate_auth_data_error_codes, true );
	}


	/**
	 * This function converts the given data to a normalized success response.
	 *
	 * @param array|string $data The data to return as a success response.
	 * @return array|mixed The success response.
	 */
	public function respond_success( $data ) {
		if ( is_array( $data ) ) {
			$data['success'] = true;
		} else {
			$data = array(
				'success' => true,
				'data'    => wp_kses_post( $data ),
			);
		}

		return $data;
	}


	/**
	 * Return a fully sanitized array, using custom sanitize functions for both keys and values.
	 *
	 * @param array|string $data The array or value to be sanitized.
	 * @param string       $sanitize_value_function_name The custom sanitize function for the values (default: sanitize_text_field).
	 * @param string       $sanitize_key_function_name The custom sanitize function for the keys (default: sanitize_key).
	 * @return array|string The sanitized array or value.
	 */
	public function deep_sanitize( $data, string $sanitize_value_function_name = 'sanitize_text_field', string $sanitize_key_function_name = 'sanitize_key' ) {
		if ( is_array( $data ) ) {
			$sanitized_data = array();
			foreach ( $data as $key => $value ) {
				// Sanitize the key using the key sanitize function.
				$sanitized_key = $sanitize_key_function_name( $key );

				// Recursively sanitize the value if it's an array, or sanitize the value using the value sanitize function.
				if ( is_array( $value ) ) {
					$sanitized_data[ $sanitized_key ] = ai4seo_deep_sanitize( $value, $sanitize_value_function_name, $sanitize_key_function_name );
				} elseif ( is_bool( $value ) ) {
					$sanitized_data[ $sanitized_key ] = $value;
				} else {
					$sanitized_data[ $sanitized_key ] = $sanitize_value_function_name( $value );
				}
			}
			return $sanitized_data;
		} else {
			if ( is_bool( $data ) ) {
				return $data;
			}

			// If it's not an array, sanitize the value directly.
			return $sanitize_value_function_name( $data );
		}
	}


	/**
	 * Function to sanitize endpoint data recursively.
	 *
	 * @param mixed  $data                         The payload or response data.
	 * @param string $endpoint                     Endpoint name.
	 * @param string $sanitize_value_function_name The value sanitizer.
	 * @param array  $path                         Internal recursive path.
	 *
	 * @return mixed Sanitized data.
	 */
	public function deep_sanitize_for_endpoint( $data, string $endpoint, string $sanitize_value_function_name = 'sanitize_text_field', array $path = array() ) {
		if ( ! is_array( $data ) ) {
			if ( is_bool( $data ) ) {
				return $data;
			}

			if (
			$this->is_html_value_path( $path )
			&& in_array( $sanitize_value_function_name, array( 'sanitize_text_field', 'ai4seo_wp_kses' ), true )
			) {
				return $this->sanitize_html_value_for_endpoint( (string) $data );
			}

			return $sanitize_value_function_name( $data );
		}

		$sanitized_data = array();

		foreach ( $data as $key => $value ) {
			$sanitized_key = sanitize_key( $key );
			$child_path    = array_merge( $path, array( $sanitized_key ) );

			$sanitized_data[ $sanitized_key ] = $this->deep_sanitize_for_endpoint(
				$value,
				$endpoint,
				$sanitize_value_function_name,
				$child_path
			);
		}

		return $sanitized_data;
	}


	/**
	 * Normalize a payload path for internal matching.
	 *
	 * @param string $path Payload path.
	 * @return string Normalized path.
	 */
	private function normalize_payload_path( string $path ): string {
		$path_parts            = preg_split( '#[/.]+#', $path );
		$path_parts            = is_array( $path_parts ) ? $path_parts : array();
		$normalized_path_parts = array();

		foreach ( $path_parts as $this_path_part ) {
			$this_path_part = trim( (string) $this_path_part );

			if ( '' === $this_path_part ) {
				continue;
			}

			$normalized_path_parts[] = ( '*' === $this_path_part )
			? '*'
			: sanitize_key( $this_path_part );
		}

		return implode( '/', $normalized_path_parts );
	}


	/**
	 * Sanitize HTML values while preserving safe WordPress block comment artifacts.
	 *
	 * @param string $html HTML value.
	 * @return string Sanitized HTML.
	 */
	private function sanitize_html_value_for_endpoint( string $html ): string {
		$editor_comments = array();
		$html            = $this->protect_editor_artifact_comments( $html, $editor_comments );
		$html            = wp_kses_post( $html );

		if ( $editor_comments ) {
			$html = strtr( $html, $editor_comments );
		}

		return $html;
	}


	/**
	 * Replace safe Gutenberg block comments with temporary placeholders.
	 *
	 * @param string $html            HTML value.
	 * @param array  $editor_comments Placeholder map.
	 * @return string HTML with placeholders.
	 */
	private function protect_editor_artifact_comments( string $html, array &$editor_comments ): string {
		$editor_comments = array();

		return (string) preg_replace_callback(
			'#<!--\s*/?wp:[A-Za-z0-9_/-]+(?:\s+\{.*?})?\s*-->#s',
			static function ( array $matches ) use ( &$editor_comments ): string {
				$comment = (string) ( $matches[0] ?? '' );

				if ( '' === $comment ) {
					return '';
				}

				$placeholder                     = 'AI4SEO_EDITOR_ARTIFACT_COMMENT_' . count( $editor_comments ) . '_';
				$editor_comments[ $placeholder ] = $comment;

				return $placeholder;
			},
			$html
		);
	}


	/**
	 * Check whether the current recursive payload path should preserve safe HTML.
	 *
	 * @param array $path Current sanitized payload path.
	 * @return bool Whether safe HTML should be preserved.
	 */
	private function is_html_value_path( array $path ): bool {
		if ( ! $this->html_value_paths || ! $path ) {
			return false;
		}

		foreach ( $this->html_value_paths as $this_pattern ) {
			$pattern_parts = explode( '/', $this_pattern );

			if ( count( $pattern_parts ) !== count( $path ) ) {
				continue;
			}

			foreach ( $pattern_parts as $this_index => $this_pattern_part ) {
				if ( '*' === $this_pattern_part ) {
					continue;
				}

				if ( (string) ( $path[ $this_index ] ?? '' ) !== $this_pattern_part ) {
					continue 2;
				}
			}

			return true;
		}

		return false;
	}


	/**
	 * Checks if this endpoint is allowed.
	 *
	 * @param string $endpoint The endpoint to check.
	 * @return bool True if the endpoint is allowed, false otherwise.
	 */
	public function is_endpoint_allowed( string $endpoint ): bool {
		// Extension registration canonicalizes identifiers as strings, so compare within the same strict domain.
		return in_array( $endpoint, $this->allowed_endpoints, true );
	}


	/**
	 * Merge endpoint identifiers into a unique endpoint list.
	 *
	 * @param array $current_endpoints Current endpoint list.
	 * @param array $new_endpoints     New endpoint list.
	 * @return array Merged endpoint list.
	 */
	private function merge_endpoint_list( array $current_endpoints, array $new_endpoints ): array {
		foreach ( $new_endpoints as $this_endpoint ) {
			$this_endpoint = $this->normalize_endpoint_identifier( (string) $this_endpoint );

			if ( ! $this_endpoint ) {
				continue;
			}

			$current_endpoints[] = $this_endpoint;
		}

		return array_values( array_unique( $current_endpoints ) );
	}


	/**
	 * Merge integer values into a unique integer list.
	 *
	 * @param array $current_values Current values.
	 * @param array $new_values     New values.
	 * @return array Merged integer list.
	 */
	private function merge_integer_list( array $current_values, array $new_values ): array {
		foreach ( $new_values as $this_value ) {
			if ( ! is_numeric( $this_value ) ) {
				continue;
			}

			$current_values[] = (int) $this_value;
		}

		return array_values( array_unique( $current_values ) );
	}


	/**
	 * Normalize an API endpoint identifier while preserving service slashes.
	 *
	 * @param string $endpoint Endpoint identifier.
	 * @return string Normalized endpoint identifier.
	 */
	private function normalize_endpoint_identifier( string $endpoint ): string {
		$endpoint = trim( $endpoint );
		$endpoint = preg_replace( '/[^a-zA-Z0-9_\/-]/', '', $endpoint );

		return trim( (string) $endpoint, '/' );
	}


	/**
	 * Returns the api username if credentials are initialized.
	 *
	 * @param bool $check_credentials Whether to check if credentials are initialized before returning the username. Defaults to true.
	 * @return string The api username or an empty string if credentials are not initialized.
	 */
	public function get_api_username( bool $check_credentials = true ): string {
		// Make sure that credentials are initialized.
		if ( $check_credentials && ! $this->check_credentials() ) {
			return '';
		}

		return $this->api_username;
	}


	/**
	 * Returns the api password if credentials are initialized.
	 *
	 * @param bool $check_credentials Whether to check if credentials are initialized before returning the password. Defaults to true.
	 * @return string The api password or an empty string if credentials are not initialized.
	 */
	public function get_api_password( bool $check_credentials = true ): string {
		// Make sure that credentials are initialized.
		if ( $check_credentials && ! $this->check_credentials() ) {
			return '';
		}

		return $this->api_password;
	}


	/**
	 * Returns the credits balance of the client.
	 *
	 * @return int The credits balance of the client.
	 */
	public function get_credits_balance(): int {
		return (int) $this->read_environmental_variable( self::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE );
	}


	/**
	 * Determine whether account data has been synchronized.
	 *
	 * @return bool Whether account data is synchronized.
	 */
	public function is_account_synced(): bool {
		return (bool) $this->read_environmental_variable( self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED );
	}


	/**
	 * Function to unset the last account sync timer to effectively to force sync again
	 */
	public function reset_last_account_sync(): void {
		$this->update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC, 0 );
		$this->update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED, false );
	}


	/**
	 * Performs an anonymous call to the robhub api to reject the terms of service.
	 *
	 * @param int $tos_version The version of the terms of service to reject.
	 * @return void
	 */
	public function perform_reject_terms_call( int $tos_version ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Preserve the public parameter for caller compatibility.
		$reject_terms_parameter = array(
			'timestamp'   => time(),
			'tos_version' => AI4SEO_TOS_VERSION_TIMESTAMP,
		);

		$this->call( 'client/reject-terms', $reject_terms_parameter, 'POST', true );
	}


	/**
	 * Notify RobHub that the product was deactivated.
	 *
	 * @return void
	 */
	public function perform_product_deactivated_call() {
		// call robhub api endpoint "client/product-deactivated".
		$this->call( 'client/product-deactivated', array(), 'POST', true );
	}


	/**
	 * Submit client feedback to RobHub.
	 *
	 * @param string $reason Feedback reason.
	 * @param string $message Feedback message.
	 * @param string $flow Feedback flow identifier.
	 * @return array Normalized API response.
	 */
	public function perform_client_feedback_call( string $reason, string $message, string $flow = 'deactivate' ) {
		$endpoint_parameter = array(
			'reason'  => $reason,
			'message' => $message,
			'flow'    => $flow,
		);

		return $this->call( 'client/feedback', $endpoint_parameter, 'POST', true );
	}


	/**
	 * Generates a random api username
	 *
	 * @return string The generated api username
	 */
	public function generate_random_pseudo_api_username(): string {
		return $this->product . '-' . wp_rand( 1000000, 9999999 );
	}


	// ___________________________________________________________________________________________ \\
	// === ROBHUB ENVIRONMENTAL VARIABLES ======================================================== \\
	// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

	/**
	 * Set the option name used for environmental variables.
	 *
	 * @param string $environmental_variables_option_name WordPress option name.
	 * @return void
	 */
	public function set_environmental_variables_option_name( string $environmental_variables_option_name ): void {
		$this->environmental_variables_option_name = $environmental_variables_option_name;
	}


	/**
	 * Normalize stored environmental overrides into the complete runtime contract.
	 *
	 * @param mixed $environmental_variables Stored environmental-variable value.
	 * @return array Complete validated environmental-variable values.
	 */
	private function normalize_environmental_variables( $environmental_variables ): array {
		$environmental_variables = ai4seo_safe_maybe_unserialize( $environmental_variables );
		$normalized_variables    = self::DEFAULT_ENVIRONMENTAL_VARIABLES;

		if ( ! is_array( $environmental_variables ) || ! $environmental_variables ) {
			return $normalized_variables;
		}

		foreach ( self::DEFAULT_ENVIRONMENTAL_VARIABLES as $environmental_variable_name => $default_environmental_variable_value ) {
			if ( ! isset( $environmental_variables[ $environmental_variable_name ] ) ) {
				continue;
			}

			if ( ! $this->validate_environmental_variable_value( $environmental_variable_name, $environmental_variables[ $environmental_variable_name ] ) ) {
				ai4seo_debug_message( 541226225, "Invalid value for environmental variable '" . $environmental_variable_name . "'", true );
				continue;
			}

			$normalized_variables[ $environmental_variable_name ] = $environmental_variables[ $environmental_variable_name ];
		}

		return $normalized_variables;
	}


	/**
	 * Read authoritative environmental values directly from persistent storage.
	 *
	 * @return array Complete validated environmental-variable values.
	 */
	private function read_persisted_environmental_variables(): array {
		return $this->normalize_environmental_variables(
			ai4seo_get_option( $this->environmental_variables_option_name, array(), true )
		);
	}


	/**
	 * Persist environmental deltas through bounded raw-snapshot CAS merges.
	 *
	 * Each caller supplies its view before and after a local mutation. Only that delta is merged
	 * into the freshest authoritative snapshot, so a stale request cannot restore older credentials
	 * while updating an unrelated field.
	 *
	 * @param array $environmental_variables Complete or override-only environmental values.
	 * @return bool Whether storage contains the requested effective values.
	 */
	private function persist_environmental_variables( array $environmental_variables ): bool {
		$baseline_environmental_variables  = $this->normalize_environmental_variables( $this->environmental_variables );
		$requested_environmental_variables = $this->normalize_environmental_variables( $environmental_variables );
		$changed_environmental_variables   = array();

		foreach ( array_keys( self::DEFAULT_ENVIRONMENTAL_VARIABLES ) as $environmental_variable_name ) {
			if ( ! ai4seo_are_persisted_state_values_equivalent(
				$baseline_environmental_variables[ $environmental_variable_name ],
				$requested_environmental_variables[ $environmental_variable_name ]
			) ) {
				$changed_environmental_variables[ $environmental_variable_name ] = $requested_environmental_variables[ $environmental_variable_name ];
			}
		}

		if ( ! $changed_environmental_variables ) {
			return true;
		}

		for ( $storage_attempt = 0; $storage_attempt < self::ENVIRONMENTAL_VARIABLE_STORAGE_MAX_ATTEMPTS; ++$storage_attempt ) {
			$environmental_snapshot = ai4seo_get_raw_option_snapshot( $this->environmental_variables_option_name );

			if ( ! is_array( $environmental_snapshot ) ) {
				return false;
			}

			$fresh_environmental_variables      = $this->normalize_environmental_variables(
				$environmental_snapshot['exists'] ? $environmental_snapshot['value'] : array()
			);
			$filter_old_environmental_variables = $environmental_snapshot['exists']
				? $environmental_snapshot['value']
				: false;
			$merged_environmental_variables     = array_merge(
				$fresh_environmental_variables,
				$changed_environmental_variables
			);

			// Mirror WordPress' authoritative pre-update filters before the exact CAS write.
			// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- These intentionally mirror WordPress' core option filters.
			$merged_environmental_variables = apply_filters(
				"pre_update_option_{$this->environmental_variables_option_name}",
				$merged_environmental_variables,
				$filter_old_environmental_variables,
				$this->environmental_variables_option_name
			);
			$merged_environmental_variables = apply_filters(
				'pre_update_option',
				$merged_environmental_variables,
				$this->environmental_variables_option_name,
				$filter_old_environmental_variables
			);
			// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$merged_environmental_variables = $this->normalize_environmental_variables( $merged_environmental_variables );
			$environmental_overrides        = $this->build_environmental_variable_overrides( $merged_environmental_variables );
			$is_existing_storage_equivalent = $environmental_snapshot['exists']
				&& 'no' === $environmental_snapshot['autoload']
				&& hash_equals( (string) maybe_serialize( $environmental_overrides ), $environmental_snapshot['raw_value'] )
				&& $environmental_overrides === $environmental_snapshot['value'];

			// Match update_option(): a pre-update filter that restores the old value is a failed
			// mutation, and an empty missing option must not be materialized as an empty row.
			if ( $is_existing_storage_equivalent
				|| ( ! $environmental_snapshot['exists'] && ! $environmental_overrides ) ) {
				$this->environmental_variables = $fresh_environmental_variables;
				$this->synchronize_runtime_credentials_from_environmental_variables();

				foreach ( $changed_environmental_variables as $environmental_variable_name => $requested_value ) {
					if ( ! ai4seo_are_persisted_state_values_equivalent(
						$requested_value,
						$fresh_environmental_variables[ $environmental_variable_name ]
					) ) {
						return false;
					}
				}

				return true;
			}

			$write_result = ai4seo_compare_and_swap_option_snapshot(
				$this->environmental_variables_option_name,
				$environmental_snapshot,
				$environmental_overrides,
				false,
				true
			);

			if ( null === $write_result ) {
				return false;
			}

			$verified_snapshot = ai4seo_get_raw_option_snapshot( $this->environmental_variables_option_name );

			if ( ! is_array( $verified_snapshot )
			|| ! $verified_snapshot['exists']
			|| 'no' !== $verified_snapshot['autoload']
			|| ! hash_equals( (string) maybe_serialize( $environmental_overrides ), $verified_snapshot['raw_value'] )
			|| $environmental_overrides !== $verified_snapshot['value'] ) {
				continue;
			}

			$persisted_environmental_variables = $this->normalize_environmental_variables( $verified_snapshot['value'] );
			$is_requested_delta_persisted      = true;

			foreach ( $changed_environmental_variables as $environmental_variable_name => $requested_value ) {
				if ( ! ai4seo_are_persisted_state_values_equivalent(
					$requested_value,
					$persisted_environmental_variables[ $environmental_variable_name ]
				) ) {
					$is_requested_delta_persisted = false;
					break;
				}
			}

			$this->environmental_variables = $persisted_environmental_variables;
			$this->synchronize_runtime_credentials_from_environmental_variables();

			return $is_requested_delta_persisted;
		}

		return false;
	}


	/**
	 * Reduce complete environmental values to non-default persisted overrides.
	 *
	 * @param array $environmental_variables Complete normalized values.
	 * @return array Valid non-default overrides.
	 */
	private function build_environmental_variable_overrides( array $environmental_variables ): array {
		$environmental_overrides = array();

		foreach ( self::DEFAULT_ENVIRONMENTAL_VARIABLES as $environmental_variable_name => $default_value ) {
			if ( ! ai4seo_are_persisted_state_values_equivalent(
				$default_value,
				$environmental_variables[ $environmental_variable_name ]
			) ) {
				$environmental_overrides[ $environmental_variable_name ] = $environmental_variables[ $environmental_variable_name ];
			}
		}

		return $environmental_overrides;
	}


	/**
	 * Synchronize runtime API credentials with the authoritative environmental cache.
	 *
	 * @return void
	 */
	private function synchronize_runtime_credentials_from_environmental_variables(): void {
		$this->api_username = (string) ( $this->environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ] ?? '' );
		$this->api_password = (string) ( $this->environmental_variables[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ] ?? '' );
	}


	/**
	 * Function to retrieve all robhub environmental variables
	 *
	 * @return array All RobHub environmental variables
	 */
	public function read_all_environmental_variables(): array {
		if ( ! isset( $this->environmental_variables ) || ! $this->environmental_variables ) {
			$this->environmental_variables = self::DEFAULT_ENVIRONMENTAL_VARIABLES;
		}

		// use cached version.
		if ( self::DEFAULT_ENVIRONMENTAL_VARIABLES !== $this->environmental_variables ) {
			return $this->environmental_variables;
		}

		$this->environmental_variables = $this->read_persisted_environmental_variables();

		return $this->environmental_variables;
	}


	/**
	 * Function to retrieve a specific robhub environmental variable
	 *
	 * @param string $environmental_variable_name The name of the robhub environmental variable.
	 * @return mixed The value of the environmental variable
	 */
	public function read_environmental_variable( string $environmental_variable_name ) {
		// Make sure that $environmental_variable_name-parameter has content.
		if ( ! $environmental_variable_name ) {
			ai4seo_debug_message( 3515181024, 'Environmental variable name is empty.', true );
			return null;
		}

		// check for a default value.
		if ( ! isset( self::DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] ) ) {
			ai4seo_debug_message( 197825, "Environmental variable '" . $environmental_variable_name . "' does not exist.", true );
			return null;
		}

		$current_environmental_variables = $this->read_all_environmental_variables();

		// Check if the $environmental_variable_name-parameter exists in environmental variables-array.
		if ( isset( $current_environmental_variables[ $environmental_variable_name ] ) ) {
			return $current_environmental_variables[ $environmental_variable_name ];
		} else {
			return self::DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ];
		}
	}


	/**
	 * Function to update a specific robhub environmental variable
	 *
	 * @param string $environmental_variable_name The name of the robhub environmental variable.
	 * @param mixed  $new_environmental_variable_value The new value of the robhub environmental variable.
	 * @return bool True if the robhub environmental variable was updated successfully, false if not
	 */
	public function update_environmental_variable( string $environmental_variable_name, $new_environmental_variable_value ): bool {
		if ( ! isset( self::DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ] ) ) {
			ai4seo_debug_message( 1297825, "Environmental variable '" . $environmental_variable_name . "' does not exist.", true );
			return false;
		}

		// Make sure that the new value of the environmental variable is valid.
		if ( ! $this->validate_environmental_variable_value( $environmental_variable_name, $new_environmental_variable_value ) ) {
			ai4seo_debug_message( 3715181024, "Invalid value for environmental variable '" . $environmental_variable_name . "'.", true );
			return false;
		}

		// sanitize.
		$new_environmental_variable_value = $this->deep_sanitize( $new_environmental_variable_value );

		// overwrite entry in $current_environmental_variables-array.
		$current_environmental_variables = $this->read_all_environmental_variables();

		// Remove default-equivalent values so the option stores overrides only.
		if ( ai4seo_are_persisted_state_values_equivalent(
			self::DEFAULT_ENVIRONMENTAL_VARIABLES[ $environmental_variable_name ],
			$new_environmental_variable_value
		) ) {
			unset( $current_environmental_variables[ $environmental_variable_name ] );
		} else {
			// Preserve the existing representation when only its storage-compatible type differs.
			if ( isset( $current_environmental_variables[ $environmental_variable_name ] )
			&& ai4seo_are_persisted_state_values_equivalent(
				$current_environmental_variables[ $environmental_variable_name ],
				$new_environmental_variable_value
			)
			) {
				return true;
			}

			$current_environmental_variables[ $environmental_variable_name ] = $new_environmental_variable_value;
		}

		// no changes made.
		if ( ai4seo_are_persisted_state_values_equivalent( $current_environmental_variables, $this->environmental_variables ) ) {
			return true;
		}

		return $this->persist_environmental_variables( $current_environmental_variables );
	}


	/**
	 * Bulk update RobHub environmental variables.
	 *
	 * @param array $environmental_variable_updates Associative array: name => value.
	 * @return array {
	 *     @type bool  $success        True if persisted successfully (or nothing to persist).
	 *     @type int   $updated_count  Number of variables changed (added/updated/removed).
	 *     @type array $invalid_names  Unknown names skipped.
	 *     @type array $invalid_values Names skipped due to invalid values.
	 * }
	 */
	public function bulk_update_environmental_variables( array $environmental_variable_updates ): array {
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
				ai4seo_debug_message( 1397825, "Environmental variable '" . $this_name . "' does not exist.", true );
				continue;
			}

			// Validate value.
			if ( ! $this->validate_environmental_variable_value( $this_name, $this_value ) ) {
				$result['invalid_values'][] = $this_name;
				ai4seo_debug_message( 3715181024, "Invalid value for environmental variable '" . $this_name . "'", true );
				continue;
			}

			// Sanitize.
			$this_value = $this->deep_sanitize( $this_value );

			// Remove default-equivalent values so the option stores overrides only.
			if ( ai4seo_are_persisted_state_values_equivalent(
				self::DEFAULT_ENVIRONMENTAL_VARIABLES[ $this_name ],
				$this_value
			) ) {
				if ( isset( $current_environmental_variables[ $this_name ] ) ) {
					unset( $current_environmental_variables[ $this_name ] );
					++$result['updated_count'];
				}
				continue;
			}

			// Preserve the existing representation when only its storage-compatible type differs.
			if ( isset( $current_environmental_variables[ $this_name ] )
			&& ai4seo_are_persisted_state_values_equivalent(
				$current_environmental_variables[ $this_name ],
				$this_value
			)
			) {
				continue;
			}

			// Apply change.
			$current_environmental_variables[ $this_name ] = $this_value;
			++$result['updated_count'];
		}

		// Avoid rewriting the shared option when every requested update is representation-equivalent.
		if ( ai4seo_are_persisted_state_values_equivalent(
			$current_environmental_variables,
			$this->environmental_variables
		) ) {
			return $result;
		}

		// Persist once and compare against WordPress' authoritative filtered value.
		if ( ! $this->persist_environmental_variables( $current_environmental_variables ) ) {
			$result['success'] = false;
			ai4seo_debug_message( 64912045, 'Failed to persist environmental variables in bulk update.', true );
		}

		return $result;
	}



	/**
	 * Function to delete an robhub environmental variable
	 *
	 * @param string $environmental_variable_name The name of the robhub environmental variable.
	 * @return bool True if the robhub environmental variable was deleted successfully, false if not
	 */
	public function delete_environmental_variable( string $environmental_variable_name ): bool {
		// Make sure that $environmental_variable_name-parameter has content.
		if ( ! $environmental_variable_name ) {
			ai4seo_debug_message( 31319225, 'Environmental variable name is empty.', true );
			return false;
		}

		// overwrite entry in $current_environmental_variables-array.
		$current_environmental_variables = $this->read_all_environmental_variables();

		if ( ! isset( $current_environmental_variables[ $environmental_variable_name ] ) ) {
			return false;
		}

		// delete the entry.
		unset( $current_environmental_variables[ $environmental_variable_name ] );

		return $this->persist_environmental_variables( $current_environmental_variables );
	}


	/**
	 * Determine whether the environmental option row currently exists.
	 *
	 * @return bool Whether the option row exists.
	 */
	private function does_environmental_variables_option_exist(): bool {
		$missing_option = new stdClass();

		return ai4seo_get_option(
			$this->environmental_variables_option_name,
			$missing_option,
			true
		) !== $missing_option;
	}


	/**
	 * Delete the environmental option row through the WordPress option API.
	 *
	 * This narrow seam permits deterministic failure coverage without replacing the option API.
	 *
	 * @return bool Whether an existing option row was deleted.
	 */
	protected function delete_environmental_variables_option(): bool {
		return ai4seo_delete_option( $this->environmental_variables_option_name, false );
	}


	/**
	 * Deletes all robhub environmental variables
	 *
	 * @return bool
	 */
	public function delete_all_environmental_variables(): bool {
		if ( ! $this->does_environmental_variables_option_exist() ) {
			$this->environmental_variables = self::DEFAULT_ENVIRONMENTAL_VARIABLES;
			$this->synchronize_runtime_credentials_from_environmental_variables();
			return true;
		}

		$this->delete_environmental_variables_option();

		if ( $this->does_environmental_variables_option_exist() ) {
			return false;
		}

		$this->environmental_variables = self::DEFAULT_ENVIRONMENTAL_VARIABLES;
		$this->synchronize_runtime_credentials_from_environmental_variables();

		return true;
	}


	/**
	 * Validate value of an robhub environmental variable
	 *
	 * @param string $environmental_variable_name The name of the robhub environmental variable.
	 * @param mixed  $environmental_variable_value The value of the robhub environmental variable.
	 */
	public function validate_environmental_variable_value( string $environmental_variable_name, $environmental_variable_value ): bool {
		switch ( $environmental_variable_name ) {
			case self::ENVIRONMENTAL_VARIABLE_API_USERNAME:
				if ( $environmental_variable_value && ! preg_match( '/^[a-z0-9_\-]{5,48}$/', $environmental_variable_value ) ) {
					return false;
				}

				return true;

			case self::ENVIRONMENTAL_VARIABLE_API_PASSWORD:
				if ( $environmental_variable_value && ! preg_match( '/^[a-z0-9_\-]{48}$/', $environmental_variable_value ) ) {
					return false;
				}

				return true;

			case self::ENVIRONMENTAL_VARIABLE_CREDITS_BALANCE:
			case self::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC:
			case self::ENVIRONMENTAL_VARIABLE_NEXT_FREE_CREDITS_TIMESTAMP:
				// contains only of numbers.
				return is_numeric( $environmental_variable_value ) && $environmental_variable_value >= 0;

			case self::ENVIRONMENTAL_VARIABLE_GROUP:
				// Accept canonical groups plus the legacy unset/default sentinels used by persisted state.
				return is_string( $environmental_variable_value )
				&& in_array( $environmental_variable_value, array( 'a', 'b', 'c', 'd', 'e', 'f', 'x', '' ), true );

			case self::ENVIRONMENTAL_VARIABLE_SUBSCRIPTION:
				// must be an array.
				if ( ! is_array( $environmental_variable_value ) ) {
					return false;
				}

				return true;

			case self::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA:
				// array, contains of two elements, each of them contains only of alphanumeric characters.
				if ( ! is_array( $environmental_variable_value ) ) {
					return false;
				}

				// empty array is allowed.
				if ( count( $environmental_variable_value ) === 0 ) {
					return true;
				}

				if ( count( $environmental_variable_value ) !== 2 ) {
					return false;
				}

				if ( ! preg_match( '/^[a-z0-9_\-]{5,48}$/', $environmental_variable_value[0] ) ) {
					return false;
				}

				if ( ! preg_match( '/^[a-z0-9_\-]{48}$/', $environmental_variable_value[1] ) ) {
					return false;
				}

				return true;

			case self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED:
			case self::ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED:
				return is_bool( $environmental_variable_value );

			default:
				return false;
		}
	}


	/**
	 * Function to get the checksum of an API call.
	 *
	 * @param string $endpoint The endpoint of the API call.
	 * @param array  $parameters The parameters of the API call.
	 * @param string $method The method of the API call.
	 * @return int The crc32 checksum of the API call.
	 */
	public function get_api_call_checksum( string $endpoint, array $parameters, string $method ): int {
		if ( 'client/get-free-account' === $endpoint ) {
			// Use only stable site identity fields here so concurrent first-run requests with
			// different user agents, client IPs, or timestamps still hit the same existing lock.
			$parameters = array(
				'website_url'         => $parameters['website_url'] ?? '',
				'website_name'        => $parameters['website_name'] ?? '',
				'admin_email_address' => $parameters['admin_email_address'] ?? '',
			);
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Existing lock checksums depend on PHP serialization.
		return crc32( $endpoint . serialize( $parameters ) . $method );
	}


	/**
	 * Function to get the checksum of an API call endpoint.
	 *
	 * @param string $endpoint The endpoint of the API call endpoint.
	 * @return int The crc32 checksum of the API call endpoint
	 */
	public function get_api_call_endpoint_checksum( string $endpoint ): int {
		return crc32( $endpoint );
	}


	/**
	 * Function to tidy up all existing api lock transients
	 */
	public function tidy_up_api_locks(): void {
		foreach ( $this->endpoint_lock_durations as $endpoint => $duration ) {
			$endpoint_checksum = $this->get_api_call_endpoint_checksum( $endpoint );
			$transient_name    = 'robhub_api_lock_' . $endpoint_checksum;
			delete_transient( $transient_name );
		}
	}


	/**
	 * Migrate and remove deprecated combined authentication data.
	 *
	 * @return bool|null True when no migration is needed, otherwise null.
	 */
	public function tidy_up_deprecated_auth_data() {
		if ( ! self::read_environmental_variable( self::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA ) ) {
			return true;
		}

		$old_auth_data    = self::read_environmental_variable( self::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA );
		$old_api_username = sanitize_text_field( $old_auth_data[0] ?? '' );
		$old_api_password = sanitize_text_field( $old_auth_data[1] ?? '' );

		if ( $old_api_username ) {
			self::update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_API_USERNAME, $old_api_username );
		}

		if ( $old_api_password ) {
			self::update_environmental_variable( self::ENVIRONMENTAL_VARIABLE_API_PASSWORD, $old_api_password );
		}

		self::delete_environmental_variable( self::ENVIRONMENTAL_VARIABLE_DEPRECATED_API_AUTH_DATA );

		return null;
	}


	// ___________________________________________________________________________________________ \\
	// === LOCAL MODE ============================================================================ \\
	// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

	/**
	 * Configure local API mode.
	 *
	 * @param bool   $is_local_api_enabled Whether local API mode is enabled.
	 * @param string $local_api_url Local API base URL.
	 * @return void
	 */
	public function init_local_mode( bool $is_local_api_enabled, string $local_api_url ): void {
		$this->is_local_api_enabled = $is_local_api_enabled;
		$this->local_api_url        = $local_api_url;
	}


	/**
	 * Function to check whether the current environment is a localhost environment
	 *
	 * @return bool Whether the current environment is a localhost environment
	 */
	public function are_we_using_local_api(): bool {
		return $this->is_local_api_enabled && $this->are_we_on_a_localhost_system();
	}


	/**
	 * Determine whether the current server matches a localhost identity.
	 *
	 * @return bool Whether the current server is local.
	 */
	public function are_we_on_a_localhost_system(): bool {
		// Reuse one sanitized server name for all localhost comparisons in this request.
		$server_name = '';

		if ( isset( $_SERVER['SERVER_NAME'] ) && is_string( $_SERVER['SERVER_NAME'] ) ) {
			$server_name = sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) );
		}

		return ( '127.0.0.1' === $server_name || 'localhost' === $server_name || str_replace( 'http://', '', $this->local_api_url ) === $server_name );
	}
}
