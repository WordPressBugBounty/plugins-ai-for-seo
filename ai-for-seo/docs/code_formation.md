# Code Formatting Guidelines for AI for SEO Plugin

This document describes the code formatting and style conventions used throughout the AI for SEO WordPress plugin. Following a consistent style helps keep the codebase readable and maintainable.

---

## General Formatting

- Indentation  
  Use four spaces for each level. Do not use tabs.
- Braces  
  Place opening braces on the same line as the declaration. Place closing braces on their own line, aligned with the start of the statement.
    ```php
    function ai4seo_example_function( $arg1, $arg2 ) {
        if ( $arg1 === $arg2 ) {
            // Your logic here.
        }
    }
    ```
- Blank lines  
  Use blank lines to separate logical sections, for example between the end of a control structure and the next function or variable declaration.
- Line length  
  Keep lines reasonably short. Break long arrays or function signatures across multiple lines, one item per line, with a trailing comma.

---

## PHP Tags and Files

- Always begin pure PHP files with `<?php` and omit the closing `?>` tag.
- For template files mixing PHP and HTML, include `?>` where needed.
- Use Unix line endings (`\n`) only.
- Group major sections with comment dividers:
    ```php
    // =========================================================================================== \\
    ```

---

## Naming Conventions

- **Functions**
    - Lowercase with underscores
    - Prefix with `ai4seo_`
    - Example: `ai4seo_get_setting()`

- **Classes**
    - StudlyCaps (PascalCase)
    - Files under `includes/`
    - Example: `Ai4Seo_RobHubApiCommunicator`

- **Variables**
    - Lowercase with underscores
    - Prefix plugin‑wide variables with `$ai4seo_`
    - Declare globals as `global $ai4seo_variable;` at the top of functions
    - When using a “get” function, name the variable after what it returns:
      ```php
      $robhub_client_price = get_robhub_client_price();
      ```  
    - Prefix boolean variables with `do_`, `is_` or `was_`:
      ```php
      $is_cache_enabled = true;
      ```  
    - Suffix with the variable type (`name`, `title`, `id`) or plural for 1D arrays (`names`, `titles`, `ids`):
      ```php
      $post_titles = ai4seo_get_post_titles();
      ```  
    - For complex arrays, use the pattern `ai4seo_{{adjective}}_{{subject}}_{{variable_type}}`:
      ```php
      $ai4seo_metadata                 = ai4seo_get_metadata();              // all metadata (2D array)
      $ai4seo_missing_metadata         = ai4seo_get_missing_metadata( $ai4seo_metadata );  
      $ai4seo_missing_metadata_post_ids = ai4seo_get_metadata_post_ids( $ai4seo_missing_metadata ); // post IDs (1D array)
      ```  
        - **{{adjective}}** (optional) describes a filtered subset (`active`, `missing`)
        - **{{subject}}** names the data subject (`robhub_client`, `key_phrase`, `post`)
        - **{{variable_type}}** (optional) specifies content type (`_title` for string, `_ids` for array)
    - In loops, prefix variables that are overwritten with `this_`:
      ```php
      foreach ( $robhub_client_users as $this_robhub_client_user ) {
          $this_robhub_client_user_name = $this_robhub_client_user['name'];
      }
      ```  
    - When extracting a single field from an array, include the field name in the variable:
      ```php
      $robhub_client_name = $robhub_client['name'];
      ```  

- **Constants**
    - Uppercase with underscores
    - Define with `define()` at the top of files
    - Example:
      ```php
      define( 'AI4SEO_PLUGIN_VERSION_NUMBER', '2.1.0' );
      ```  

---

## Arrays and Operators

- Use long array syntax `array( … )`.
- When multiline, put each key/value pair on its own line, indent one level, include a trailing comma:
    ```php
    $defaults = array(
        'option_1' => 'value_1',
        'option_2' => 'value_2',
    );
    ```
- Surround operators (=, =>, ==, ===, +, ., ,) with single spaces.
- In calls, no space after `(` or before `)`, but space after commas:
    ```php
    ai4seo_example_function( $arg1, $arg2 );
    ```
- Use strict comparisons (`===`, `!==`) when types matter; loose comparisons (`==`, `!=`) only when needed.

---

## Comments and Documentation

- Document functions and methods with a docblock immediately before the declaration. Include summary, `@param` and `@return` tags:
    ```php
    /**
     * Function to initialise the plugin settings.
     *
     * Reads settings from the database into the global `$ai4seo_settings` array.
     *
     * @return void
     */
    function ai4seo_init_settings() {
        // Implementation…
    }
    ```
- Use `//` for inline comments or to disable code temporarily. Align with the code.
- Avoid trailing whitespace in comments.
- Group related sections with the same horizontal dividers used above.

---

## WordPress‑Specific Best Practices

- Use WP API functions (`get_option()`, `update_option()`, `add_action()`, `add_filter()`) instead of direct queries.
- Sanitize input and escape output. Example:
    ```php
    $value  = sanitize_text_field( $_POST['my_field'] ?? '' );
    $output = esc_html( $value );
    echo '<span>' . $output . '</span>';
    ```
- Use named functions for hooks rather than anonymous functions:
    ```php
    add_action( 'init', 'ai4seo_init_settings' );
    ```
- Prefix database or transient keys with `ai4seo_`.

---

## File Organisation

- Place classes, helpers and API code under `includes/`.
- Place JS and CSS under `assets/`.
- Keep each file focused: one class or related group of functions only.
- Store documentation and notes under `docs/` in Markdown format.

---

## Examples and Patterns

- Initialization hooks in main plugin file:
    ```php
    // === INITIALISATION =============================================================== \\

    // Init settings
    add_action( 'init', 'ai4seo_init_settings' );

    // For frontend
    add_action( 'init', 'ai4seo_init_frontend_injections' );
    ```
- Class definitions with typed properties and explicit constructor:
    ```php
    class Ai4Seo_RobHubApiCommunicator {
        private array $recent_endpoint_responses = array();

        public function __construct() {
            // Constructor intentionally left blank.
        }
    }
    ```
- Always use braces, even for single‑line conditionals:
    ```php
    if ( ! ai4seo_can_manage_this_plugin() ) {
        return;
    }
    ```
