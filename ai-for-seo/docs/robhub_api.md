# AI for SEO – API Guide

This guide describes how to use the **RobHub API** for various functionalities within the **AI for SEO** plugin.

---

## Overview

* The RobHub API is used to send and fetch data from the main server (**RobHub Server**).
* The RobHub Server manages user accounts, subscriptions, billing, and provides AI services (content generation, media attributes, etc.).
* Built on top of the OpenAI API — all AI requests are forwarded to OpenAI. Users do **not** need their own OpenAI key.
* Access via: `Ai4Seo_RobHubApiCommunicator` class in `/includes/api/class-robhub-api-communicator.php`.
* This class handles requests, authentication, error handling, and responses.
* The RobHub API is RESTful, using standard HTTP methods (`GET`, `POST`).

---

## Example Usage

Use the helper function `ai4seo_robhub_api()`:

```php
$response = ai4seo_robhub_api()->call($robhub_endpoint, $endpoint_parameter, $method); // method optional, default GET

if ( ! ai4seo_robhub_api()->was_call_successful($response) ) {
    // Handle error or stop execution
}

$response_data = $response['data'] ?? array();
```

---

## RobHub Server Source Code

The RobHub server is part of this project in `robhub-api/v1/` (not included in the plugin). Key files:

1. `robhub-api/v1/index.php` → Handles initialization.
2. `robhub-api/v1/class-robhub-api.php` → Core class, routes requests to endpoint handlers.
3. `robhub-api/v1/services/` → Service endpoints (e.g., `class-robhub-api-service-client.php` for `client/xxxx` endpoints, `class-robhub-api-service-ai4seo.php` for `ai4seo/xxxx`).
4. `robhub-api/v1/ai-tools/` → OpenAI usage instructions (e.g., `class-robhub-api-ai-tool-chatgpt.php`, `class-robhub-api-ai-tool-gpt5.php`).

---

## Adding New Endpoints

### Plugin Side (`class-robhub-api-communicator.php`)

* Add endpoint to `$allowed_endpoints` array.
* Add endpoint to `private const ENDPOINT_LOCK_DURATIONS` (define lock time).
* If endpoint is free (no credits), add to `$free_endpoints`.
* For complex calls, consider adding a dedicated `perform_xxxxx_call()` wrapper.

### Server Side (`robhub-api/v1/services/`)

* Add to `$allowedEndpoints` array (`[METHOD]/endpoint`).

    * Example: `POST/generate-all-metadata`.
    * Define `load` (performance cost, 1–10) and `min-credits`.
* Implement in `performEndpointAction()` to route requests.
* Validate input, return errors with `$this->respondError($message, $code)`.
* Set `$this->response` (string or array) to return data back to plugin.
* Example reference: `class-robhub-api-service-ai4seo.php`, endpoint `POST/changed-api-user`.

---

## Environmental Variables

* Used to store API-related state.
* Check `// === ENVIRONMENTAL VARIABLES` in `/includes/api/class-robhub-api-communicator.php`.
* **Read:** `read_environmental_variable($name)`
* **Write:** `update_environmental_variable($name, $value)`

---

## Developer Checklist

* [ ] Confirm endpoint belongs under `ai4seo/xxxx` (AI-related) or `client/xxxx` (user-related).
* [ ] Add endpoint to communicator (`$allowed_endpoints`, lock duration, `$free_endpoints` if applicable).
* [ ] Implement plugin wrapper method if needed (`perform_xxxxx_call`).
* [ ] Update RobHub Server `services/` file with allowed endpoint and method.
* [ ] Define `load` and `min-credits` for the endpoint.
* [ ] Implement logic in `performEndpointAction()`.
* [ ] Validate inputs and handle errors with `respondError()`.
* [ ] Return proper `$this->response`.
* [ ] Use endpoint with `ai4seo_robhub_api()->call()`.