<?php
defined('ABSPATH') || exit;

use OpenProfile\WordpressFactPod\OAuth\Repositories\ScopeRepository;

function wpfp_settings_page_html()
{
    if (isset($_POST['wpfp_scope_update']) && check_admin_referer('wpfp_update_scope')) {
        $scopeRepository = new ScopeRepository();
        $scope = sanitize_text_field($_POST['scope']);
        $isActive = ($_POST['is_active'] ?? '0') === '1';

        if ($scopeRepository->updateScopeStatus($scope, $isActive)) {
            // Regenerate OpenProfile discovery document with updated scopes
            $baseUrl = get_site_url();
            $openProfileDiscovery = \OpenProfile\WordpressFactPod\Utils\WellKnown::generateOpenProfileDiscovery($baseUrl);
            update_option('wpfp_openprofile', json_encode($openProfileDiscovery, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            add_settings_error('wpfp_messages', 'wpfp_scope_updated', 'Scope status updated successfully.', 'updated');
        } else {
            add_settings_error('wpfp_messages', 'wpfp_scope_error', 'Failed to update scope status.', 'error');
        }
    }
?>

    <div class="wrap">
        <h1>Admin Settings Fact Pod</h1>
        <?php
        require_once WPFP_PLUGIN_DIR . 'templates/admin/oauth-clients-table.php';
        require_once WPFP_PLUGIN_DIR . 'templates/admin/oauth-scopes-table.php';
        ?>
    </div>

<?php
}
