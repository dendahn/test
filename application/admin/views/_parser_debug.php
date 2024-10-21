<?php defined('\ABSPATH') || exit; ?>
<small id="ei-debug-notice" style="color: red;cursor: pointer;">Debug mode is enabled</small><br>
<div ng-show="debug" class="panel panel-default" style="overflow: auto;height:400px;" id="ei-debug-panel">

    <div class="panel-heading">
        <pre ng-show="debug">{{debug}}</pre>
        <pre ng-show="products.length">Product data: {{products[products.length - 1]| json}}
        </pre>
    </div>

</div>

<script>
    jQuery(document).ready(function($) {
        jQuery('#ei-debug-notice').click(function() {
            jQuery('#ei-debug-panel').toggle();
        });
    });
</script>