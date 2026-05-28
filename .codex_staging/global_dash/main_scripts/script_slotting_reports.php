<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    $(window).on('load', function () {
        // Hardcode to 280px to match CSS and prevent any compression
        var leftPanel = $('#left-panel');
        leftPanel.css({
            'width': '280px',
            'min-width': '280px',
            'max-width': '280px'
        });
    });

    $("#reports_slot").addClass('active');

    function _shorts_expeditor_escape_html(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function _shorts_expeditor_escape_attr(value) {
        return _shorts_expeditor_escape_html(value).replace(/"/g, '&quot;');
    }

    function _shorts_expeditor_close_omitted_panel() {
        $('#shortsExpeditorManager').removeClass('is-open').attr('aria-hidden', 'true');
        $('body').removeClass('shorts-expeditor-panel-open');
    }

    function _shorts_expeditor_reset_report_shell() {
        $('#shorts_exp_summary').remove();
        $('#card_report').remove();
        _shorts_expeditor_close_omitted_panel();
    }

    function _shorts_expeditor_ensure_manager_shell() {
        if ($('#shortsExpeditorManagerStyles').length === 0) {
            $('head').append(
                '<style id="shortsExpeditorManagerStyles">' +
                '.shorts-expeditor-panel-open{overflow:hidden;}' +
                '#shortsExpeditorManager{position:fixed;top:0;right:0;bottom:0;left:0;display:none;align-items:center;justify-content:center;padding:24px;background:rgba(33,37,41,0.18);z-index:2050;}' +
                '#shortsExpeditorManager.is-open{display:flex;}' +
                '#shortsExpeditorManager .shorts-expeditor-panel{width:100%;max-width:860px;max-height:86vh;display:flex;flex-direction:column;overflow:hidden;border-radius:10px;border:1px solid #d6dde5;background:#ffffff;box-shadow:0 18px 48px rgba(0,0,0,0.16);}' +
                '#shortsExpeditorManager .shorts-expeditor-panel-head{padding:18px 22px 16px 22px;border-bottom:1px solid #dde4eb;background:linear-gradient(180deg,#f8fafc 0%,#eef3f7 100%);box-shadow:inset 4px 0 0 #007bff;color:#2f3c4d;}' +
                '#shortsExpeditorManager .shorts-expeditor-panel-title{margin:0;font-size:1.35rem;font-weight:700;letter-spacing:0.01em;}' +
                '#shortsExpeditorManager .shorts-expeditor-panel-subtitle{margin-top:5px;font-size:0.93rem;max-width:560px;color:#617181;}' +
                '#shortsExpeditorManager .shorts-expeditor-panel-badge{display:inline-flex;align-items:center;justify-content:center;min-width:42px;padding:7px 12px;border-radius:999px;background:#e9eff5;color:#34495e;font-size:0.92rem;font-weight:700;border:1px solid #d7e0e8;}' +
                '#shortsExpeditorManager .shorts-expeditor-panel-close{border:1px solid #cfd7df;background:#ffffff;color:#495057;border-radius:999px;width:38px;height:38px;font-size:1.05rem;line-height:1;display:inline-flex;align-items:center;justify-content:center;box-shadow:none;}' +
                '#shortsExpeditorManager .shorts-expeditor-panel-close:hover{background:#f5f7fa;border-color:#bfc9d4;}' +
                '#shortsExpeditorManager .shorts-expeditor-panel-body{padding:22px;overflow:auto;background:#f7f9fb;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-toolbar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-bottom:18px;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-input{flex:1 1 260px;min-width:220px;height:42px;border:1px solid #ced4da;border-radius:6px;padding:0 12px;font-size:0.98rem;background:#ffffff;box-shadow:none;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-input:focus{border-color:#80bdff;box-shadow:0 0 0 0.2rem rgba(0,123,255,0.14);outline:none;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-actions{display:flex;flex-wrap:wrap;gap:10px;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-list{display:flex;flex-direction:column;gap:12px;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-item{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:14px;padding:15px 16px;border:1px solid #e0e6ec;border-radius:8px;background:#ffffff;box-shadow:none;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-item:hover{background:#fdfefe;border-color:#d2dbe4;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-loc{font-size:1.08rem;font-weight:700;color:#2c3e50;letter-spacing:0.02em;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-meta{margin-top:4px;font-size:0.89rem;color:#6c757d;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-row-actions{display:flex;flex-wrap:wrap;gap:8px;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-empty{display:none;padding:28px 20px;border:1px dashed #c9d2db;border-radius:8px;background:#ffffff;text-align:center;color:#6c757d;font-size:0.96rem;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-loading{display:none;padding:22px 10px;color:#6c757d;font-weight:600;text-align:center;}' +
                '#shortsExpeditorManager .shorts-expeditor-manager-footer{margin-top:18px;font-size:0.87rem;color:#6c757d;}' +
                '@media (max-width: 767px){#shortsExpeditorManager{padding:14px;}#shortsExpeditorManager .shorts-expeditor-panel-head{padding:18px 18px 16px 18px;}#shortsExpeditorManager .shorts-expeditor-panel-body{padding:18px;}#shortsExpeditorManager .shorts-expeditor-manager-item{padding:14px;}#shortsExpeditorManager .shorts-expeditor-manager-row-actions{width:100%;}#shortsExpeditorManager .shorts-expeditor-manager-row-actions .btn{flex:1 1 0;}}' +
                '</style>'
            );
        }

        if ($('#shortsExpeditorManager').length) {
            return;
        }

        $('body').append(
            '<div id="shortsExpeditorManager" aria-hidden="true">' +
                '<div class="shorts-expeditor-panel" role="dialog" aria-modal="true" aria-labelledby="shortsExpeditorManagerTitle">' +
                    '<div class="shorts-expeditor-panel-head">' +
                        '<div class="d-flex justify-content-between align-items-start flex-wrap" style="gap:12px;">' +
                            '<div>' +
                                '<h3 id="shortsExpeditorManagerTitle" class="shorts-expeditor-panel-title">Omitted Locations</h3>' +
                                '<div class="shorts-expeditor-panel-subtitle">Review the 1-hour exclusions, add a location manually, or restore one without leaving the report.</div>' +
                            '</div>' +
                            '<div class="d-flex align-items-center" style="gap:10px;">' +
                                '<span id="shortsExpeditorManagerCount" class="shorts-expeditor-panel-badge">0</span>' +
                                '<button type="button" class="shorts-expeditor-panel-close" data-close-shortsexp-manager="1" aria-label="Close">' +
                                    '<i class="fa fa-times"></i>' +
                                '</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="shorts-expeditor-panel-body">' +
                        '<div id="shortsExpeditorManagerMessage" style="display:none;"></div>' +
                        '<div class="shorts-expeditor-manager-toolbar">' +
                            '<input type="text" id="shortsExpeditorNewLoc" class="shorts-expeditor-manager-input" maxlength="20" placeholder="Enter a location to omit for 1 hour">' +
                            '<div class="shorts-expeditor-manager-actions">' +
                                '<button type="button" class="btn btn-primary" id="shortsExpeditorAddLoc"><i class="fa fa-ban" style="margin-right:6px;"></i>Omit 1 Hr</button>' +
                                '<button type="button" class="btn btn-outline-secondary" id="shortsExpeditorRefreshList"><i class="fa fa-refresh" style="margin-right:6px;"></i>Refresh</button>' +
                                '<button type="button" class="btn btn-outline-danger" id="shortsExpeditorClearAll"><i class="fa fa-eraser" style="margin-right:6px;"></i>Clear All</button>' +
                            '</div>' +
                        '</div>' +
                        '<div id="shortsExpeditorManagerLoading" class="shorts-expeditor-manager-loading"><i class="fa fa-spinner fa-spin" style="margin-right:6px;"></i>Loading omitted locations...</div>' +
                        '<div id="shortsExpeditorManagerList" class="shorts-expeditor-manager-list"></div>' +
                        '<div id="shortsExpeditorManagerEmpty" class="shorts-expeditor-manager-empty">No locations are currently omitted. Use the row dismiss icon or add one manually here.</div>' +
                        '<div class="shorts-expeditor-manager-footer">Use "Add Back" to restore a single location immediately or "Reset 1 Hr" to restart its hold timer.</div>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
    }

    function _shorts_expeditor_manager_is_open() {
        return $('#shortsExpeditorManager').hasClass('is-open');
    }

    function _shorts_expeditor_set_manager_message(type, message) {
        var $message = $('#shortsExpeditorManagerMessage');
        if ($message.length === 0) {
            return;
        }

        if (!message) {
            $message.hide().removeClass('alert alert-success alert-danger alert-info').empty();
            return;
        }

        var alertClass = 'alert-info';
        if (type === 'success') {
            alertClass = 'alert-success';
        } else if (type === 'error') {
            alertClass = 'alert-danger';
        }

        $message.removeClass('alert-success alert-danger alert-info')
            .addClass('alert ' + alertClass)
            .text(message)
            .show();
    }

    function _shorts_expeditor_fetch_omitted_locations(onSuccess, onError) {
        $.ajax({
            url: '../global_dash/ajax/get_shortsexp_omitted.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response && response.success) {
                    onSuccess(response);
                    return;
                }

                if (onError) {
                    onError(response && response.message ? response.message : 'Unable to load omitted locations.');
                }
            },
            error: function () {
                if (onError) {
                    onError('Unable to load omitted locations right now.');
                }
            }
        });
    }

    function _shorts_expeditor_refresh_omitted_count() {
        _shorts_expeditor_fetch_omitted_locations(function (response) {
            var count = response.count || 0;
            $('#shortsExpeditorOmittedCount').text(count);
            $('#shortsExpeditorManagerCount').text(count);
        });
    }

    function _shorts_expeditor_load_omitted_locations(preserveMessage) {
        _shorts_expeditor_ensure_manager_shell();

        if (!preserveMessage) {
            _shorts_expeditor_set_manager_message('', '');
        }

        $('#shortsExpeditorManagerLoading').show();
        $('#shortsExpeditorManagerEmpty').hide();
        $('#shortsExpeditorManagerList').empty();

        _shorts_expeditor_fetch_omitted_locations(function (response) {
            var items = response.items || [];
            var rowsHtml = '';

            $('#shortsExpeditorManagerLoading').hide();
            $('#shortsExpeditorOmittedCount').text(response.count || 0);
            $('#shortsExpeditorManagerCount').text(response.count || 0);

            if (items.length === 0) {
                $('#shortsExpeditorManagerEmpty').show();
                return;
            }

            $.each(items, function (index, item) {
                var locText = _shorts_expeditor_escape_html(item.loc);
                var locAttr = _shorts_expeditor_escape_attr(item.loc);
                var omittedAt = _shorts_expeditor_escape_html(item.omitted_at_display);
                var statusText = _shorts_expeditor_escape_html(item.status_text);
                var expiresAt = _shorts_expeditor_escape_html(item.expires_at_display);

                rowsHtml +=
                    '<div class="shorts-expeditor-manager-item">' +
                        '<div>' +
                            '<div class="shorts-expeditor-manager-loc">' + locText + '</div>' +
                            '<div class="shorts-expeditor-manager-meta">Omitted ' + omittedAt + ' | Expires ' + expiresAt + ' | ' + statusText + '</div>' +
                        '</div>' +
                        '<div class="shorts-expeditor-manager-row-actions">' +
                            '<button type="button" class="btn btn-outline-secondary btn-sm shortsExpeditorResetLoc" data-loc="' + locAttr + '">Reset 1 Hr</button>' +
                            '<button type="button" class="btn btn-success btn-sm shortsExpeditorRestoreLoc" data-loc="' + locAttr + '">Add Back</button>' +
                        '</div>' +
                    '</div>';
            });

            $('#shortsExpeditorManagerList').html(rowsHtml);
        }, function (message) {
            $('#shortsExpeditorManagerLoading').hide();
            $('#shortsExpeditorManagerEmpty').show();
            _shorts_expeditor_set_manager_message('error', message);
        });
    }

    function _shorts_expeditor_open_omitted_panel() {
        _shorts_expeditor_ensure_manager_shell();
        $('#shortsExpeditorManager').addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('shorts-expeditor-panel-open');
        $('#shortsExpeditorNewLoc').val('').trigger('focus');
        _shorts_expeditor_load_omitted_locations(false);
    }

    function _shorts_expeditor_after_reload(table) {
        _shorts_expeditor_summary(table);
        if (_shorts_expeditor_manager_is_open()) {
            _shorts_expeditor_load_omitted_locations(true);
        }
    }

    function _shorts_expeditor_reload_table() {
        if (typeof oTable4 !== 'undefined' && $.fn.DataTable.isDataTable('#dt_reports')) {
            oTable4.ajax.reload(function () {
                _shorts_expeditor_after_reload(oTable4);
            }, false);
            return;
        }

        _shorts_expeditor_refresh_omitted_count();
        if (_shorts_expeditor_manager_is_open()) {
            _shorts_expeditor_load_omitted_locations(true);
        }
    }

    function _shorts_expeditor_sort_old_first() {
        if (typeof oTable4 === 'undefined' || !$.fn.DataTable.isDataTable('#dt_reports')) {
            return;
        }

        oTable4.order([[5, 'asc'], [9, 'desc']]).draw();
    }

    function _shorts_expeditor_sort_default() {
        if (typeof oTable4 === 'undefined' || !$.fn.DataTable.isDataTable('#dt_reports')) {
            return;
        }

        if (window.shortsExpeditorDefaultOrder) {
            oTable4.order(window.shortsExpeditorDefaultOrder).draw();
        }
    }

    function _shorts_expeditor_manage_omitted(action, loc, successMessage) {
        var requestData = { action: action };
        if (loc) {
            requestData.loc = $.trim(loc).toUpperCase();
        }

        $.ajax({
            url: '../global_dash/post/manage_shortsexp_omitted.php',
            type: 'POST',
            dataType: 'json',
            data: requestData,
            success: function (response) {
                if (!response || !response.success) {
                    _shorts_expeditor_set_manager_message('error', response && response.message ? response.message : 'Unable to update omitted locations.');
                    return;
                }

                _shorts_expeditor_set_manager_message('success', successMessage || response.message || 'Omitted locations updated.');
                _shorts_expeditor_reload_table();
            },
            error: function () {
                _shorts_expeditor_set_manager_message('error', 'Unable to update omitted locations right now.');
            }
        });
    }
    
    //pre-filter data to be loaded before main datatable is called
    function html_prefilter(report_id) {
        //        debugger;
        $.ajax({
            url: '../global_dash/data_stats/reports_prefilterhtml.php',
            data: { report_id: report_id },
            type: 'POST',
            dataType: 'html',
            success: function (ajaxresult) {
                //                debugger;
                var responsedata = $.parseJSON(ajaxresult); //parses the json result containint the HTLM table variable and the primary key variable
                $("#tableDiv").append(responsedata.tablehtml); //first value of result is the table
                $('#startfiscal').datepicker();
                $('#endfiscal').datepicker();
            }
        });
    }

    function prefilter_click(report_id) {
        //debugger;
        //all prefilter data must have class of prefilter_data.  Loop through each class to get the value
        var array_prefilter_data = [];
        $(".prefilter_data").each(function () {
            // Test if the div element is empty
            array_prefilter_data.push($(this).val());
        });
        card_report_html(report_id, array_prefilter_data);
    }

    //on click of desired report, start logic to build html and data
    $(document).on("click touchstart", ".reportclick", function (e) {
        // Clear any running shorts_expeditor auto-refresh
        if (window.shortsExpeditorRefresh) {
            clearInterval(window.shortsExpeditorRefresh);
            window.shortsExpeditorRefresh = null;
        }
        $('#card_report').addClass('hidden');
        $("#tableDiv").empty(); //reset the tablediv
        var report_id = $(this).attr("id");
        //Does report have a pre-filter such as date range, etc.
        var prefilter = $(this).attr('data-prefilter');
        if (prefilter === '1') {
            //build pre-filter data based off report selected
            html_prefilter(report_id);
        } else {
            //no prefilter designated, go directly to build card_report datatable HTML
            card_report_html(report_id);
        }






    });

    //load the html for the main report datatable which includes the dynamic column headings for the report clicked
    //control_call() is called after sucessful rendering of HTML
    function card_report_html(report_id, array_prefilter_data) {
        //        debugger;
        if (window.shortsExpeditorRefresh) {
            clearInterval(window.shortsExpeditorRefresh);
            window.shortsExpeditorRefresh = null;
        }
        _shorts_expeditor_reset_report_shell();
        $.ajax({
            url: '../global_dash/data_stats/reports_tablehtml.php',
            data: { report_id: report_id },
            type: 'POST',
            dataType: 'html',
            success: function (ajaxresult) {

                var responsedata = $.parseJSON(ajaxresult); //parses the json result containint the HTLM table variable and the primary key variable
                //                $("#tableDiv").empty(); //reset the tablediv
                //debugger;
                $("#tableDiv").append(responsedata.tablehtml); //first value of result is the table
                var col_primkey = responsedata.col_primkey; //second value of result is the column primary key
                var col_filterarray = responsedata.col_filterarray; //filters
                var col_aasort_array = responsedata.aasort_array; //default sort
                var fncreatedrow_action = responsedata.action; //default sort
                var col_itemlink = responsedata.col_itemlink; //default sort
                //debugger;
                $('#card_report').removeClass('hidden'); //show the table
                control_call(report_id, col_primkey, col_filterarray, col_itemlink, array_prefilter_data, col_aasort_array, fncreatedrow_action); //once table HTLM is built, can call the datatable
            }
        });
    }

    //control call to load the main report datatable
    function control_call(report_id, col_primkey, col_filterarray, col_itemlink, array_prefilter_data, col_aasort_array, fncreatedrow_action) {
        //        debugger;
        var whse = '<?php echo $var_whse; ?>';
        var schema = '<?php echo $schema; ?>';
        var build = <?php echo $var_build; ?>;
        if (col_primkey.length === 0) {
            col_primkey = 0; //default col_primkey to 0 if null
        }
        dt_load(whse, schema, build, report_id, col_primkey, col_itemlink, array_prefilter_data, col_filterarray, col_aasort_array, fncreatedrow_action);
    }

    //Main datatable jquery and settings
    function dt_load(whse, schema, build, report_id, col_primkey, col_itemlink, array_prefilter_data, col_filterarray, col_aasort_array, fncreatedrow_action) {
        // debugger;
        //default sorting
        sort_col = 0;
        sort_dir = 'asc';
        $.each(col_aasort_array, function (index, value) {
            sort_col = value.aasort;
            sort_dir = value.aasort_dir;
        });
        //set default sort
        if (sort_col === null) {
            sort_col = 0;
            sort_dir = 'asc';
        }
        if (report_id === 'shorts_expeditor') {
            window.shortsExpeditorDefaultOrder = [[sort_col, sort_dir]];
        }
        //debugger;
        $.each(col_primkey, function (index, value) {
            val_col_primkey = value.primkey;
        });

        oTable4 = $('#dt_reports').DataTable({
            dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
            destroy: true,
            'processing': true,
            "scrollX": true,
            "aoColumnDefs": [
                {
                    "aTargets": [col_itemlink], // Column to target
                    "mRender": function (data, type, full) {
                        // 'full' is the row's data object, and 'data' is this column's data
                        // e.g. 'full[col_itemlink]' is the comic id, and 'data' is the comic title
                        return '<a href="itemquery.php?itemnum=' + full[col_itemlink] + '" target="_blank" style="background-color: rgba(0, 128, 0, 0.5); color: white; padding: 5px 10px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block; transition: transform 0.3s ease-in-out;" onmouseover="this.style.transform=\'scale(1.1)\'" onmouseout="this.style.transform=\'scale(1)\'">' + data + '</a>';
                    }
                }
            ],

            "fnCreatedRow": function (nRow, aData, iDataIndex) {
                // debugger;
                $(nRow).addClass('hovercoloer');
                $(nRow).attr('data-report', report_id);
                // debugger;
                data_id = '';
                $.each(col_primkey, function (col_index) {
                    data_id += aData[col_index];
                });
                $(nRow).attr('id', data_id);
                if (fncreatedrow_action === 1) {
                    $('td:eq(0)', nRow).append("<div class='text-center'><i class='fa fa-check reviewclick' style='cursor: pointer; margin-right: 5px;' data-toggle='tooltip' title='Mark as Reviewed' data-reportid='" + report_id + "' ></i><i id='" + aData[3] + "' class='fa fa-comment addcomment' style='cursor: pointer;' data-toggle='tooltip' title='Add Comment' data-placement='top' data-container='body' ></i> </div>");
                }
                if (fncreatedrow_action === 2) {
                    var safeId = $('<div>').text(aData[2]).html(); // Escape the ID
                    $('td:eq(0)', nRow).append("<div class='text-center'><input id='" + aData[2] + "'  type='checkbox' class='input_checkbox' disabled></div>");
                }
                if (fncreatedrow_action === 3) {
                    // $(nRow).on('click', function () {
                    //     var newTab = window.open(window.location.href, '_blank');
                    //     newTab.onload = function () {
                    //         newTab.document.getElementById('empty_locs').click();
                    //     };
                    // });
                }
                if (fncreatedrow_action === 4) {
                    // New logic to highlight rows based on date criteria
                    var dateColumnIndex = 5;
                    var dateStr = aData[dateColumnIndex];
                    var date = new Date(dateStr);
                    var oneHourAgo = new Date(Date.now() - 3600 * 1000);

                    if (date < oneHourAgo) {
                        $(nRow).addClass('highlight-row');
                    }
                }

                // ── Shorts Expeditor special row rendering ──────────────────
                if (report_id === 'shorts_expeditor') {
                    var totalCount = parseInt(aData[9]) || 0;

                    // Row color by urgency (overrides highlight-row)
                    if (totalCount >= 6) {
                        $(nRow).css('background-color', 'rgba(220, 53, 69, 0.18)'); // red - urgent
                    } else if (totalCount >= 3) {
                        $(nRow).css('background-color', 'rgba(255, 193, 7, 0.22)'); // yellow - attention
                    } else {
                        $(nRow).css('background-color', 'rgba(40, 167, 69, 0.10)'); // green - normal
                    }

                    // Age badge on Request Datetime (col 5) — warn if > 2 hours old
                    var reqStr = aData[5];
                    if (reqStr) {
                        var reqDate = new Date(reqStr);
                        var twoHrsAgo = new Date(Date.now() - 2 * 3600 * 1000);
                        if (reqDate < twoHrsAgo) {
                            $('td:eq(5)', nRow).append(' <span class="badge badge-warning" title="Request is more than 2 hours old">OLD</span>');
                        }
                    }

                    // Reverse-fill Box Hold Lines (col 7), Shorts Count (col 8), and Total (col 9)
                    var lineVal  = parseInt(aData[7]) || 0;
                    var shortVal = parseInt(aData[8]) || 0;
                    if (lineVal > 0) {
                        $('td:eq(7)', nRow).html('<span class="badge badge-pill" style="background-color:#6f42c1;color:#fff;font-size:1em;padding:5px 9px;">' + lineVal + '</span>');
                    }
                    if (shortVal > 0) {
                        $('td:eq(8)', nRow).html('<span class="badge badge-pill badge-danger" style="font-size:1em;padding:5px 9px;">' + shortVal + '</span>');
                    }
                    // Total badge color mirrors row urgency
                    var totalBadgeColor = totalCount >= 6 ? '#dc3545' : (totalCount >= 3 ? '#fd7e14' : '#28a745');
                    $('td:eq(9)', nRow).html('<span class="badge badge-pill" style="background-color:' + totalBadgeColor + ';color:#fff;font-size:1em;padding:5px 9px;">' + totalCount + '</span>');

                    // Dismiss icon appended to last cell
                    $('td:last', nRow).append(
                        ' <i class="fa fa-times-circle dismiss_loc" style="cursor:pointer;color:#dc3545;margin-left:8px;opacity:0.7;" ' +
                        'data-loc="' + $('<div>').text(aData[4]).html() + '" ' +
                        'title="Dismiss this location for 1 hour" data-toggle="tooltip"></i>'
                    );
                }
            },
            "rowCallback": function (row, data, index) {
                // debugger;
                if (fncreatedrow_action === 1) {
                    if (data[1] !== null) {
                        $(row).addClass('recentcomment');
                    }
                    if (data[1] === 'SHOW COMMENTS') {  //add class to show comment so modal can be displayed
                        $('td:eq(1)', row).addClass("showallcomments");
                    }
                }
            },
            aaSorting: [[sort_col, sort_dir]],
            'ajax': {
                'type': 'POST',
                'url': '../global_dash/datatables/dt_slotreports.php',
                'data': {
                    whse: whse,
                    schema: schema,
                    build: build,
                    report_id: report_id,
                    array_prefilter_data: array_prefilter_data
                }
            },
            fixedHeader: true,
            buttons: [
                { extend: 'print', className: 'btn btn-primary separatedbutton' },
                { extend: 'excel', className: 'btn btn-primary separatedbutton' },
                { extend: 'copy', className: 'btn btn-primary separatedbutton' }
            ],
            createdRow: function (row, data, index) {

                $(row).addClass('hovercoloer'); //add hover color at the row level
                $(row).attr('id', data[val_col_primkey]); // set the id to the column primary key.  this is passed from the card_report_html() function
                $(row).attr('data-report', report_id);
            },
            initComplete: function () {
                //debugger;
                $('[data-toggle="tooltip"]').tooltip(); // Or your function for tooltips
                var table = this.api();
                $.each(col_filterarray, function (index, value) {
                    var column = table.column(value.col_sortorder);
                    debugger;
                    var trimmedHeading = value.col_heading.replace(/\s+/g, ''); // Trim spaces from the heading
                    var select = $('<select class="form-control"><option value=""></option></select>')
                        
                        .appendTo($('#' + trimmedHeading).empty().text(value.col_heading + ': ')) // Use trimmed heading for ID
                        .on('change', function () {
                            var val = $.fn.dataTable.util.escapeRegex(
                                $(this).val()
                            );
                            column
                                .search(val ? '^' + val + '$' : '', true, false)
                                .draw();
                        });
                    column.data().unique().sort().each(function (d, j) {
                        if (d !== null && d !== "null") { // Exclude null values
                            select.append('<option value="' + d + '">' + d + '</option>');
                        }
                    });
                });
                // debugger;
                var height = $("#tablefilters").height();
                if (height <= 130) {
                    height = height + 15;
                    $("#filteralert").css({ "height": height + "px" });
                }
                /* do more here if you want :) */

                // ── Shorts Expeditor: summary cards + auto-refresh ───────────
                if (report_id === 'shorts_expeditor') {
                    _shorts_expeditor_summary(table);
                    _shorts_expeditor_autorefresh(whse, schema, build, report_id, col_primkey, col_itemlink, array_prefilter_data, col_filterarray, col_aasort_array, fncreatedrow_action);
                }
            }
        });
    }

    //on click of the row, of main report datatable generate the modal html for the table displayed on modal
    $(document).on("click", "#dt_reports tbody tr", function (e) {
        // Prevent row modal when clicking actionable controls inside the row.
        if (
            $(e.target).is('a, button, input, select, textarea') ||
            $(e.target).closest('a, button, input, select, textarea, .reviewclick, .addcomment, .showallcomments, .dismiss_loc').length
        ) {
            return;
        }
        //        debugger;
        var report_id = $(this).attr('data-report'); //report clicked is passed through the data-report from the generated datatables row.

        // **** Special handling for italy_true_fit report ****
        if (report_id === 'italy_true_fit') {
            // Check if oTable4 is initialized and the row exists
            if (typeof oTable4 !== 'undefined' && oTable4.row(this).length) {
                var rowData = oTable4.row(this).data();
                // console.log("Italy True Fit Row Data:", rowData); // For debugging indices

                // Assuming indices based on italy_true_fit.php SELECT + calculated columns
                // Need to verify these indices if modal data is incorrect
                var reportData = {
                    sku: rowData[0],
                    udc: rowData[1],
                    udc_class: rowData[2],
                    udc_max: rowData[3],
                    udc_height: rowData[4],
                    udc_depth: rowData[5],
                    udc_width: rowData[6],
                    sku_height: rowData[7],
                    sku_depth: rowData[8],
                    sku_width: rowData[9],
                    act_true_fit: rowData[10], // Calculated in report source
                    fit_percent_diff: rowData[11] // Calculated in report source
                };

                // Check if data extraction was successful (simple check)
                if (reportData.sku !== undefined) {
                     $('#modal_reports').modal('toggle'); // Show the modal first
                     dt_load_modal_true_fit(reportData); // Call the specific modal function with full data
                } else {
                    console.error("Could not extract data from row for italy_true_fit.");
                    alert("Error loading details for this row.");
                }
            } else {
                console.error("DataTable oTable4 not ready or row not found for italy_true_fit.");
                alert("Error loading details for this row.");
            }
            return; // Prevent default modal logic below for this report
        }
        // **** End special handling ****

        // Default modal logic for other reports
        var col_primkey = (this.id); //id of clicked row is the item location from datatable #dt_live_shorts
        var whse = '<?php echo $var_whse; ?>';
        var schema = '<?php echo $schema; ?>';
        var build = <?php echo $var_build; ?>;
        $.ajax({
            url: '../global_dash/data_stats/reports_modal_tablehtml.php',
            data: { report_id: report_id, schema: schema, whse: whse },
            type: 'POST',
            dataType: 'html',
            success: function (ajaxresult_modal) {
                var responsedata_modal = $.parseJSON(ajaxresult_modal); //parses the json result containint the HTLM table variable and the primary key variable
                $("#tableDiv_modal").empty(); //reset the tablediv
                $('#modal_reports').modal('toggle');
                $("#tableDiv_modal").append(responsedata_modal.tablehtml_modal); //first value of result is the table
                $('#card_report_modal').removeClass('hidden'); //show the table
                control_call_modal(whse, schema, build, report_id, col_primkey); //once table HTLM is built, can call the datatable

            }
        });
    });

    function dt_load_modal_ipopps(col_primkey) {
        // debugger;
        $.ajax({
            url: '../global_dash/data_stats/reports_modal_ipopps.php',
            type: 'POST',
            data: { col_primkey: col_primkey },
            dataType: 'html',
            success: function (response) {
                var responsedata = $.parseJSON(response); //parses the json result containing the HTML table variable
                $("#tableDiv_modal").empty(); //reset the tablediv
                $("#tableDiv_modal").append(responsedata.tablehtml_modal); //append the table HTML
                $('#card_report_modal').removeClass('hidden'); //show the table
            }
        });
    }

    function control_call_modal(whse, schema, build, report_id, col_primkey) {
        //debugger;
        if (report_id === 'ip_opps') {
            dt_load_modal_ipopps(col_primkey);
        } else if (report_id === 'italy_true_fit') { // Add case for italy_true_fit
            dt_load_modal_true_fit(col_primkey);
        } else {
            dt_load_modal(whse, schema, build, report_id, col_primkey);
        }
    }

    function dt_load_modal(whse, schema, build, report_id, col_primkey) {
        //debugger;
        oTable6 = $('#dt_reports_modal').DataTable({
            dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
            //                    dom: 'Bfrtip',
            destroy: true,
            'processing': true,
            "scrollX": true,
            "ajax": {
                'type': 'POST',
                'url': '../global_dash/datatables/dt_slotreports_modal.php',
                'data': {
                    col_primkey: col_primkey,
                    whse: whse,
                    schema: schema,
                    build: build,
                    report_id: report_id
                }
            },
            buttons: [
                { extend: 'print', className: 'btn btn-primary separatedbutton' },
                { extend: 'excel', className: 'btn btn-primary separatedbutton' },
                { extend: 'copy', className: 'btn btn-primary separatedbutton' }
            ]
        });
    }

    // New function to load the content for the Italy True Fit modal
    function dt_load_modal_true_fit(reportData) { // Accept the full reportData object
        // debugger; // Keep debugger for potential future use

        $.ajax({
            url: '../global_dash/data_stats/italy_true_fit_modal.php', // Path to the new PHP file
            type: 'POST',
            // Pass the entire reportData object
            // PHP will access it via $_POST['reportData']['sku'], etc.
            data: { reportData: reportData },
            dataType: 'html', // Expecting HTML in the response for now, wrapped in JSON
            success: function (response) {
                // debugger;
                try {
                    var responsedata = $.parseJSON(response); // Parse the JSON response
                    $("#tableDiv_modal").empty(); // Reset the tablediv
                    $("#tableDiv_modal").append(responsedata.tablehtml_modal); // Append the HTML from PHP
                    $('#card_report_modal').removeClass('hidden'); // Show the modal content area
                } catch (e) {
                    console.error("Error parsing JSON response:", e);
                    console.error("Response received:", response);
                    // Optionally display an error message in the modal
                    $("#tableDiv_modal").html("<p>Error loading modal content.</p>");
                    $('#card_report_modal').removeClass('hidden');
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", status, error);
                // Optionally display an error message in the modal
                $("#tableDiv_modal").html("<p>Error loading modal content.</p>");
                $('#card_report_modal').removeClass('hidden');
            }
        });
    }

    //Go to external link page on click
    $(document).on("click", ".btn_click_extlink", function (event) {
        event.preventDefault();
        event.stopPropagation();
        var data_id = $(this).attr('data-id');
        var data_extpage = $(this).attr('data-extpage');
        switch (data_extpage) {
            case 'itemquery':
                document.cookie = "post-item=" + data_id;
                var date = new Date();
                date.setTime(date.getTime() + (30 * 1000));
                var expires = "expires=" + date;
                document.cookie = expires;
                var url = "itemquery.php";
                window.open(url, '_blank');
                break;
            default:
            // code to be executed if n is different from case 1 and 2
        }

    });

    //Go to external link page on click
    $(document).on("click", ".show_helpmodal", function (event) {
        //        debugger;
        event.preventDefault();
        event.stopPropagation();
        var report_id = $(this).closest('div').attr('id');
        
        // Show specific modal for recom_slot
        if (report_id === 'recom_slot') {
            $('#modal_help_recom_slot').modal('toggle');
            return;
        }

        $.ajax({
            url: '../global_dash/data_stats/reports_modal_help.php',
            data: { report_id: report_id },
            type: 'POST',
            dataType: 'html',
            success: function (ajaxresult_modal) {
                //                debugger;
                var responsedata_modal = $.parseJSON(ajaxresult_modal); //parses the json result containint the HTLM table variable and the primary key variable
                $("#helpdiv_modal").empty(); //reset the tablediv
                $("#title_helpmodal").empty(); //reset the tablediv title
                $('#modal_help').modal('toggle');
                $("#title_helpmodal").append(responsedata_modal.helphtml_modal[0]['rep_desc']); //first value of result is the table
                $("#helpdiv_modal").append(responsedata_modal.helphtml_modal[0]['rep_helptext']); //first value of result is the table
            }
        });
    });

    //Toggle review modal
    $(document).on("click", ".reviewclick", function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        $('#reviewmodal').modal('toggle');
        $('#itemnummodal').val($(this).closest('tr').find('td:eq(3)').text());
        $('#reportid').val($(this).attr('data-reportid'));
        return false;
    });

    //mark item as reviewed through mysql post
    $(document).on("click", "#addreview", function (event) {
        event.preventDefault();
        var itemnum = $('#itemnummodal').val();
        var report_id = $('#reportid').val();
        //        debugger;
        $.ajax({
            url: '../global_dash/post/markasreviewed.php',
            data: {
                report_id: report_id,
                itemnum: itemnum
            },
            type: 'POST',
            dataType: 'html',
            success: function (ajaxresult) {
                $('#reviewmodal').modal('hide');
                $('#dt_reports').DataTable().ajax.reload();
            }
        });

    });

    //if all comments for an item is wanted to be viewed through modal
    $(document).on("click", ".showallcomments", function (event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        //        debugger;
        $('#allcommentsmodal').modal('toggle');
        $('#commentmodal_container').addClass('hidden');
        var itemnum = $(this).closest('tr').find('td:eq(3)').text();
        $.ajax({
            url: '../global_dash/data_stats/commentsbyitem.php',
            data: { itemnum: itemnum },
            type: 'POST',
            dataType: 'html',
            success: function (ajaxresult) {
                $('#commentmodal_container').removeClass('hidden');
                $("#commentdetaildata").html(ajaxresult);
            }
        });

    });

    //function to mark selected items as moved
    $(document).on("click", "#btn_addtoslotfile", function (e) {
        // debugger;
        var selectedData = [];

        // Loop through each row in the DataTable
        $('#dt_reports tbody tr').each(function () {
            var $row = $(this);
            var isChecked = $row.find('input.input_checkbox').is(':checked');

            if (isChecked) {
                // Collect data from the row
                var rowData = {
                    // Collecting the necessary data from the row
                    item: $row.find('td:eq(2)').text(), // Column 2 called "item"
                    sugg_tier: $row.find('td:eq(11)').text() // Column 11 called "Sugg_Tier"
                };
                selectedData.push(rowData);
            }
        });

        // Send the selected data to the server via AJAX
        $.ajax({
            url: 'path/to/your/server/endpoint.php',
            type: 'POST',
            data: { selectedData: selectedData },
            success: function (response) {
                // Handle the response from the server
                console.log(response);
            },
            error: function (xhr, status, error) {
                // Handle any errors
                console.error(error);
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        //  debugger;
        var openReport = localStorage.getItem('openReport');
        var reportDate = localStorage.getItem('reportDate');

        if (openReport && reportDate) {
            localStorage.removeItem('openReport');
            localStorage.removeItem('reportDate');

            var reportButton = document.getElementById(openReport);
            if (reportButton) {
                reportButton.click();

                // Set up a MutationObserver to watch for the date pickers
                var observer = new MutationObserver(function (mutations) {
                    var startFiscalPicker = document.getElementById('startfiscal');
                    var endFiscalPicker = document.getElementById('endfiscal');
                    if (startFiscalPicker && endFiscalPicker) {
                        var dateParts = reportDate.split('-');
                        var formattedDate =
                            dateParts[1].padStart(2, '0') + '/' +
                            dateParts[2].padStart(2, '0') + '/' +
                            dateParts[0];
                        startFiscalPicker.value = formattedDate;
                        endFiscalPicker.value = formattedDate;
                        observer.disconnect(); // Stop observing once we've set the dates

                        // Trigger the report to load with the selected date
                        prefilter_click(openReport);
                    }
                });

                // Start observing the document with the configured parameters
                observer.observe(document.body, { childList: true, subtree: true });
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        } else {
            // If not coming from the event listener, set to today's date

            var today = new Date();

            var formattedToday = ('0' + (today.getMonth() + 1)).slice(-2) + '/' + ('0' + today.getDate()).slice(-2) + '/' + today.getFullYear(); // Format: MM/DD/YYYY

            var observer = new MutationObserver(function (mutations) {
                var startFiscalPicker = document.getElementById('startfiscal');

                var endFiscalPicker = document.getElementById('endfiscal');

                if (startFiscalPicker && endFiscalPicker) {

                    startFiscalPicker.value = formattedToday;

                    endFiscalPicker.value = formattedToday;
                    observer.disconnect(); // Stop observing once we've set the dates

                }
            });

            // Start observing the document with the configured parameters
            observer.observe(document.body, { childList: true, subtree: true });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });

    // ── Shorts Expeditor: dismiss a location for 1 hour ─────────────────────
    $(document).on('click', '.dismiss_loc', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var loc = $(this).attr('data-loc');
        _shorts_expeditor_manage_omitted('omit', loc, 'Location ' + loc + ' omitted for 1 hour.');
    });

    $(document).on('click', '#shortsExpeditorManageBtn', function (e) {
        e.preventDefault();
        _shorts_expeditor_open_omitted_panel();
    });

    $(document).on('click', '#shortsExpeditorSortOldBtn', function (e) {
        e.preventDefault();
        _shorts_expeditor_sort_old_first();
    });

    $(document).on('click', '#shortsExpeditorSortDefaultBtn', function (e) {
        e.preventDefault();
        _shorts_expeditor_sort_default();
    });

    $(document).on('click', '[data-close-shortsexp-manager="1"]', function (e) {
        e.preventDefault();
        _shorts_expeditor_close_omitted_panel();
    });

    $(document).on('click', '#shortsExpeditorManager', function (e) {
        if (e.target === this) {
            _shorts_expeditor_close_omitted_panel();
        }
    });

    $(document).on('keydown', function (e) {
        if (e.which === 27 && _shorts_expeditor_manager_is_open()) {
            _shorts_expeditor_close_omitted_panel();
        }
    });

    $(document).on('click', '#shortsExpeditorRefreshList', function (e) {
        e.preventDefault();
        _shorts_expeditor_load_omitted_locations(false);
    });

    $(document).on('click', '#shortsExpeditorAddLoc', function (e) {
        e.preventDefault();

        var loc = $.trim($('#shortsExpeditorNewLoc').val()).toUpperCase();
        if (loc.length === 0) {
            _shorts_expeditor_set_manager_message('error', 'Enter a location before omitting it.');
            $('#shortsExpeditorNewLoc').trigger('focus');
            return;
        }

        _shorts_expeditor_manage_omitted('omit', loc, 'Location ' + loc + ' omitted for 1 hour.');
        $('#shortsExpeditorNewLoc').val('');
    });

    $(document).on('keydown', '#shortsExpeditorNewLoc', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#shortsExpeditorAddLoc').trigger('click');
        }
    });

    $(document).on('click', '.shortsExpeditorRestoreLoc', function (e) {
        e.preventDefault();

        var loc = $(this).attr('data-loc');
        _shorts_expeditor_manage_omitted('restore', loc, 'Location ' + loc + ' added back.');
    });

    $(document).on('click', '.shortsExpeditorResetLoc', function (e) {
        e.preventDefault();

        var loc = $(this).attr('data-loc');
        _shorts_expeditor_manage_omitted('omit', loc, 'Location ' + loc + ' reset for another hour.');
    });

    $(document).on('click', '#shortsExpeditorClearAll', function (e) {
        e.preventDefault();

        _shorts_expeditor_manage_omitted('clear_all', '', 'All omitted locations cleared.');
    });

    // ── Shorts Expeditor helpers ─────────────────────────────────────────────

    function _shorts_expeditor_summary(table) {
        _shorts_expeditor_ensure_manager_shell();
        $('#shorts_exp_summary').remove();

        var rows       = table.data().toArray();
        var totalLocs  = rows.length;
        var totalHolds = 0;
        var totalLines = 0;
        var totalShorts= 0;
        var urgent     = 0;
        var lseCount   = 0;
        var cseCount   = 0;

        rows.forEach(function (r) {
            totalHolds  += parseInt(r[6]) || 0;
            totalLines  += parseInt(r[7]) || 0;
            totalShorts += parseInt(r[8]) || 0;
            var tot      = parseInt(r[9]) || 0;
            if (tot >= 6)   urgent++;
            if (r[0] === 'LSE') lseCount++;
            else if (r[0] === 'CSE') cseCount++;
        });

        var html =
            '<div id="shorts_exp_summary" class="row" style="margin: 8px 0 12px 0; flex-wrap: wrap;">' +
            _se_card(totalLocs,   'Locations',     'fa-map-marker-alt', '#17a2b8') +
            _se_card(totalHolds,  'Box Holds',     'fa-boxes',          '#6f42c1') +
            _se_card(totalLines,  'Hold Lines',    'fa-list',           '#6f42c1') +
            _se_card(totalShorts, 'Shorts Today',  'fa-exclamation-triangle', '#fd7e14') +
            _se_card(urgent,      'Urgent (6+)',   'fa-fire',           '#dc3545') +
            _se_card(lseCount,    'LSE Locs',      'fa-th-large',       '#28a745') +
            _se_card(cseCount,    'CSE Locs',      'fa-pallet',         '#6c757d') +
            '<div class="col" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;padding:4px;gap:10px;">' +
            '<small class="text-muted"><i class="fa fa-clock-o" style="margin-right:4px;"></i>' +
            'Auto-refreshes every 4 min &nbsp;|&nbsp; ' +
            '<i class="fa fa-times-circle text-danger"></i> = dismiss location for 1 hr</small>' +
            '<div style="display:flex;flex-wrap:wrap;gap:8px;">' +
            '<button type="button" id="shortsExpeditorSortOldBtn" class="btn btn-outline-secondary btn-sm" style="border-radius:999px;padding:7px 14px;font-weight:700;">' +
            '<i class="fa fa-clock-o" style="margin-right:6px;"></i>Old &gt; 2h First' +
            '</button>' +
            '<button type="button" id="shortsExpeditorSortDefaultBtn" class="btn btn-outline-secondary btn-sm" style="border-radius:999px;padding:7px 14px;font-weight:700;">' +
            '<i class="fa fa-sort-amount-desc" style="margin-right:6px;"></i>Default Sort' +
            '</button>' +
            '<button type="button" id="shortsExpeditorManageBtn" class="btn btn-light btn-sm" style="border:1px solid #d6dbe3;border-radius:999px;padding:7px 14px;font-weight:700;box-shadow:0 8px 20px rgba(15,23,42,0.08);">' +
            '<i class="fa fa-sliders" style="margin-right:6px;color:#495057;"></i>Manage Omitted ' +
            '<span id="shortsExpeditorOmittedCount" class="badge badge-pill badge-dark" style="margin-left:6px;">0</span>' +
            '</button>' +
            '</div>' +
            '</div>' +
            '</div>';

        $('#card_report .card-header').after(html);
        _shorts_expeditor_refresh_omitted_count();
    }

    function _se_card(value, label, icon, color) {
        var borderStyle = value > 0 ? 'border-left: 4px solid ' + color + ';' : 'border-left: 4px solid #dee2e6;';
        var valColor    = value > 0 ? color : '#adb5bd';
        return '<div class="col-auto" style="padding: 4px;">' +
            '<div class="card text-center" style="min-width:90px;' + borderStyle + 'padding:8px 12px;">' +
            '<div style="font-size:1.5em;font-weight:700;color:' + valColor + ';">' + value + '</div>' +
            '<div style="font-size:0.72em;color:#666;white-space:nowrap;">' +
            '<i class="fa ' + icon + '" style="margin-right:3px;"></i>' + label +
            '</div></div></div>';
    }

    function _shorts_expeditor_autorefresh(whse, schema, build, report_id, col_primkey, col_itemlink, array_prefilter_data, col_filterarray, col_aasort_array, fncreatedrow_action) {
        clearInterval(window.shortsExpeditorRefresh);
        window.shortsExpeditorRefresh = setInterval(function () {
            _shorts_expeditor_reload_table();
        }, 245000);
    }

    // Define your loadReport function if it doesn't exist
    function loadReport(reportId, date) {
        // Implement the logic to load the report with the given date
        // This will depend on how your report loading system is set up
        console.log('Loading report:', reportId, 'with date:', date);
        // Your report loading code here...

    }

    function saveSelectedReports() {
        var selectedReports = [];
        $('#reportsList input:checked').each(function () {
            selectedReports.push($(this).attr('id').replace('report_', ''));
        });

        $.ajax({

            url: '../global_dash/data_stats/save_selected_reports.php',
            type: 'POST',
            data: { selectedReports: selectedReports },
            success: function (response) {
                console.log('Reports saved successfully:', response);
                $('#selectReportsModal').modal('hide');
                location.reload(); // Reload the page to reflect changes
            },
            error: function (xhr, status, error) {
                console.error('Error saving reports:', error);
            }
        });
    }
    // Save selected reports
    $(document).on('click', '#saveReportsSelection', function () {
        saveSelectedReports();
    });

    // Show the modal and load available reports when the button is clicked
    $(document).on('click', '#showSelectReportsModal', function () {
        $('#selectReportsModal').modal('show');
    });

</script>
