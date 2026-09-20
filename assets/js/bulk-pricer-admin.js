(function($) {
    'use strict';

    $(function() {
        const cfg = window.sbp_vars || {};
        const i18n = cfg.i18n || {};
        const fmt = cfg.format || {};
        const $status = $('#sbp-batch-status');

        /* ------------------------------------------------------------------
         * Helpers
         * ------------------------------------------------------------------ */

        /** Minimal sprintf for "%s", "%d", "%1$s", "%2$d" placeholders. */
        function t(template) {
            const args = Array.prototype.slice.call(arguments, 1);
            let sequence = 0;
            return String(template).replace(/%(?:(\d+)\$)?[sd]/g, function(match, position) {
                const index = position ? parseInt(position, 10) - 1 : sequence++;
                return args[index] !== undefined ? args[index] : '';
            });
        }

        function esc(text) {
            return $('<div>').text(text === null || text === undefined ? '' : String(text)).html();
        }

        function notice(type, html) {
            return '<div class="notice notice-' + type + '"><p>' + html + '</p></div>';
        }

        function formatNumber(value) {
            const decimals = Number(fmt.decimals) || 0;
            const parts = Math.abs(value).toFixed(decimals).split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, fmt.thousandSep || ',');
            return parts.join(fmt.decimalSep || '.');
        }

        function formatMoney(value, signed) {
            let sign = '';
            if (signed && value > 0) {
                sign = '+';
            } else if (signed && value < 0) {
                sign = '−';
            }
            return sign + formatNumber(value) + ' ' + (fmt.symbol || '');
        }

        function showStatus(html) {
            $status.show().html(html);
        }

        function progressHtml(label, done, total) {
            const percent = total > 0 ? Math.min(100, Math.round((done / total) * 100)) : 0;
            return '<div class="notice notice-info sbp-progress-notice">' +
                '<p>' + esc(label) + ' ' + esc(t(i18n.progress_label, done, total)) + '</p>' +
                '<div class="sbp-progress"><div class="sbp-progress__bar" style="width:' + percent + '%"></div></div>' +
                '</div>';
        }

        function historyLink() {
            return ' <a href="' + esc(cfg.historyUrl) + '">' + esc(i18n.view_history) + '</a>';
        }

        /**
         * Drive a run to completion, one batch per request.
         *
         * The first request (runId 0) creates the run from `extra` (the form);
         * every later request only carries the run ID.
         */
        function runBatches(opts) {
            function step(runId, first) {
                let data = $.param({ action: opts.action, security: cfg.nonce, run_id: runId });
                if (first && opts.extra) {
                    data += '&' + opts.extra;
                }

                $.ajax({ url: cfg.ajaxurl, type: 'POST', dataType: 'json', data: data })
                    .done(function(response) {
                        if (!response || !response.success) {
                            opts.onError(response && response.data ? response.data : i18n.error_applying, runId);
                            return;
                        }

                        const d = response.data;
                        showStatus(progressHtml(opts.label, d.done, d.total));

                        if (d.remaining) {
                            step(d.run_id, false);
                        } else {
                            opts.onDone(d);
                        }
                    })
                    .fail(function() {
                        opts.onError(i18n.error_connection, runId);
                    });
            }

            step(opts.runId || 0, true);
        }

        /* ------------------------------------------------------------------
         * Bulk Editor tab
         * ------------------------------------------------------------------ */
        const Editor = {
            excluded: {},
            summary: null,
            summaryToken: 0,

            init: function() {
                if (!$('#sbp-form').length) {
                    return;
                }
                this.bindEvents();
                this.toggleFields();
            },

            bindEvents: function() {
                const self = this;

                $('#sbp-form').on('change', '[name="operation_type"], [name="round_mode"], [name="apply_mode"]', function() {
                    self.toggleFields();
                });

                $(document).on('click', '#sbp-preview-btn, .sbp-page-link', this.handlePreview.bind(this));
                $(document).on('click', '#sbp-confirm-btn', this.handleConfirm.bind(this));
                $(document).on('click', '.sbp-delete-row', this.handleToggleRow.bind(this));
                $(document).on('click', '#sbp-export-btn', this.handleExport.bind(this));
            },

            /** Show only the fields that matter for the chosen operation. */
            toggleFields: function() {
                const $form = $('#sbp-form');
                const $option = $form.find('[name="operation_type"] option:selected');
                const type = $option.val();
                const input = $option.attr('data-input');
                const roundMode = $form.find('[name="round_mode"]').val();
                const scheduled = $form.find('[name="apply_mode"]:checked').val() === 'schedule';

                $form.find('.sbp-row-change').toggle(input === 'change');
                $form.find('.sbp-row-exact').toggle(input === 'exact');
                $form.find('.sbp-row-sync').toggle($option.attr('data-sync') === '1');
                $form.find('.sbp-row-dates').toggle($option.attr('data-dates') === '1');
                $form.find('.sbp-row-rounding').toggle(type !== 'remove_discount');
                $form.find('.sbp-row-limits').toggle(type !== 'remove_discount' && type !== 'round_prices');

                const stepMode = ['nearest', 'up', 'down'].indexOf(roundMode) !== -1;
                $form.find('.sbp-round-step, .sbp-round-help--step').toggle(stepMode);
                $form.find('.sbp-round-ending, .sbp-round-help--ending').toggle(roundMode === 'ending');

                $form.find('.sbp-op-description').each(function() {
                    const types = String($(this).attr('data-for') || '').split(' ');
                    $(this).toggle(types.indexOf(type) !== -1);
                });

                $form.find('.sbp-schedule-at').toggle(scheduled);
            },

            /** Form data + the products the user removed from the preview. */
            requestData: function() {
                return $('#sbp-form').serialize() + '&excluded_ids=' + encodeURIComponent(JSON.stringify(this.excludedIds()));
            },

            excludedIds: function() {
                return Object.keys(this.excluded).map(Number);
            },

            /* ---- Preview ---- */

            handlePreview: function(e) {
                e.preventDefault();

                const $el = $(e.currentTarget);
                const fresh = $el.is('#sbp-preview-btn');

                // Starting a new preview forgets removed rows; paging keeps them.
                if (fresh) {
                    this.excluded = {};
                }

                this.loadPreview(fresh ? 1 : ($el.data('page') || 1), fresh);
            },

            loadPreview: function(page, fresh) {
                const self = this;
                const $result = $('#sbp-results');

                $result.html(notice('info', esc(i18n.loading_preview) + ' ' + page + '...')).css('opacity', '0.6');

                $.ajax({
                    url: cfg.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: $('#sbp-form').serialize() + '&action=sbp_preview_action&paged=' + page + '&security=' + cfg.nonce,
                    success: function(response) {
                        $result.css('opacity', '1');

                        if (!response.success) {
                            $result.html(notice('error', esc(response.data)));
                            self.summaryToken++;
                            return;
                        }

                        $result.html(response.data.html);
                        self.applyExclusions();

                        if (!$('#sbp-summary').length) {
                            self.summaryToken++;
                        } else if (fresh) {
                            self.startSummary();
                        } else {
                            self.renderSummary();
                        }
                    },
                    error: function() {
                        $result.css('opacity', '1').html(notice('error', esc(i18n.error_connection)));
                    }
                });
            },

            /* ---- Removing rows from the run ---- */

            handleToggleRow: function(e) {
                e.preventDefault();

                const $row = $(e.currentTarget).closest('tr');
                const id = String($row.data('product-id'));

                this.setRowExcluded($row, !this.excluded[id]);
                this.renderSummary();
            },

            setRowExcluded: function($row, excluded) {
                const id = String($row.data('product-id'));
                const $button = $row.find('.sbp-delete-row');

                if (excluded) {
                    this.excluded[id] = true;
                    $row.addClass('sbp-row-excluded');
                    $button.text('+').attr('title', i18n.restore_row);
                } else {
                    delete this.excluded[id];
                    $row.removeClass('sbp-row-excluded');
                    $button.text('×').attr('title', i18n.remove_row);
                }
            },

            /** Re-mark removed rows after the table is redrawn (paging). */
            applyExclusions: function() {
                const self = this;

                $('#sbp-results tr[data-product-id]').each(function() {
                    if (self.excluded[String($(this).data('product-id'))]) {
                        self.setRowExcluded($(this), true);
                    }
                });
            },

            /* ---- Summary (whole product set, calculated in chunks) ---- */

            startSummary: function() {
                const token = ++this.summaryToken;

                this.summary = { total: 0, loaded: 0, rows: {}, done: false, error: false };
                this.renderSummary();
                this.summaryStep(token, 0);
            },

            summaryStep: function(token, offset) {
                const self = this;

                $.ajax({
                    url: cfg.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: $('#sbp-form').serialize() + '&action=sbp_summary_action&offset=' + offset + '&security=' + cfg.nonce
                }).done(function(response) {
                    if (token !== self.summaryToken) {
                        return; // A newer preview replaced this one.
                    }

                    if (!response.success) {
                        self.summary.error = true;
                        self.renderSummary();
                        return;
                    }

                    const d = response.data;
                    d.rows.forEach(function(row) {
                        self.summary.rows[row[0]] = row;
                    });
                    self.summary.total = Number(d.total);
                    self.summary.loaded = Number(d.next_offset);
                    self.summary.done = !!d.done;
                    self.renderSummary();

                    if (!d.done) {
                        self.summaryStep(token, d.next_offset);
                    }
                }).fail(function() {
                    if (token === self.summaryToken) {
                        self.summary.error = true;
                        self.renderSummary();
                    }
                });
            },

            renderSummary: function() {
                const $box = $('#sbp-summary');
                const s = this.summary;

                if (!$box.length || !s) {
                    return;
                }

                let changed = 0, up = 0, down = 0, other = 0, unchanged = 0, impact = 0, base = 0;

                for (const id in s.rows) {
                    if (this.excluded[id]) {
                        continue;
                    }

                    const row = s.rows[id];
                    if (!row[1]) {
                        unchanged++;
                        continue;
                    }

                    const delta = row[3] - row[2];
                    changed++;
                    impact += delta;
                    base += row[2];

                    if (delta > 0) {
                        up++;
                    } else if (delta < 0) {
                        down++;
                    } else {
                        other++;
                    }
                }

                const excludedCount = Object.keys(this.excluded).length;
                const set = function(key, text) {
                    return $box.find('[data-sbp-sum="' + key + '"]').text(text);
                };

                const direction = [];
                if (up) { direction.push(t(i18n.summary_increase, up)); }
                if (down) { direction.push(t(i18n.summary_decrease, down)); }
                if (other) { direction.push(t(i18n.summary_other, other)); }

                set('scope', s.total ? String(Math.max(0, s.total - excludedCount)) : '…');
                set('changed', String(changed));
                set('direction', direction.length ? '(' + direction.join(' · ') + ')' : '');
                set('unchanged', String(unchanged));
                set('excluded', String(excludedCount));
                set('impact', formatMoney(impact, true))
                    .toggleClass('price-increase', impact > 0)
                    .toggleClass('price-decrease', impact < 0);
                set('average', base > 0 ? '(' + t(i18n.summary_average, (impact >= 0 ? '+' : '−') + Math.abs((impact / base) * 100).toFixed(1) + '%') + ')' : '');

                if (s.error) {
                    set('progress', i18n.summary_error);
                } else if (!s.done) {
                    set('progress', t(i18n.summary_loading, s.loaded, s.total));
                } else {
                    set('progress', '');
                }
            },

            /* ---- Confirm: apply now or schedule ---- */

            handleConfirm: function(e) {
                e.preventDefault();

                const scheduled = $('#sbp-form [name="apply_mode"]:checked').val() === 'schedule';

                if (!window.confirm(scheduled ? i18n.confirm_schedule : i18n.confirm_apply)) {
                    return;
                }

                if (scheduled) {
                    this.schedule();
                } else {
                    this.apply();
                }
            },

            apply: function() {
                const self = this;
                const $button = $('#sbp-confirm-btn').prop('disabled', true).text(i18n.processing);

                runBatches({
                    action: 'sbp_apply_batch_action',
                    extra: this.requestData(),
                    label: i18n.processing_batch,
                    onDone: function() {
                        showStatus(notice('success', esc(i18n.success) + historyLink()));
                        $('#sbp-results').empty();
                        self.summaryToken++;
                        self.summary = null;
                    },
                    onError: function(message, runId) {
                        showStatus(notice('error', esc(message) + (runId ? ' ' + esc(i18n.error_resume_hint) + historyLink() : '')));
                        $button.prop('disabled', false).text(i18n.confirm_final);
                    }
                });
            },

            schedule: function() {
                const self = this;
                const $button = $('#sbp-confirm-btn').prop('disabled', true).text(i18n.processing);

                $.ajax({
                    url: cfg.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: this.requestData() + '&action=sbp_schedule_action&security=' + cfg.nonce
                }).done(function(response) {
                    if (!response.success) {
                        showStatus(notice('error', esc(response.data)));
                        $button.prop('disabled', false).text(i18n.schedule_final);
                        return;
                    }

                    const d = response.data;
                    let message = t(i18n.scheduled_ok, d.count, d.apply_at);
                    if (d.revert_at) {
                        message += ' ' + t(i18n.scheduled_revert, d.revert_at);
                    }

                    showStatus(notice('success', esc(message) + historyLink()));
                    $('#sbp-results').empty();
                    self.summaryToken++;
                    self.summary = null;
                }).fail(function() {
                    showStatus(notice('error', esc(i18n.error_connection)));
                    $button.prop('disabled', false).text(i18n.schedule_final);
                });
            },

            /* ---- CSV export of the whole preview ---- */

            handleExport: function(e) {
                e.preventDefault();

                const $form = $('<form method="post" target="_blank" style="display:none"></form>').attr('action', cfg.adminPostUrl);
                const add = function(name, value) {
                    $('<input type="hidden">').attr('name', name).val(value).appendTo($form);
                };

                $('#sbp-form').serializeArray().forEach(function(field) {
                    add(field.name, field.value);
                });
                add('action', 'sbp_export_csv');
                add('security', cfg.nonce);
                add('excluded_ids', JSON.stringify(this.excludedIds()));

                $form.appendTo('body');
                $form[0].submit();
                $form.remove();
            }
        };

        /* ------------------------------------------------------------------
         * Import CSV tab
         * ------------------------------------------------------------------ */
        const Importer = {
            init: function() {
                if (!$('#sbp-import-form').length) {
                    return;
                }

                $('#sbp-import-form').on('submit', this.upload.bind(this));
                $(document).on('click', '#sbp-import-confirm', this.confirm.bind(this));
                $(document).on('click', '#sbp-import-cancel', this.discard.bind(this));
            },

            upload: function(e) {
                e.preventDefault();

                const $results = $('#sbp-import-results');
                const $button = $('#sbp-import-upload-btn').prop('disabled', true);
                const data = new window.FormData(e.currentTarget);

                data.append('action', 'sbp_import_upload_action');
                data.append('security', cfg.nonce);
                $results.html(notice('info', esc(i18n.processing)));

                $.ajax({
                    url: cfg.ajaxurl,
                    type: 'POST',
                    data: data,
                    processData: false,
                    contentType: false,
                    dataType: 'json'
                }).done(function(response) {
                    $results.html(response.success ? response.data.html : notice('error', esc(response.data)));
                }).fail(function() {
                    $results.html(notice('error', esc(i18n.error_connection)));
                }).always(function() {
                    $button.prop('disabled', false);
                });
            },

            confirm: function(e) {
                e.preventDefault();

                if (!window.confirm(i18n.confirm_import)) {
                    return;
                }

                const $button = $(e.currentTarget).prop('disabled', true);
                $('#sbp-import-cancel').prop('disabled', true);

                runBatches({
                    action: 'sbp_apply_batch_action',
                    runId: $button.data('run-id'),
                    label: i18n.processing_batch,
                    onDone: function() {
                        showStatus(notice('success', esc(i18n.import_success) + historyLink()));
                        $('#sbp-import-results').empty();
                    },
                    onError: function(message) {
                        showStatus(notice('error', esc(message) + ' ' + esc(i18n.error_resume_hint) + historyLink()));
                        $button.prop('disabled', false);
                        $('#sbp-import-cancel').prop('disabled', false);
                    }
                });
            },

            discard: function(e) {
                e.preventDefault();

                $.post(cfg.ajaxurl, {
                    action: 'sbp_cancel_run_action',
                    security: cfg.nonce,
                    run_id: $(e.currentTarget).data('run-id')
                }).always(function() {
                    $('#sbp-import-results').empty();
                });
            }
        };

        /* ------------------------------------------------------------------
         * History tab
         * ------------------------------------------------------------------ */
        const History = {
            init: function() {
                if (!$('.sbp-history-table').length) {
                    return;
                }

                $(document).on('click', '.sbp-history-action', this.handle.bind(this));
            },

            handle: function(e) {
                e.preventDefault();

                const $button = $(e.currentTarget);
                const action = $button.data('action');
                const runId = $button.data('run-id');
                const scheduled = $button.closest('tr').find('.sbp-status').hasClass('sbp-status--scheduled');

                switch (action) {
                    case 'revert':
                        if (window.confirm(i18n.confirm_revert)) {
                            this.run('sbp_revert_batch_action', runId, i18n.reverting_batch, i18n.revert_success);
                        }
                        break;

                    case 'resume':
                        if (!scheduled || window.confirm(i18n.confirm_run_now)) {
                            this.run('sbp_apply_batch_action', runId, i18n.processing_batch, i18n.success);
                        }
                        break;

                    case 'cancel':
                        if (window.confirm(i18n.confirm_cancel)) {
                            this.simple('sbp_cancel_run_action', runId);
                        }
                        break;

                    case 'delete':
                        if (window.confirm(i18n.confirm_delete)) {
                            this.simple('sbp_delete_run_action', runId);
                        }
                        break;

                    case 'details':
                        this.details($button, runId);
                        break;
                }
            },

            lock: function() {
                $('.sbp-history-action').prop('disabled', true);
            },

            run: function(action, runId, label, successMessage) {
                this.lock();

                runBatches({
                    action: action,
                    runId: runId,
                    label: label,
                    onDone: function() {
                        showStatus(notice('success', esc(successMessage)));
                        window.setTimeout(function() { window.location.reload(); }, 800);
                    },
                    onError: function(message) {
                        showStatus(notice('error', esc(message)));
                        $('.sbp-history-action').prop('disabled', false);
                    }
                });
            },

            simple: function(action, runId) {
                this.lock();

                $.post(cfg.ajaxurl, { action: action, security: cfg.nonce, run_id: runId }, function(response) {
                    if (response && response.success) {
                        window.location.reload();
                        return;
                    }

                    showStatus(notice('error', esc(response && response.data ? response.data : i18n.error_applying)));
                    $('.sbp-history-action').prop('disabled', false);
                }, 'json').fail(function() {
                    showStatus(notice('error', esc(i18n.error_connection)));
                    $('.sbp-history-action').prop('disabled', false);
                });
            },

            details: function($button, runId) {
                const $row = $button.closest('tr');
                const $open = $row.next('.sbp-details-row');

                if ($open.length) {
                    $open.remove();
                    return;
                }

                const $details = $('<tr class="sbp-details-row"><td colspan="7"></td></tr>').insertAfter($row);
                $details.find('td').text(i18n.details_loading);

                $.post(cfg.ajaxurl, { action: 'sbp_run_details_action', security: cfg.nonce, run_id: runId }, function(response) {
                    $details.find('td').html(response && response.success ? response.data.html : esc(response && response.data ? response.data : ''));
                }, 'json').fail(function() {
                    $details.find('td').text(i18n.error_connection);
                });
            }
        };

        Editor.init();
        Importer.init();
        History.init();
    });
})(jQuery);
