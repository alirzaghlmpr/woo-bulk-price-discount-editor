(function($) {
    'use strict';

    $(function() {
        /**
         * Bulk Pricer Admin Object
         */
        const BulkPricer = {
            currentPage: 1,
            excludedProductIds: [],

            /**
             * Initialize the plugin
             */
            init: function() {
                this.bindEvents();
            },

            /**
             * Bind event handlers
             */
            bindEvents: function() {
                $(document).on('click', '#sbp-preview-btn, .sbp-page-link', this.handlePreview.bind(this));
                $(document).on('click', '#sbp-confirm-btn', this.handleConfirm.bind(this));
                $(document).on('click', '.sbp-delete-row', this.handleDeleteRow.bind(this));
            },

            /**
             * Handle delete row button click
             */
            handleDeleteRow: function(e) {
                e.preventDefault();
                const $row = $(e.currentTarget).closest('tr');
                const productId = $row.data('product-id');

                // Add to excluded list
                if (!this.excludedProductIds.includes(productId)) {
                    this.excludedProductIds.push(productId);
                }

                // Remove row with animation
                $row.fadeOut(300, function() {
                    $(this).remove();
                });
            },

            /**
             * Handle preview button/pagination click
             */
            handlePreview: function(e) {
                e.preventDefault();

                // Reset excluded products when clicking preview button (not pagination)
                if ($(e.currentTarget).is('#sbp-preview-btn')) {
                    this.excludedProductIds = [];
                }

                this.currentPage = $(e.currentTarget).data('page') || 1;
                this.loadPreview(this.currentPage);
            },

            /**
             * Load preview data
             */
            loadPreview: function(page) {
                const formData = $('#sbp-form').serialize();
                const $resultDiv = $('#sbp-results');

                $resultDiv.html('<div class="notice notice-info"><p>' + sbp_vars.i18n.loading_preview + ' ' + page + '...</p></div>')
                         .css('opacity', '0.6');

                $.ajax({
                    url: sbp_vars.ajaxurl,
                    type: 'POST',
                    data: formData + '&action=sbp_preview_action&paged=' + page + '&security=' + sbp_vars.nonce,
                    success: function(response) {
                        $resultDiv.css('opacity', '1');
                        if (response.success) {
                            $resultDiv.html(response.data.html);
                        } else {
                            $resultDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        $resultDiv.css('opacity', '1');
                        $resultDiv.html('<div class="notice notice-error"><p>' + sbp_vars.i18n.error_connection + '</p></div>');
                    }
                });
            },

            /**
             * Handle confirm button click
             */
            handleConfirm: function() {
                if (!confirm(sbp_vars.i18n.confirm_apply)) {
                    return;
                }

                $(this).prop('disabled', true).text(sbp_vars.i18n.processing);
                this.processBatch(1);
            },

            /**
             * Process batch of products
             */
            processBatch: function(page) {
                const formData = $('#sbp-form').serialize();
                const $status = $('#sbp-batch-status');

                $status.show().html('<div class="notice notice-info"><p>' + sbp_vars.i18n.processing_batch + ' ' + page + '...</p></div>');

                $.ajax({
                    url: sbp_vars.ajaxurl,
                    type: 'POST',
                    data: formData + '&action=sbp_apply_batch_action&paged=' + page + '&security=' + sbp_vars.nonce + '&excluded_ids=' + JSON.stringify(this.excludedProductIds),
                    success: function(response) {
                        if (response.success) {
                            if (response.data.remaining) {
                                this.processBatch(page + 1);
                            } else {
                                $status.html('<div class="notice notice-success"><p>' + sbp_vars.i18n.success + '</p></div>');
                                $('#sbp-results').empty();
                                $('#sbp-confirm-btn').prop('disabled', false).text(sbp_vars.i18n.confirm_final);
                            }
                        } else {
                            $status.html('<div class="notice notice-error"><p>' + sbp_vars.i18n.error_applying + '</p></div>');
                            $('#sbp-confirm-btn').prop('disabled', false).text(sbp_vars.i18n.confirm_final);
                        }
                    }.bind(this),
                    error: function() {
                        $status.html('<div class="notice notice-error"><p>' + sbp_vars.i18n.error_connection + '</p></div>');
                        $('#sbp-confirm-btn').prop('disabled', false).text(sbp_vars.i18n.confirm_final);
                    }
                });
            }
        };

        // Initialize the plugin
        BulkPricer.init();
    });
})(jQuery);
