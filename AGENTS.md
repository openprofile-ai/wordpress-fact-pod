### Purpose of this document
Guidance for AI agents (and contributors) working on the WordPress Fact Pod plugin. It explains the domain context (OpenProfile), the plugin’s architecture, strict placement rules for code, and how to safely extend features.

---

### Domain context: OpenProfile
- OpenProfile (see https://github.com/openprofile-ai/openprofile) defines a way to expose “facts” and capabilities about a site/user and to authorize access using OAuth 2.0.

---

### Project type and scope
- This project is a WordPress + WooCommerce plugin.
- Root plugin file: wordpress-fact-pod.php.
- Main plugin orchestration, hooks, rewrite rules, and WordPress integrations are in includes/WordpressFactPod.php.
- Install/Uninstall lifecycle:
    - install.php handles plugin installation (DB migrations, key generation, .well-known publication, rewrite flush flag).
    - uninstall.php handles plugin uninstallation/cleanup.

---

### Repository structure and placement rules
- Root files
    - wordpress-fact-pod.php: boots the plugin and loads includes/WordpressFactPod.php.
    - install.php, uninstall.php: lifecycle scripts.
- includes/ (PHP only — no HTTP handlers or direct output)
    - WordpressFactPod.php: central place for WordPress hooks, rewrite rules, enqueueing assets, menus, and wiring modules together. All webhooks and other WordPress-related wiring should be implemented here as much as possible.
    - Database/: database migrations and schema helpers.
    - OAuth/: authorization, registration, token services, repositories, validators.
    - Utils/: general helpers (e.g., WellKnown.php builds discovery/JWKS payloads; WooCommerce.php reads categories; Session.php session handling; Http.php for transforms and authenticate()).
    - Facts/: module for facts-related functionality.
        - Api.php: registers Facts REST routes (register_rest_route) and coordinates handling.
        - Entities/: domain entities (e.g., Entities/Fact.php).
        - Repositories/: data access (e.g., Repositories/FactsRepository.php).
- templates/
    - WordPress HTML/PHP view files (e.g., oauth-login.php, oauth-scopes.php, admin settings page, public templates).
    - The src/ folder will be moved under templates/ as well (keep logic minimal in templates; call into includes/* code).
- assets/
    - JS and CSS (enqueued from includes/WordpressFactPod.php).

Strict rules:
- includes/ must contain only PHP classes/modules with no direct HTTP I/O (no echo/header/json_encode for responses). Build data, return arrays/strings, and let WordPress routing/templates output.
- Prefer adding WordPress hooks, filters, rewrite rules, and enqueueing to includes/WordpressFactPod.php to keep a single integration point.
- Place business logic in includes/ submodules; keep templates strictly for rendering.

---

### Where things happen (current implementation reference)
- Activation and setup
    - register_activation_hook in includes/WordpressFactPod.php->init() points to this plugin and calls activate().
    - activate() requires install.php, runs DB install, key generation, publishes .well-known, sets a flag to flush rewrites.
- Discovery and JWKS
    - init_well_known() adds rewrite rules and query vars for /.well-known/openprofile.json and /.well-known/openprofile-jwks.json.
    - template_redirect serves JSON directly from WordPress options when the query var is present.
    - Utils\WellKnown::generateOpenProfileDiscovery() builds the discovery JSON (issuer, endpoints, scopes, JSON-LD factpod, etc.).
    - Utils\WellKnown::generateJwks() builds a minimal RS256 JWKS from the public key.
- OAuth scaffolding
    - init_oauth() registers rewrites for /openprofile/oauth/login and /openprofile/oauth/scopes to load templates/oauth-*.php.
    - OAuth runtime wiring (Auth, Register) is initialized on init if keys are present.
    - wp_login redirects to scopes UI.
- Facts REST API
    - init_facts() instantiates Facts\Api registrar on init; the registrar hooks rest_api_init and registers routes under /wp-json/openprofile/facts/*.
    - Protected Facts routes use Utils\\Http::authenticate() in permission_callback.
- WooCommerce integration
    - Utils\WooCommerce provides top-level category metadata and shop URL for discovery.
- Assets and Admin
    - enqueue_styles()/enqueue_scripts() add assets from assets/.
    - add_admin_menu() loads admin settings page template.

---

### How agents should implement changes
- WordPress integration, hooks, routing
    - Add all WordPress add_action/add_filter/add_rewrite_rule registrations in includes/WordpressFactPod.php.
    - New REST endpoints should be registered in a small module registrar class (e.g., includes/Facts/Api.php) that hooks rest_api_init. Instantiate the registrar from a dedicated init_<api>() method in includes/WordpressFactPod.php (e.g., init_facts()).
    - Permission callbacks for Bearer-protected routes must call Utils\\Http::authenticate($request) and propagate WP_Error (401 on invalid/missing token; 403 for future insufficient scope cases).
- Business logic
    - Create/extend classes under includes/ according to these categories:
        - Database for migrations and schema.
        - OAuth for auth flows, tokens, repositories.
        - Utils for helpers and cross-cutting utilities (including Http::authenticate and request/response transforms).
        - Facts for facts domain (Api, Entities, Repositories).
- Templates and output
    - Put any HTML/PHP rendering into templates/.
    - Keep templates thin; they should call into includes/* classes to fetch data.
- Assets
    - Add JS/CSS to assets/ and enqueue them from includes/WordpressFactPod.php.
- Install/Uninstall
    - Put DB migrations and setup tasks into install.php. Expose functions (e.g., wp_fact_pod_install_database) that are called from activate().
    - Put cleanup/teardown in uninstall.php.

---

### Coding guidelines for agents
- PHP 8.3 target (per composer.json). Do not use PHP strict mode (do not add declare(strict_types=1)). Use namespaces under OpenProfile\\WordpressFactPod.
- Do not add docblocks/comments for properties or methods. Keep classes minimal; prefer native type hints where clear.
- Do not perform header() or echo in includes/* except within WordPress output hooks specifically designed to output (e.g., the small template_redirect closures in includes/WordpressFactPod.php). Prefer delegating output to templates.
- Avoid global state; use singletons only where already established (WordpressFactPod::get_instance()).
- Security: validate all input (REST params, query vars), escape output in templates, never store unhashed secrets, respect WordPress nonces in admin forms.
- Performance: cache where appropriate with transients/options; avoid heavy queries on every page load.
- Logging: use error_log() sparingly; consider WP logging facilities if available.

---

### Quick reference to key files
- wordpress-fact-pod.php (root)
- install.php / uninstall.php (lifecycle)
- includes/WordpressFactPod.php (all core hooks and WP wiring)
- includes/Database/* (migrations)
- includes/OAuth/* (OAuth server logic, repositories)
- includes/Utils/WellKnown.php (discovery/JWKS generation)
- includes/Utils/Http.php (PSR-7/WP transforms and authenticate())
- includes/Facts/* (Facts module: Api.php, Entities, Repositories)
- includes/Utils/WooCommerce.php (category/shop helpers)
- templates/* (views; includes admin settings, oauth-login.php, oauth-scopes.php)
- assets/js/* and assets/styles/* (client assets)

---

### Non-negotiable rules for AI agents
- Do not place HTTP output or direct WordPress routing inside includes/* modules other than the minimal wiring in includes/WordpressFactPod.php.
- All new WordPress hooks, rewrite rules, and REST route registrar instantiations must be wired from includes/WordpressFactPod.php in a dedicated init_<api>() method per feature (e.g., init_facts()).
- Protected REST routes must use Utils\\Http::authenticate() in permission_callback.
- The includes folder must contain no HTTP — only PHP code (classes, services, repositories, migrations, utilities).
- Keep assets in assets/ and templates in templates/ (the src folder is being migrated into templates/).
- This is a WordPress + WooCommerce plugin; ensure WooCommerce checks exist before calling its APIs.
