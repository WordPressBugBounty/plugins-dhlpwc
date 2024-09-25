<?php
if (is_admin()) {
    return;
}
?>

<?php // React app ?>
<div id='dhlpwc-app'></div>

<?php // DHL ServicePoint Locator modal ?>
<div id="dhlpwc-servicepoint-modal" class="dhlpwc-modal">
    <div id="dhlpwc-modal-content" class="dhlpwc-modal-content">
        <div class="dhlpwc-modal-close-wrapper">
                <div class="dhlpwc-modal-logo">
                    <img src="<?php echo esc_attr(DHLPWC_PLUGIN_URL . 'assets/images/dhlpwc_logo.png') ?>" />
                </div>
            <span id="dhlpwc-modal-close" class="dhlpwc-modal-close">&times;</span>
        </div>
        <div id="dhlparcel_shipping_locator_real_reset">
            <div id="dhl-servicepoint-locator-component"
                 data-query=""
                 data-limit="7"
            ></div>
        </div>
        <div class="clear"></div>
    </div>
</div>
