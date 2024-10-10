;(function ($) {
    'use strict'

    var product_option = window.WPFunnelVars.products
    var selectedIds = []

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    jQuery(document).ready(function ($) {
        //date format
        function PresentDateForm(date) {
            var date = new Date(date),
                day = date.getDate(),
                month = date.getMonth() + 1, //Months are zero based
                year = date.getFullYear(),
                date = new Date(year + '-' + month + '-' + day).toDateString()
            return date
        }

        function CompareDateForm(date) {
            var date = new Date(date),
                day = date.getDate(),
                month = date.getMonth() + 1, //Months are zero based
                year = date.getFullYear() - 1,
                date = new Date(year + '-' + month + '-' + day).toDateString()
            return date
        }

        // -------analytics line chart filter-------
        function wpfnlDatePicker() {
            $('.wpfunnel-datepicker').datepicker({
                maxDate: 0,
                changeMonth: true,
                changeYear: true,
                showOtherMonths: true,
                selectOtherMonths: true,
                beforeShowDay: function (date) {
                    let date1 = $.datepicker.parseDate(
                        $.datepicker._defaults.dateFormat,
                        $('#date_from_value').val(),
                    )
                    let date2 = $.datepicker.parseDate(
                        $.datepicker._defaults.dateFormat,
                        $('#date_to_value').val(),
                    )

                    let isStartDate = date1 && date.getTime() === date1.getTime()
                    let isEndDate = date2 && date.getTime() === date2.getTime()

                    return [
                        true,
                        isStartDate || (date2 && date >= date1 && date <= date2) ? 'in-range' : '',
                        isStartDate ? 'Start' : isEndDate ? 'End' : '',
                    ]
                },
                onSelect: function (dateText, inst) {
                    let date1 = $.datepicker.parseDate(
                        $.datepicker._defaults.dateFormat,
                        $('#date_from_value').val(),
                    )
                    let date2 = $.datepicker.parseDate(
                        $.datepicker._defaults.dateFormat,
                        $('#date_to_value').val(),
                    )
                    let selectedDate = $.datepicker.parseDate(
                        $.datepicker._defaults.dateFormat,
                        dateText,
                    )
                    const months = [
                        'Jan',
                        'Feb',
                        'Mar',
                        'Apr',
                        'May',
                        'Jun',
                        'Jul',
                        'Aug',
                        'Sep',
                        'Oct',
                        'Nov',
                        'Dec',
                    ]
                    const formattedDate =
                        months[selectedDate.getMonth()] +
                        ' ' +
                        selectedDate.getDate() +
                        ', ' +
                        selectedDate.getFullYear()

                    if (!date1 || date2) {
                        $('#date-from').val(formattedDate)
                        $('#date_from_value').val(dateText)
                        $('li.select-from-date').text(dateText).css('color', '#2D3149')

                        $('#date-to').val('')
                        $('#date_to_value').val('')
                        $(this).datepicker()
                        $('.analytics-header .custom-date-range').show()
                    } else if (selectedDate < date1) {
                        $('#date-to').val($('#date-from').val())
                        $('#date_to_value').val($('#date_from_value').val())
                        $('#date-from').val(formattedDate)
                        $('#date_from_value').val(dateText)

                        $('li.select-to-date')
                            .text($('li.select-from-date').text())
                            .css('color', '#2D3149')
                        $('li.select-from-date').text(dateText).css('color', '#2D3149')

                        $(this).datepicker()
                    } else {
                        $('#date-to').val(formattedDate)
                        $('#date_to_value').val(dateText)
                        $('li.select-to-date').text(dateText).css('color', '#2D3149')
                        $(this).datepicker()
                        $('.analytics-header .custom-date-range').show()
                    }
                },
            })
        }

        $('.wpfnl_muliple_select').select2()
        // edit field error handle
        $('.wpfnl-section-type-error').hide()
        $('.wpfnl-field-type-error').hide()
        $('.wpfnl-name-type-error').hide()
        $('.wpfnl-label-type-error').hide()

        //--------show calendar when select as custom filter----------
        $(document).on('change', '#analytics-filter', function () {
            let selectedValue = $(this).find(':selected').val()

            if ('custom' === selectedValue) {
                var selectedDate = $('#date-from').val()
                $('.wpfnl-analytics .overlay-click').show()
                $('.analytics-header .custom-date-range').show()
                $('.analytics-header .header-right').addClass('show-calendar')
            } else {
                $('.wpfnl-analytics .overlay-click').hide()

                $('.analytics-header .header-right').removeClass('show-calendar')
                $('.analytics-header .custom-date-range').hide()
            }
        })

        //--------hide calendar when click on calender filter button----------
        $(document).on('click', '.do-filter', function (e) {
            $('.analytics-header .header-right').removeClass('show-calendar')
            $('.wpfnl-analytics .overlay-click').hide()
        })

        //--------analytics calendar filter dropdown----------
        $(document).on('click', '.filter-selectbox', function (e) {
            e.stopPropagation()
            $(this).parents('.header-right').toggleClass('show-calendar')
            $('.wpfnl-analytics .overlay-click').show()
        })
        $(document).on('click', '.calendar-dorpdown', function (e) {
            e.stopPropagation()
        })
        $(document).on('click', '.wpfnl-analytics .overlay-click', function () {
            $(this).hide()
            $('.analytics-header .header-right').removeClass('show-calendar')
        })

        //--------analytics filter header dropdown----------
        $(document).on('click', '.funnel-steps > li', function (e) {
            e.preventDefault()
            e.stopPropagation()
            $(this).addClass('active')
            $(this).siblings('li').removeClass('active')
            $(this).children('.wpfnl-dropdown').toggleClass('show-dropdown')
            $(this).siblings('li').children('.wpfnl-dropdown').removeClass('show-dropdown')
        })
        $(document).on('click', 'body', function () {
            $('.chart-header .funnel-steps .wpfnl-dropdown').removeClass('show-dropdown')
        })

        // -------show/hide step stats info-------
        $(document).on('change', '#stats', function (e) {
            if ($(this).is(':checked')) {
                $('.node-wrapper .node-stats').show()
            } else {
                $('.node-wrapper .node-stats').hide()
            }
        })

        var FunnelHandler = function () {
            this.initStepSortable()
            this.initProductSortable()
            $(document.body).on('submit', '#wpfnl-change-funnel-name', this.changeFunnelName)

            $('.wpfnl-duplicate-funnel').on('click', this.cloneFunnel)
            $('.wpfnl-delete-funnel').on('click', this.deleteFunnel)
            $('.wpfnl-permanent-delete-funnel').on('click', this.permanentDeleteFunnel)
            $('.wpfnl-restore-funnel').on('click', this.restoreFunnel)
            $('.wpfnl-update-funnel-status').on('click', this.UpdateFunnelStatus)
            $('.wpfnl-export-funnel').on('click', this.ExportFunnel)
            $('.wpfnl-export-all-funnels').on('click', this.ExportAllFunnel)
            $('#wpfnl-import-funnels').on('submit', this.ImportFunnels)
            $('#wpfnl-import-funnels').on('change', this.UploadFile)
            $('.wpfnl-import-funnels').on('click', this.ShowImportFunnelModal)
            $(
                '.import-funnel-modal .close-modal, .import-funnel-modal .import-funnel-modal-inner',
            ).on('click', this.CloseImportFunnelModal)
            $('.import-funnel-modal .import-funnel-modal-wrapper').on(
                'click',
                this.PreventImportFunnelModal,
            )

            $(document.body).on('click', '.wpfnl-bulk-trash', this.bulkTrashFunnel)
            $(document.body).on('click', '.wpfnl-bulk-delete', this.bulkDeleteFunnel)
            $(document.body).on('click', '.wpfnl-bulk-restore', this.bulkRestoreFunnel)
            $(document.body).on('click', '.wpfnl-bulk-export', this.bulkExportFunnel)
        }

        /**
         * Select quantity
         *
         * @since 1.0.0
         */
        $('input[name=quantity]').change(function () {
            var val = parseInt(this.value)
            var product = $(this).attr('data-product')

            for (var i in product_option) {
                if (product_option[i].id == product) {
                    product_option[i].quantity = val
                    break //Stop this loop, we found it!
                }
            }
        })

        /**
         * Subtext Setup
         *
         * @since 1.0.0
         */
        $('input[name=subtext]').change(function () {
            var val = $(this).val()
            var product = $(this).attr('data-product')

            for (var i in product_option) {
                if (product_option[i].id == product) {
                    product_option[i].subtext = val
                    break //Stop this loop, we found it!
                }
            }
        })

        /**
         * Highlight Text Setup
         *
         * @since 1.0.0
         */
        $('input[name=text-highlight]').change(function () {
            var val = $(this).val()
            var product = $(this).attr('data-product')

            for (var i in product_option) {
                if (product_option[i].id == product) {
                    product_option[i].text_highlight = val
                    break //Stop this loop, we found it!
                }
            }
        })

        /**
         * Enable Highlight setup
         *
         * @since 1.0.0
         */
        $('input[name=hide-img-mobile]').change(function () {
            if (this.checked) {
                var val = 'on'
            } else {
                var val = 'off'
            }

            var product = $(this).attr('data-product')

            for (var i in product_option) {
                if (product_option[i].id == product) {
                    product_option[i].enable_highlight = val
                    break //Stop this loop, we found it!
                }
            }
        })

        /**
         * Sort step order
         *
         * @since 1.0.0
         */
        FunnelHandler.prototype.initStepSortable = function () {
            $('#wpfnl-funnel-step-lists').sortable({
                axis: 'y',
                start: function (event, ui) {},
                update: function (event, ui) {
                    var order = $('#wpfnl-funnel-step-lists').sortable('toArray')

                    var funnel_id = getUrlParameter('id')

                    var payload = {
                        funnel_id: funnel_id,
                        order: order,
                    }
                    wpAjaxHelperRequest('funnel-drag-order', payload)
                        .success(function (response) {})
                        .error(function (response) {
                            // console.log('error');
                        })
                },
            })
            $('#wpfnl-funnel-step-lists').disableSelection()
        }

        /**
         * Sort product accordion order
         *
         * @since 1.0.0
         */
        FunnelHandler.prototype.initProductSortable = function () {
            $('.product-single-accordion__sortable-wrapper').sortable({
                axis: 'y',
                start: function (event, ui) {},
                update: function (event, ui) {
                    var product_order = $('.product-single-accordion__sortable-wrapper').sortable(
                        'toArray',
                    )

                    product_option = []
                    var funnel_id = getUrlParameter('id')

                    for (var i in product_order) {
                        var dragger_id = product_order[i]
                        var prod_id = dragger_id.replace('product__single-accordion-', '')
                        var quantity = $('#product-quantity-' + prod_id).val()
                        product_option.push({
                            id: prod_id,
                            quantity: quantity,
                        })
                    }
                },
            })
            $('.product-single-accordion__sortable-wrapper').disableSelection()
        }

        /**
         *
         * Ajax handler for duplicate
         * funnel action
         *
         * @param event
         * @since 1.0.0
         */
        FunnelHandler.prototype.cloneFunnel = function (event) {
            event.preventDefault()
            var funnel_id = $(this).attr('data-id'),
                loader = $(this).find('.wpfnl-loader')
            var payload = {
                funnel_id: funnel_id,
            }

            $(this).css('pointer-events', 'none')
            loader.show()

            wpAjaxHelperRequest('clone-funnel', payload)
                .success(function (response) {
                    window.location.href = response.redirectUrl
                    loader.hide()
                })
                .error(function (response) {
                    loader.hide()
                })
        }

        /**
         * delete funnel and all the related
         * data
         *
         * @param event
         * @since 1.0.0
         */
        FunnelHandler.prototype.deleteFunnel = function (event) {
            event.preventDefault()
            var funnel_id = $(this).attr('data-id')
            var payload = {
                funnel_id: funnel_id,
            }
            if (confirm('Are you sure?')) {
                wpAjaxHelperRequest('trash-funnel', payload)
                    .success(function (response) {
                        window.location.href = response.redirectUrl
                    })
                    .error(function (response) {})
            }
        }

        /**
         * delete funnel and all the related
         * data
         *
         * @param event
         * @since 1.0.0
         */
        FunnelHandler.prototype.permanentDeleteFunnel = function (event) {
            event.preventDefault()
            var funnel_id = $(this).attr('data-id')
            var payload = {
                funnel_id: funnel_id,
            }
            if (confirm('Are you sure?')) {
                wpAjaxHelperRequest('delete-funnel', payload)
                    .success(function (response) {
                        window.location.href = response.redirectUrl
                    })
                    .error(function (response) {})
            }
        }

        /**
         * delete funnel and all the related
         * data
         *
         * @param event
         * @since 1.0.0
         */
        FunnelHandler.prototype.restoreFunnel = function (event) {
            event.preventDefault()
            var funnel_id = $(this).attr('data-id')
            var payload = {
                funnel_id: funnel_id,
            }
            if (confirm('Are you sure?')) {
                wpAjaxHelperRequest('restore-funnel', payload)
                    .success(function (response) {
                        window.location.href = response.redirectUrl
                    })
                    .error(function (response) {})
            }
        }

        /**
         * bulk delete funnel and all the related
         * data
         *
         * @param event
         * @since 1.0.0
         */
        FunnelHandler.prototype.bulkDeleteFunnel = function (event) {
            event.preventDefault()
            let funnel_ids = selectedIds

            let payload = {
                ids: funnel_ids,
            }

            if (confirm('Are you sure?')) {
                fetch(
                    `${window.template_library_object.rest_api_url}wpfunnels/v1/funnel-control/bulk-delete-funnel`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': wpApiSettings.nonce,
                        },
                        body: JSON.stringify(payload),
                    },
                )
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            window.location.href = data.redirectUrl
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error)
                    })
            }
            selectedIds = []
        }

        /**
         * bulk trash funnel and all the related
         * data
         *
         * @param event
         * @since 1.0.0
         */
        FunnelHandler.prototype.bulkTrashFunnel = function (event) {
            event.preventDefault()
            let funnel_ids = selectedIds

            let payload = {
                ids: funnel_ids,
            }

            if (confirm('Are you sure?')) {
                fetch(
                    `${window.template_library_object.rest_api_url}wpfunnels/v1/funnel-control/bulk-trash-funnel`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': wpApiSettings.nonce,
                        },
                        body: JSON.stringify(payload),
                    },
                )
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            window.location.href = data.redirectUrl
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error)
                    })
            }
            selectedIds = []
        }

        /**
         * bulk delete funnel and all the related
         * data
         *
         * @param event
         * @since 1.0.0
         */
        FunnelHandler.prototype.bulkRestoreFunnel = function (event) {
            event.preventDefault()
            let funnel_ids = selectedIds

            let payload = {
                ids: funnel_ids,
            }

            if (confirm('Are you sure?')) {
                fetch(
                    `${window.template_library_object.rest_api_url}wpfunnels/v1/funnel-control/bulk-restore-funnel`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': wpApiSettings.nonce,
                        },
                        body: JSON.stringify(payload),
                    },
                )
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            window.location.href = data.redirectUrl
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error)
                    })
            }
            selectedIds = []
        }

        /**
         * Update funnel status
         * data
         *
         * @param event
         * @since 1.0.0
         */
        FunnelHandler.prototype.UpdateFunnelStatus = function (event) {
            event.preventDefault()
            var funnel_id = $(this).attr('data-id')
            var new_status = $(this).attr('data-status')
            var payload = {
                funnel_id: funnel_id,
                status: new_status,
            }
            if (confirm('Are you sure?')) {
                wpAjaxHelperRequest('update-funnel-status', payload)
                    .success(function (response) {
                        window.location.href = response.redirect_url
                    })
                    .error(function (response) {})
            }
        }

        /**
         *
         * Ajax handler for export a single funnel
         * funnel action
         *
         * @param event
         * @since 2.6.3
         */
        FunnelHandler.prototype.ExportFunnel = function (event) {
            event.preventDefault()

            var funnel_id = $(this).attr('data-id'),
                loader = $(this).find('.wpfnl-loader')
            var payload = {
                funnel_id: funnel_id,
            }

            $(this).css('pointer-events', 'none')
            loader.show()

            wpAjaxHelperRequest('wpfnl-export-funnel', payload)
                .success(function (response) {
                    if (response.success) {
                        const jsonData = response.steps
                        const filename = response.title + '.json'
                        download(JSON.stringify(jsonData), filename, 'text/plain')
                    }
                    loader.hide()
                })
                .error(function (response) {
                    loader.hide()
                })
        }

        /**
         *
         * Ajax handler for export a single funnel
         * funnel action
         *
         * @param event
         * @since 2.6.3
         */
        FunnelHandler.prototype.ExportAllFunnel = function (event) {
            event.preventDefault()

            var status = 'all',
                loader = $(this).find('.wpfnl-loader')
            var payload = {
                status: status,
            }

            $(this).css('pointer-events', 'none')
            loader.show()

            wpAjaxHelperRequest('wpfnl-export-all-funnels', payload)
                .success(function (response) {
                    if (response.success) {
                        const jsonData = response.data

                        var today = new Date()
                        var dd = String(today.getDate()).padStart(2, '0')
                        var mm = String(today.getMonth() + 1).padStart(2, '0') //January is 0!
                        var yyyy = today.getFullYear()
                        today = mm + '-' + dd + '-' + yyyy
                        const filename = 'wpfunnels-export-' + today + '-.json'

                        download(JSON.stringify(jsonData), filename, 'text/plain')
                    }
                    loader.hide()
                })
                .error(function (response) {
                    loader.hide()
                })
        }

        /**
         *
         * Ajax handler for export bulk funnels
         * funnel action
         *
         * @param event
         * @since 2.7.19
         */
        FunnelHandler.prototype.bulkExportFunnel = function (event) {
            event.preventDefault()

            let funnel_ids = selectedIds

            let status = 'all',
                loader = $(this).find('.wpfnl-loader')
            let payload = {
                status: status,
                ids: funnel_ids,
            }

            $(this).css('pointer-events', 'none')
            loader.show()

            fetch(
                `${window.template_library_object.rest_api_url}wpfunnels/v1/import-export/bulk-export-funnel`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpApiSettings.nonce,
                    },
                    body: JSON.stringify(payload),
                },
            )
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        const jsonData = data.data

                        let today = new Date()
                        let dd = String(today.getDate()).padStart(2, '0')
                        let mm = String(today.getMonth() + 1).padStart(2, '0') // January is 0!
                        let yyyy = today.getFullYear()
                        today = mm + '-' + dd + '-' + yyyy
                        const filename = 'wpfunnels-export-' + today + '-.json'

                        download(JSON.stringify(jsonData), filename, 'text/plain')
                    }
                    loader.hide()
                    window.location.href = data.redirectUrl
                })
                .catch((error) => {
                    console.error('Error:', error)
                    loader.hide()
                })
            selectedIds = []
        }

        /**
         * Show import funnels Modal
         *
         * @param event
         * @since 2.6.3
         */
        FunnelHandler.prototype.ShowImportFunnelModal = function (e) {
            e.stopPropagation()
            e.preventDefault()
            $('.import-funnel-modal').addClass('show-modal')
        }

        /**
         * Stop Propagation for .import-funnel-modal-wrapper
         *
         */
        FunnelHandler.prototype.PreventImportFunnelModal = function (e) {
            e.stopPropagation()
        }

        /**
         * Close import funnels Modal
         *
         * @param event
         * @since 2.6.3
         */
        FunnelHandler.prototype.CloseImportFunnelModal = function (e) {
            e.preventDefault()
            $('.import-funnel-modal').removeClass('show-modal')
            $('#wpfnl-export-import-warning').css('display', 'none')
        }

        /**
         * Show import funnels Modal
         *
         * @param event
         * @since 2.6.3
         */
        FunnelHandler.prototype.ShowImportFunnelModal = function (e) {
            e.stopPropagation()
            e.preventDefault()
            $('.import-funnel-modal').addClass('show-modal')
        }

        /**
         * Stop Propagation for .import-funnel-modal-wrapper
         *
         */
        FunnelHandler.prototype.PreventImportFunnelModal = function (e) {
            e.stopPropagation()
        }

        /**
         * Close import funnels Modal
         *
         * @param event
         * @since 2.6.3
         */
        FunnelHandler.prototype.CloseImportFunnelModal = function (e) {
            e.preventDefault()
            $('.import-funnel-modal').removeClass('show-modal')
            $('#wpfnl-export-import-warning').css('display', 'none')
        }

        /**
         *
         * Ajax handler for import funnels
         * funnel action
         *
         * @param event
         * @since 2.6.3
         */
        FunnelHandler.prototype.ImportFunnels = function (event) {
            event.preventDefault()
            $('#wpfnl-import-funnel .wpfnl-loader').css('display', 'inline-block')
            $('#wpfnl-export-import-warning').css('display', 'none')
            let formData = new FormData(this)
            let importFile = $('#wpfnl-file-import')[0].files[0]
            if (importFile) {
                const fileName = importFile?.name
                const fileExtension = fileName.split('.').pop()
                if ('json' === fileExtension) {
                    formData.append('action', 'wpfnl_import_funnels')
                    formData.append('is_ajax', 'yes')
                    formData.append('uploaded_file', importFile)

                    $.ajax({
                        url: WPFunnelVars.ajaxurl,
                        type: 'post',
                        contentType: false,
                        dataType: 'json',
                        processData: false,
                        data: formData,
                        success: function (response) {
                            if (response.success) {
                                window.location.reload()
                            }
                            $('#wpfnl-import-funnel .wpfnl-loader').css('display', 'none')
                            $('.import-funnel-modal').removeClass('show-modal')
                        },
                    }).done(function (request, status, XHR) {
                        if (status.success) {
                            // window.location.reload();
                        }
                        $('#wpfnl-import-funnel .wpfnl-loader').css('display', 'none')
                        $('.import-funnel-modal').removeClass('show-modal')
                    })
                } else {
                    $('#wpfnl-export-import-warning').css('display', 'inline-block')
                    $('#wpfnl-export-import-warning').css('color', '#d63638 !important')
                    $('#wpfnl-import-funnel .wpfnl-loader').css('display', 'none')
                }
            } else {
                $('#wpfnl-export-import-warning').css('display', 'inline-block')
                $('#wpfnl-export-import-warning').css('color', '#d63638 !important')
                $('#wpfnl-import-funnel .wpfnl-loader').css('display', 'none')
            }
        }

        /**
         *
         * Ajax handler for import funnels
         * funnel action
         *
         * @param event
         * @since 2.6.3
         */
        FunnelHandler.prototype.UploadFile = function (event) {
            event.preventDefault()
            $('#wpfnl-export-import-warning').css('display', 'none')
            let importFile = $('#wpfnl-file-import')[0].files[0]

            if (importFile) {
                const fileName = importFile?.name
                const fileExtension = fileName.split('.').pop()

                if ('json' === fileExtension) {
                    $('#wpfnl-export-import-warning').css('display', 'none')
                    $('.import-funnel-modal .import-label').addClass('file-added')
                    $('.import-funnel-modal .import-label > h4').text('Json file ready for import.')
                    $('.import-funnel-modal .import-label > p').text(fileName)
                } else {
                    $('.import-funnel-modal .import-label').removeClass('file-added')
                    $('#wpfnl-export-import-warning').css('display', 'inline-block')
                    $('#wpfnl-export-import-warning').css('color', '#d63638 !important')

                    $('.import-funnel-modal .import-label > h4').html(
                        'Drag & Drop or <span class="primary-color">Choose file</span> to upload.',
                    )
                    $('.import-funnel-modal .import-label > p').text(
                        'Supported formats: JSON file.',
                    )
                }
            } else {
                $('.import-funnel-modal .import-label').removeClass('file-added')
                $('#wpfnl-export-import-warning').css('display', 'inline-block')
                $('#wpfnl-export-import-warning').css('color', '#d63638 !important')

                $('.import-funnel-modal .import-label > h4').html(
                    'Drag & Drop or <span class="primary-color">Choose file</span> to upload.',
                )
                $('.import-funnel-modal .import-label > p').text('Supported formats: JSON file.')
            }
        }

        /**
         * Download json file
         *
         * @param content
         * @param fileName
         * @param contentType
         *
         * @since 2.6.3
         */
        function download(content, fileName, contentType) {
            const a = document.createElement('a')
            const file = new Blob([content], { type: contentType })
            a.href = URL.createObjectURL(file)
            a.download = fileName
            a.click()
        }

        /**
         * Realtime funnel name change on header
         *
         * @param event
         * @since 1.0.0
         */
        var fnl_name = ''
        var fnl_name2 = ''
        var funnelNameWidth = ''
        $('.funnel-name-edit').on('click', function (e) {
            e.stopPropagation()
            fnl_name = $(this).parents('.steps-page__fnl-name').find('.funnel-name').text()
            $(this).hide()
            funnelNameWidth = $(this).parents('.steps-page__fnl-name').find('.funnel-name').width()

            $(this)
                .parents('.steps-page__fnl-name')
                .find('.funnel-name-input')
                .css('width', funnelNameWidth + 'px')
            $(this)
                .parents('.steps-page__fnl-name')
                .find('.funnel-name-input')
                .val(fnl_name)
                .show()
                .focus()
            $(this).parents('.steps-page__fnl-name').find('.funnel-name').hide()
        })

        $('.funnel-name-input').keyup(function () {
            $(this).parents('.steps-page__fnl-name').find('.funnel-name').text($(this).val())
            funnelNameWidth = $(this).parents('.steps-page__fnl-name').find('.funnel-name').width()

            let input = $(this)
            let valueLength = input.val().length
            input.css('width', funnelNameWidth + 'px')
        })

        $('body').on('click', function (e) {
            fnl_name2 = $('.steps-page__fnl-name .funnel-name-input').val()

            $('.steps-page__fnl-name .funnel-name-input').val(fnl_name2).hide()
            $('.steps-page__fnl-name .funnel-name').text(fnl_name2).show()
        })

        /**
         * Realtime automation name change on automation drawer
         *
         * @param event
         * @since 1.0.0
         */
        var automation_name = ''
        var automation_name2 = ''
        $(document).on('click', '.automation-name-edit', function (e) {
            e.preventDefault()

            automation_name = $(this)
                .parents('.automation-name-wrapper')
                .find('.automation-name-preview')
                .text()
            $(this).hide()
            $('.automation-name-cancel').css('display', 'flex')
            $(this)
                .parents('.automation-name-wrapper')
                .find('.automation-name-input')
                .val(automation_name)
                .css('display', 'flex')
            $(this).parents('.automation-name-wrapper').find('.automation-name-preview').hide()
            $(this)
                .parents('.automation-name-wrapper')
                .find('.automation-name-submit')
                .css('display', 'flex')
        })

        // $(document).on("click", ".automation-name-edit", function(e) {
        $('.automation-name-input').keyup(function () {
            $(this)
                .parents('.automation-name-wrapper')
                .find('.automation-name-preview')
                .text($(this).val())
        })

        $(document).on('click', '.automation-name-submit', function (e) {
            e.preventDefault()
            automation_name2 = $(this)
                .parents('.automation-name-wrapper')
                .find('.automation-name-input')
                .val()
            $(this).hide()
            $('.automation-name-cancel').hide()
            $(this)
                .parents('.automation-name-wrapper')
                .find('.automation-name-input')
                .val(automation_name2)
                .hide()
            $(this)
                .parents('.automation-name-wrapper')
                .find('.automation-name-preview')
                .text(automation_name2)
                .css('display', 'flex')
            $(this)
                .parents('.automation-name-wrapper')
                .find('.automation-name-edit')
                .css('display', 'flex')
        })

        $(document).on('click', '.automation-name-cancel', function (e) {
            e.preventDefault()
            $(this).hide()
            $('.automation-name-cancel').hide()
            $(this)
                .parents('.automation-name-wrapper')
                .find('.automation-name-input')
                .val(automation_name)
                .hide()
            $(this)
                .parents('.automation-name-wrapper')
                .find('.automation-name-preview')
                .text(automation_name)
                .css('display', 'flex')
            $(this).parents('.automation-name-wrapper').find('.automation-name-submit').hide()
            $(this)
                .parents('.automation-name-wrapper')
                .find('.automation-name-edit')
                .css('display', 'flex')
        })

        $(document).on('click', '#save_automation', function (e) {
            e.preventDefault()
            $('.event-trigger').each(function () {
                if ($(this).children('option:selected').val() == '') {
                    $(this).parents('.wpfnl-form-group').addClass('field-required')
                } else {
                    $(this).parents('.wpfnl-form-group').removeClass('field-required')
                }
            })
            $('.list-trigger').each(function () {
                if ($(this).children('option:selected').val() == '') {
                    $(this).parents('.wpfnl-form-group').addClass('field-required')
                } else {
                    $(this).parents('.wpfnl-form-group').removeClass('field-required')
                }
            })
            $('.tag-trigger').each(function () {
                if ($(this).children('option:selected').val() == '') {
                    $(this).parents('.wpfnl-form-group').addClass('field-required')
                } else {
                    $(this).parents('.wpfnl-form-group').removeClass('field-required')
                }
            })
        })

        //-----remove "field-required" class when data selected-------
        $(document).on('change', '.single-trigger .selectbox-wrapper select', function (e) {
            if ($(this).children('option:selected').val() != '') {
                $(this).parents('.wpfnl-form-group').removeClass('field-required')
            }
        })

        /**
         * change funnel name
         *
         * @param event
         * @since 1.0.0
         */
        FunnelHandler.prototype.changeFunnelName = function (event) {
            event.preventDefault()
            var payload = {
                    data: $(this).serialize(),
                },
                button = $(this).find('.funnel-name-submit')
            button.hide()
            button.parents('.steps-page__fnl-name').find('.funnel-name-input').hide()
            button.parents('.steps-page__fnl-name').find('.funnel-name').show()
            button.parents('.steps-page__fnl-name').find('.funnel-name-edit').show()
            wpAjaxHelperRequest('funnel-name-change', payload)
                .success(function (response) {
                    location.reload()
                })
                .error(function (response) {})
        }

        var StepHandler = function () {
            $(document.body)
                .on('click', '#wpfnl-delete-step', this.deleteStep)
                .on('click', '#wpfnl-update-checkout-product-tab', this.updateCheckoutProduct)
                .on('click', '#wpfnl-update-thank-you-settings', this.updateThankYouSettings)
                .on('click', '#wpfnl-update-upsell-settings', this.updateUpsellSettings)
                .on('click', '#wpfnl-update-downsell-settings', this.updateDownsellSettings)
                .on('change', '#wpfnl-choose-step-type', this.stepTypeChange)
            // .on('click', '#wpfnl-add-product', this.addProduct)
        }

        /**
         * add product
         * @param event
         */
        StepHandler.prototype.addProduct = function (event) {
            event.preventDefault()
            var _prObj = $('#wpfnl-checkout-products').select2('data')[0],
                step_id = $(this).attr('data-id')
            if (_prObj) {
                product_option.push({
                    id: _prObj.id,
                    quantity: 1,
                })
                var payload = {
                        id: _prObj.id,
                        step_id: step_id,
                        products: JSON.stringify(product_option),
                        index: parseInt($('.product__single-accordion').length),
                    },
                    that = $(this)

                wpAjaxHelperRequest('wpfnl-add-product', payload)
                    .success(function (response) {
                        if (response.success) {
                            $('.no-product-notice').hide()
                            $('.accordion-head').show()
                            // $('.product-accordion__wrapper').append(response.html);
                            $('.product-single-accordion__sortable-wrapper').append(response.html)
                            $('#wpfnl-checkout-products').val(null).trigger('change')
                            if (product_option.length) that.html('Add product')
                            // $('.product__single-accordion:nth-child(2)').addClass('active');
                        }
                        console.log('Woohoo!')
                        console.log(response)
                        $('#setp-list-' + step_id + ' span').css('color', 'black')

                        $('.product-single-accordion__sortable-wrapper').sortable({
                            axis: 'y',
                            start: function (event, ui) {},
                            update: function (event, ui) {
                                var product_order = $(
                                    '.product-single-accordion__sortable-wrapper',
                                ).sortable('toArray')

                                product_option = []
                                var funnel_id = getUrlParameter('id')

                                for (var i in product_order) {
                                    var dragger_id = product_order[i]
                                    var prod_id = dragger_id.replace(
                                        'product__single-accordion-',
                                        '',
                                    )
                                    var quantity = $('#product-quantity-' + prod_id).val()
                                    product_option.push({
                                        id: prod_id,
                                        quantity: quantity,
                                    })
                                }
                            },
                        })
                        $('.product-single-accordion__sortable-wrapper').disableSelection()
                    })
                    .error(function (response) {})
            }
        }

        /**
         * delete product from checkout
         *
         * @param event
         */
        StepHandler.prototype.deleteproduct = function (event) {
            event.preventDefault()
            var index = $(this).attr('data-index')
            product_option.splice(index, 1)
            var payload = {
                step_id: $(this).attr('data-id'),
                products: JSON.stringify(product_option),
            }
            wpAjaxHelperRequest('wpfnl-update-checkout-product-tab', payload)
                .success(function (response) {
                    if (response.success) {
                        location.reload()
                        return false
                    }
                    console.log('Woohoo!')
                    console.log(response)
                })
                .error(function (response) {})
        }

        /**
         * save function for checkout steps
         * product tab
         *
         * @param event
         * @since 1.0.0
         */
        StepHandler.prototype.updateCheckoutProduct = function (event) {
            event.preventDefault()
            var thisLoader = $(this).find('.wpfnl-loader')
            var thisAlert = $(this).siblings('.wpfnl-alert')

            //=== Enable additional checkout fields===//
            var coupon = 'no'
            if ($('#enable-checkout-coupon').prop('checked') == true) {
                coupon = 'yes'
            } else if ($('#enable-checkout-coupon').prop('checked') == false) {
                coupon = 'no'
            }

            //=== Enable additional checkout fields===//

            thisLoader.fadeIn()
            var payload = {
                step_id: $(this).attr('data-iddata-id'),
                coupon: coupon,
                products: JSON.stringify(product_option),
                // discount: $(".wpfnl-checkout-discount").val(),
            }
            wpAjaxHelperRequest('wpfnl-update-checkout-product-tab', payload)
                .success(function (response) {
                    thisLoader.fadeOut()
                    thisAlert.text('Saved Successfully').addClass('box wpfnl-success').fadeIn()

                    setTimeout(function () {
                        thisAlert.fadeOut().text('').removeClass('box wpfnl-success')
                    }, 2000)

                    console.log('Woohoo!')
                    console.log(response)
                })
                .error(function (response) {
                    console.log('Uh, oh!')
                    console.log(response.statusText)

                    thisLoader.fadeOut()
                    thisAlert.text('Erorr occurred').addClass('box wpfnl-error').fadeIn()
                    setTimeout(function () {
                        thisAlert.fadeOut().text('').removeClass('box wpfnl-error')
                    }, 2000)
                })
        }

        /**
         * Set minimum time for custom redirection in thankyou page
         *
         * @param event
         *
         */
        $(document).on('keyup', '#set-time', function () {
            if ($('#set-time').val() && $('#set-time').val() < 1) {
                $('#set-time').val(1)
            }
        })

        /**
         * save thankyou settings
         * options
         *
         * @param event
         * @since 1.0.0
         */
        StepHandler.prototype.updateThankYouSettings = function (event) {
            event.preventDefault()
            var thisLoader = $(this).find('.wpfnl-loader')
            var thisAlert = $(this).siblings('.wpfnl-alert')
            thisLoader.fadeIn()
            var payload = {
                step_id: $(this).attr('data-id'),
                text: $('.thankyou-page-text').val(),
                redirect_link: $('.thankyou-redirect-link').val(),
                order_overview: $('#enable-order-overview').is(':checked') ? 'on' : 'off',
                order_details: $('#enable-order-details').is(':checked') ? 'on' : 'off',
                billing_details: $('#enable-billing-details').is(':checked') ? 'on' : 'off',
                shipping_details: $('#enable-shipping-details').is(':checked') ? 'on' : 'off',
                is_custom_redirect: $('#enable-custom-redirect').is(':checked') ? 'on' : 'off',

                is_direct_redirect: $('#direct-custom-redirect').is(':checked') ? 'on' : 'off',
                set_time: $('#set-time').val(),
                custom_redirect_url: $('#custom-redirect-url').val(),
            }

            wpAjaxHelperRequest('update-thankyou-settings', payload)
                .success(function (response) {
                    thisLoader.fadeOut()
                    $('#wpfnl-toaster-wrapper').addClass('quick-toastify-successful-notification')

                    $('#wpfnl-toaster-icon').html(
                        '<svg width="26" height="26" fill="none" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg"><path fill="#4BAE4F" fill-rule="evenodd" d="M13 0C5.83 0 0 5.83 0 13s5.83 13 13 13 13-5.83 13-13S20.17 0 13 0z" clip-rule="evenodd"/><path fill="#fff" fill-rule="evenodd" d="M19.287 8.618a.815.815 0 010 1.148l-7.617 7.617a.812.812 0 01-1.148 0l-3.808-3.809a.815.815 0 010-1.147.815.815 0 011.147 0l3.235 3.234 7.044-7.043a.806.806 0 011.147 0z" clip-rule="evenodd"/></svg>',
                    )

                    $('#wpfnl-toaster-message').html('Saved Successfully')
                    $('#wpfnl-toaster-wrapper').show()
                    setTimeout(function () {
                        $('#wpfnl-toaster-wrapper').removeClass(
                            'quick-toastify-successful-notification',
                        )
                        $('#wpfnl-toaster-wrapper').hide()
                    }, 3000)
                })
                .error(function (response) {
                    console.log('Uh, oh!')
                    console.log(response.statusText)
                    thisLoader.fadeOut()
                    $('#wpfnl-toaster-wrapper').addClass('quick-toastify-warn-notification')

                    $('#wpfnl-toaster-icon').html(
                        '<svg width="26" height="26" fill="none" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg"><path fill="#EC5956" fill-rule="evenodd" d="M26 13c0 7.18-5.82 13-13 13S0 20.18 0 13 5.82 0 13 0s13 5.82 13 13zm-11.375 6.5a1.625 1.625 0 11-3.25 0 1.625 1.625 0 013.25 0zM13 4.875c-.898 0-1.625.728-1.625 1.625V13a1.625 1.625 0 103.25 0V6.5c0-.897-.727-1.625-1.625-1.625z" clip-rule="evenodd"/></svg>',
                    )

                    $('#wpfnl-toaster-message').html('Erorr occurred')
                    $('#wpfnl-toaster-wrapper').show()
                    setTimeout(function () {
                        $('#wpfnl-toaster-wrapper').removeClass('quick-toastify-warn-notification')
                        $('#wpfnl-toaster-wrapper').hide()
                    }, 3000)
                })
        }

        /**
         * save upsell settings
         * options
         *
         * @param event
         * @since 1.0.0
         */
        StepHandler.prototype.updateUpsellSettings = function (event) {
            event.preventDefault()
            var thisLoader = $(this).find('.wpfnl-loader')
            var thisAlert = $(this).siblings('.wpfnl-alert')
            thisLoader.fadeIn()
            var payload = {
                step_id: $(this).attr('data-id'),
                product: $('.wpfnl-product-search').val(),
                quantity: $('#upsell-product-quantity').val(),
                product_price: $('#upsell-original-price').val(),
                product_sale_price: $('#upsell-sale-price').val(),
                discount_type: $('#upsell-discount-type').val(),
                discount_value: $('#upsell-discount-value').val(),
                hide_image: $('#img-hide-mobile').prop('checked') ? 'on' : 'off',
                next_step_yes: $('#next-step-yes').val(),
                next_step_no: $('#next-step-no').val(),
            }
            wpAjaxHelperRequest('update-upsell-settings', payload)
                .success(function (response) {
                    thisLoader.fadeOut()
                    thisAlert.text('Saved Successfully').addClass('box wpfnl-success').fadeIn()
                    setTimeout(function () {
                        thisAlert.fadeOut().text('').removeClass('box wpfnl-success')
                    }, 2000)
                    console.log('Woohoo!')
                    console.log(response)
                })
                .error(function (response) {
                    console.log('Uh, oh!')
                    console.log(response.statusText)
                    thisLoader.fadeOut()
                    thisAlert.text('Erorr occurred').addClass('box wpfnl-error').fadeIn()
                    setTimeout(function () {
                        thisAlert.fadeOut().text('').removeClass('box wpfnl-error')
                    }, 2000)
                })
        }

        /**
         * save downsell settings
         * options
         *
         * @param event
         * @since 1.0.0
         */
        StepHandler.prototype.updateDownsellSettings = function (event) {
            event.preventDefault()
            var thisLoader = $(this).find('.wpfnl-loader')
            var thisAlert = $(this).siblings('.wpfnl-alert')
            thisLoader.fadeIn()
            var payload = {
                step_id: $(this).attr('data-id'),
                product: $('.wpfnl-product-search').val(),
                quantity: $('#downsell-product-quantity').val(),
                product_price: $('#downsell-original-price').val(),
                product_sale_price: $('#downsell-sale-price').val(),
                discount_type: $('#downsell-discount-type').val(),
                discount_value: $('#downsell-discount-value').val(),
                hide_image: $('#img-hide-mobile').prop('checked') ? 'on' : 'off',
                next_step_yes: $('#next-step-yes').val(),
                next_step_no: $('#next-step-no').val(),
            }
            wpAjaxHelperRequest('update-downsell-settings', payload)
                .success(function (response) {
                    thisLoader.fadeOut()
                    thisAlert.text('Saved Successfully').addClass('box wpfnl-success').fadeIn()
                    setTimeout(function () {
                        thisAlert.fadeOut().text('').removeClass('box wpfnl-success')
                    }, 2000)
                    console.log('Woohoo!')
                    console.log(response)
                })
                .error(function (response) {
                    console.log('Uh, oh!')
                    console.log(response.statusText)
                    thisLoader.fadeOut()
                    thisAlert.text('Erorr occurred').addClass('box wpfnl-error').fadeIn()
                    setTimeout(function () {
                        thisAlert.fadeOut().text('').removeClass('box wpfnl-error')
                    }, 2000)
                })
        }

        /**
         * Ajax handler for step
         * deletion action
         *
         * @param event
         */
        StepHandler.prototype.deleteStep = function (event) {
            event.preventDefault()
            var step_id = $(this).attr('data-id')
            var payload = {
                step_id: step_id,
            }
            if (confirm('Are you sure?')) {
                wpAjaxHelperRequest('delete-step', payload)
                    .success(function (response) {
                        console.log('Woohoo!')
                        console.log(response)
                        window.location.href = response.redirectUrl
                    })
                    .error(function (response) {
                        console.log('Uh, oh!')
                        console.log(response.statusText)
                    })
            }
        }

        /**
         * Ajax handler for step
         * creation
         *
         * @param e
         * @since 1.0.0
         */
        StepHandler.prototype.createStep = function (e) {
            e.preventDefault()
            var thisLoader = $(this).find('.wpfnl-loader')
            var thisAlert = $(this).siblings('.wpfnl-alert')

            thisLoader.fadeIn()

            var wrapper = $('#create-step-form'),
                type = $(this).attr('data-step-type'),
                funnelId = $(this).attr('data-funnel-id'),
                stepListWrapper = $('#wpfnl-funnel-step-lists')

            var payload = {
                funnel_id: funnelId,
                step_type: type,
            }
            wpAjaxHelperRequest('create-step', payload)
                .success(function (response) {
                    console.log('success')
                    thisLoader.fadeOut()
                    thisAlert
                        .text('Successfully Step Created')
                        .addClass('box wpfnl-success')
                        .fadeIn()

                    setTimeout(function () {
                        thisAlert.fadeOut().text('').removeClass('box wpfnl-success')
                    }, 2000)

                    if (response.success) {
                        window.location.href = response.redirectUrl
                    } else {
                        console.log(response)
                    }
                })
                .error(function (response) {
                    thisLoader.fadeOut()
                    thisAlert.text('Erorr Occurred').addClass('box wpfnl-error').fadeIn()

                    setTimeout(function () {
                        thisAlert.fadeOut().text('').removeClass('box wpfnl-error')
                    }, 2000)
                })
        }

        StepHandler.prototype.stepTypeChange = function (event) {
            event.preventDefault()
            var wrapper = $('.choose-step-type'),
                step_type = $(this).find(':selected').val(),
                saveBtn = $('#wpfnl-create-step')
            if (
                WPFunnelVars.is_wc_installed === 'no' &&
                (step_type === 'checkout' || step_type === 'upsell' || step_type === 'downsell')
            ) {
                $('.wpfnl-modal__body').find('p').remove()
                wrapper.after(
                    "<p style='text-align: center; color: red'>You need install and active WooCommerce to use this step type. </p>",
                )
                saveBtn.addClass('disabled').css('pointer-events', 'none')
                return
            } else {
                $('.wpfnl-modal__body').find('p').remove()
                saveBtn.removeClass('disabled').css('pointer-events', 'inherit')
            }
        }

        var DataSerachHandler = function () {
            this.initProductSearch()
            this.initCouponSearch()
        }

        /**
         * initialize product search
         * for checkout
         *
         * @param event
         * @since 1.0.0
         */
        DataSerachHandler.prototype.initProductSearch = function (event) {
            var select2Args = {
                minimumInputLength: 3,
                allowClear: true,
                maximumSelectionLength: 1,
                ajax: {
                    url: WPFunnelVars.ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            term: params.term,
                            action: 'wpfnl_product_search',
                            security: WPFunnelVars.security,
                        }
                    },
                    processResults: function (data) {
                        var terms = []
                        if (data) {
                            $.each(data, function (id, value) {
                                terms.push({
                                    id: id,
                                    text: value.name,
                                    price: value.price,
                                    sale_price: value.sale_price,
                                })
                            })
                        }
                        return {
                            results: terms,
                        }
                    },
                    cache: true,
                },
            }

            $('.wpfnl-product-search').on('select2:select', function (e) {
                var data = e.params.data
                $('#upsell-original-price').val(data.price)
                $('#upsell-sale-price').val(data.sale_price)

                $('#downsell-original-price').val(data.price)
                $('#downsell-sale-price').val(data.sale_price)
            })
            $('.wpfnl-product-search').select2(select2Args)
        }

        /**
         * initialize coupon search
         * for checkout
         *
         * @param event
         * @since 1.0.0
         */
        DataSerachHandler.prototype.initCouponSearch = function (event) {
            var select2Args = {
                minimumInputLength: 3,
                allowClear: true,
                ajax: {
                    url: WPFunnelVars.ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            term: params.term,
                            action: 'wpfnl_coupon_search',
                            security: WPFunnelVars.security,
                        }
                    },
                    processResults: function (data) {
                        var terms = []
                        if (data) {
                            $.each(data, function (id, text) {
                                terms.push({
                                    id: id,
                                    text: text,
                                })
                            })
                        }
                        return {
                            results: terms,
                        }
                    },
                    cache: true,
                },
            }
            $('.wpfnl-discount-search').select2(select2Args)
        }

        var AdminNotices = function () {
            $(document.body)
                .on('click', '#wpfnl-install-plugin', this.installPlugin)
                .on('wp-plugin-install-success', this.installSuccess)
            $(document).on('wp-plugin-install-success', self._installSuccess)
        }
        AdminNotices.prototype.installPlugin = function (e) {
            e.preventDefault()
            let slug = $(this).attr('data-slug')
            wp.updates.queue.push({
                action: 'install-plugin', // Required action.
                data: {
                    slug: slug,
                },
            })
            wp.updates.queueChecker()
        }
        AdminNotices.prototype.installSuccess = function (event, response) {
            event.preventDefault()
            console.log(response)
        }

        new FunnelHandler()
        new StepHandler()
        new DataSerachHandler()
        new AdminNotices()

        /**
         * funnel list show more action on click
         *
         * @since 1.0.0
         */
        $('body').on('click', function () {
            $('.funnel-list__more-action').removeClass('show-actions')
        })

        $('.funnel-list__more-action').on('click', function (e) {
            e.stopPropagation()
            $(this).toggleClass('show-actions')
            $(this)
                .parents('.funnel__single-list')
                .siblings()
                .find('.funnel-list__more-action')
                .removeClass('show-actions')
        })

        $('.funnel-list__more-action .wpfnl-dropdown').on('click', function (e) {
            e.stopPropagation()
        })

        /**
         * copy to clipboard
         *
         * @since 1.0.0
         */
        $('.wpfnl-copy-clipboard').on('click', function () {
            var getID = $(this).attr('data-id')
            var getText = $('#' + getID).val()
            CopyToClipboard(getText)

            $(this).siblings('.copied-msg').text('Copied').fadeIn(500)

            setTimeout(function () {
                $('.copied-msg').fadeOut('slow')
            }, 2500)
        })

        /**
         * copy to clipboard helper function
         *
         * @since 1.0.0
         */
        function CopyToClipboard(text) {
            if (window.clipboardData && window.clipboardData.setData) {
                return clipboardData.setData('Text', text)
            } else if (document.queryCommandSupported && document.queryCommandSupported('copy')) {
                var textarea = document.createElement('textarea')
                textarea.textContent = text
                textarea.style.position = 'fixed'
                document.body.appendChild(textarea)
                textarea.select()

                try {
                    return document.execCommand('copy')
                } catch (ex) {
                    console.warn('Copy to clipboard failed.', ex)
                    return false
                } finally {
                    document.body.removeChild(textarea)
                }
            }
        }

        /**
         * open modal when add new step
         *
         * @param event
         * @since 1.0.0
         */
        $('.wpfnl-modal-close').on('click', function (e) {
            e.preventDefault()
            $(this).parents('.wpfnl-modal').fadeOut(200)
        })

        $('.wpfnl-modal__wrapper').on('click', function (e) {
            e.stopPropagation()
        })

        /**
         * step name change on step page
         *
         * @param event
         * @since 1.0.0
         */
        var stepName = ''
        $('.step-name-edit').on('click', function (e) {
            e.preventDefault()
            $(this).hide()
            stepName = $(this).parents('.title-area').find('.step-name-input').val()
            $(this).parents('.title-area').find('.step-name-input').show()
            $(this).parents('.title-area').find('.step-name-noupdate').show()
            $(this).parents('.title-area').find('.step-name').hide()
            $(this).parents('.title-area').find('.step-name-update').show()
        })

        $('.step-name-update').on('click', function (e) {
            e.preventDefault()
            $(this).hide()
            $(this).parents('.title-area').find('.step-name-input').hide()
            $(this).parents('.title-area').find('.step-name-noupdate').hide()
            $(this).parents('.title-area').find('.step-name').show()
            $(this).parents('.title-area').find('.step-name-edit').show()
        })

        $('.step-name-noupdate').on('click', function (e) {
            e.preventDefault()
            $(this).hide()
            $(this).parents('.title-area').find('.step-name-input').val(stepName).hide()
            $(this).parents('.title-area').find('.step-name-update').hide()
            $(this).parents('.title-area').find('.step-name').text(stepName).show()
            $(this).parents('.title-area').find('.step-name-edit').show()
        })

        $('.step-name-input').keyup(function () {
            $(this).parents('.title-area').find('.step-name').text($(this).val())
        })

        /**
         * step settings tab
         *
         * @since 1.0.0
         */
        $('.step-settings__single-tab-content:first-child').show()

        $(document).on('click', '.steps-settings__tab-nav:not(.ab-nav) a', function (e) {
            if (!$(this).parent('li').hasClass('disabled')) {
                e.preventDefault()
                var dataID = $(this).attr('href')

                $(this).parent('li').addClass('active').siblings().removeClass('active')
                $(this).parents('.steps-settings').find(dataID).show()
                $(this).parents('.steps-settings').find(dataID).siblings().hide()
            }
        })

        /**
         * step settings product options tab
         *
         * @since 1.0.0
         */
        $('.product-options__tab-nav a').on('click', function (e) {
            e.preventDefault()

            var dataID = $(this).attr('href')
            $(this).parent('li').addClass('active').siblings().removeClass('active')

            $(this).parents('.wpfnl-product-options').find(dataID).show()
            $(this).parents('.wpfnl-product-options').find(dataID).siblings().hide()
        })

        /**
         * wpfnl accordion
         *
         * @since 1.0.0
         */
        $('.wpfnl__accordion-content').hide()
        $('.wpfnl__single-accordion:first-child .wpfnl__accordion-content').show()
        $('.wpfnl__accordion-title').on('click', function (e) {
            e.preventDefault()

            var dataID = $(this).attr('href')

            $(this).parent('.wpfnl__single-accordion').find(dataID).slideToggle()
            $(this)
                .parents('.wpfnl__single-accordion')
                .siblings()
                .find('.wpfnl__accordion-content')
                .slideUp()
        })

        /**
         * steps-header-hamburger toggle menu
         *
         * @since 1.0.0
         */
        $('body').on('click', function () {
            $('#steps-header-hamburger').removeClass('show')
        })
        // $(document).on("click", "#steps-header-hamburger", function(e) {
        $('#steps-header-hamburger').on('click', function (e) {
            e.stopPropagation()
            if (!$(this).hasClass('show')) {
                $(this).removeClass('show')
            } else {
                $(this).addClass('show')
            }

            wpfnlDatePicker()
        })

        /**
         * wpfnl step title edit
         *
         * @param event
         * @since 1.0.0
         */
        function step_edit(e) {
            e.preventDefault()
            var step_id = $(this).attr('data-id')
            var input = $('#step-name-input-' + step_id).val()
            var payload = {
                step_id: step_id,
                input: input,
            }
            wpAjaxHelperRequest('step-edit', payload)
                .success(function (response) {
                    $('#setp-list-' + step_id + ' .title-txt').text(input)

                    if (response.success) {
                        console.log('success')
                    } else {
                        console.log(response)
                    }
                })
                .error(function (response) {
                    console.log('error')
                })
        }
        $('.step-name-update').on('click', step_edit)
    })

    function getUrlParameter(sParam) {
        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=')

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined
                    ? true
                    : decodeURIComponent(sParameterName[1])
            }
        }
    }

    /**
     * settings page tab nav
     *
     * @since 1.0.0
     */
    $('.wpfn-settings__nav .nav-li:not(.disabled)').on('click', function (e) {
        var dataID = $(this).attr('data-id')
        $(this).addClass('active')
        $(this).siblings('.nav-li').removeClass('active')

        $('#' + dataID).show()
        $('#' + dataID)
            .siblings('.wpfnl-funnel__single-settings')
            .hide()
    })

    /**
     * Checkout Edit Field delete alert modal
     *
     * @since 1.0.0
     */
    $(document).on('click', '.delete-checkout-field', function () {
        $(this)
            .parents('.checkout__single-field')
            .find('.wpfnl-delete-alert-wrapper')
            .css('display', 'flex')
    })

    $(document).on('click', '.wpfnl-delete-confirm-btn .cancel', function () {
        $(this).parents('.wpfnl-delete-alert-wrapper').hide()
    })

    /**
     * edit-field settings tab
     *
     * @since 1.0.0
     */
    $('.edit-field-settings__single-tab-content:first-child').show()

    $(document).on('click', '.edit-field-settings__tab-nav a', function (e) {
        e.preventDefault()
        var dataID = $(this).attr('href')
        $(this).parent('li').addClass('active').siblings().removeClass('active')
        $(this).parents('.checkout-edit-field-tab__content-wrapper').find(dataID).show()
        $(this).parents('.checkout-edit-field-tab__content-wrapper').find(dataID).siblings().hide()
    })

    /**
     * add new checkout field drawer
     *
     * @since 1.0.0
     */
    $('.add-new-checkout-field-btn').on('click', function (e) {
        e.preventDefault()
        $(this)
            .parents('.checkout-edit-field-tab__content-wrapper')
            .find('.add-checkout-field-wrapper')
            .addClass('show-drawer')
    })

    $('.add-checkout-field-close').on('click', function (e) {
        e.preventDefault()
        $(this).parents('.add-checkout-field-wrapper').removeClass('show-drawer')
    })

    /**
     * edit checkout field drawer
     *
     * @since 1.0.0
     */
    // $('button.edit-field').on('click', function(e) {
    $(document).on('click', 'button.edit-field', function (e) {
        e.preventDefault()
        $(this)
            .parents('.checkout-edit-field-tab__content-wrapper')
            .find('.edit-checkout-field-wrapper')
            .addClass('show-drawer')
    })

    $('.add-checkout-field-close').on('click', function (e) {
        e.preventDefault()
        $(this).parents('.edit-checkout-field-wrapper').removeClass('show-drawer')
    })

    /**
     * show edit field type options
     *
     * @since 1.0.0
     */
    $('.wpfnl-edit-field-type').on('change', function (e) {
        e.preventDefault()
        var thisVal = $(this).val()

        if (thisVal == 'select') {
            $(this).parents('.field-body').find('.field-type-options').show()
        } else {
            $(this).parents('.field-body').find('.field-type-options').hide()
        }
    })

    jQuery(document).ready(function () {
        var selectedType = $('.wpfnl-edit-field-type').val()

        if (selectedType == 'select') {
            $('.wpfnl-edit-field-type').parents('.field-body').find('.field-type-options').show()
        } else {
            $('.wpfnl-edit-field-type').parents('.field-body').find('.field-type-options').hide()
        }
    })

    /**
     * Step preview permalink copy to clipboard
     *
     * @since 2.2.5
     */
    $(document).on('click', '.steps-url-slug .copy-slug-url', function (e) {
        e.preventDefault()
        $(this).addClass('copy-confirmed')
        var $temp = $('<input>')
        $(this).parents('.field-group').append($temp)
        $temp.val($(this).parents('.field-group').find('#step-preview-link').val()).select()
        document.execCommand('copy')
        $temp.remove()

        setTimeout(function () {
            $('.steps-url-slug .copy-slug-url').removeClass('copy-confirmed')
        }, 1000)
    })

    $(document).on('keyup change input', '.wpfnl-set-quantity-limit', function (e) {
        // Get the input value
        var inputValue = $(this).val()

        // If input is blank, default it to 1
        if (isNaN(inputValue)) {
            inputValue = '1'
        }
        // Remove any non-numeric characters except '.'
        inputValue = inputValue.replace(/[^0-9.]/g, '')

        // Split the value into integer and decimal parts
        var parts = inputValue.split('.')

        // If there are more than one decimal point, consider only the first part
        if (parts.length > 2) {
            inputValue = parts[0] + '.' + parts.slice(1).join('')
        }

        // Parse the value into an integer
        var quantity = parseInt(inputValue)
        // Set the input value to the parsed integer
        $(this).val(quantity)

        if (quantity < 1) {
            $(this).val(1)
        }
    })

    $(document).on('blur', '.wpfnl-set-quantity-limit', function (e) {
        var inputValue = $(this).val()
        if (inputValue.trim() === '') {
            $(this).val('1')
        }
    })

    /**
     * AB testing permalink copy to clipboard
     *
     * @since 3.1.0
     */
    $(document).on('click', '.copy-ab-url', function (e) {
        e.preventDefault()
        $(this).addClass('copy-confirmed')
        var $temp = $('<input>')
        $(this).parents('.header-right').append($temp)
        $temp.val($(this).parents('.header-right').find('#ab-testing-link').val()).select()
        document.execCommand('copy')
        $temp.remove()

        setTimeout(function () {
            $('.copy-ab-url').removeClass('copy-confirmed')
        }, 1000)
    })

    /**
     * rollback feature for WPF
     */
    $('select#wpfnl-rollback')
        .on('change', function () {
            var $this = $(this),
                $rollbackButton = $this.next('.wpfnl-rollback-button'),
                placeholderText = $rollbackButton.data('placeholder-text'),
                placeholderUrl = $rollbackButton.data('placeholder-url')
            $rollbackButton.html(placeholderText.replace('{VERSION}', $this.val()))
            $rollbackButton.attr('href', placeholderUrl.replace('VERSION', $this.val()))
        })
        .trigger('change')

    $('.wpfnl-rollback-button').on('click', function (event) {
        event.preventDefault()
        var $this = $(this)
        if (confirm('Are you sure?')) {
            $this.addClass('show-loader')
            $this.addClass('loading')
            location.href = $this.attr('href')
        }
    })

    // ---delete promotional-banner notice permanently ------
    $(document).on('click', '.wp-anniversary-banner .close-promotional-banner', function (event) {
        event.preventDefault()
        $('.wp-anniversary-banner').attr('style', 'display: none !important');
        wpAjaxHelperRequest('delete_promotional_banner')
    })

    // ---delete new UI notice permanently ------
    $(document).on('click', '.wpfunnels-newui-notice .close-newui-notice', function (event) {
        event.preventDefault()
        $('.wpfunnels-newui-notice.notice').css('display', 'none')
        wpAjaxHelperRequest('delete_new_ui_notice')
    })

    /**
     * Multiple Orderbump Accordion
     *
     */
    $(document).on(
        'click',
        '.single-order-bump .order-bump-accordion-header .title-area',
        function (e) {
            e.preventDefault()
            $(this).parents('.single-order-bump').siblings().removeClass('expanded-orderbump')
            $(this).parents('.single-order-bump').toggleClass('expanded-orderbump')

            $(this)
                .parents('.single-order-bump')
                .siblings()
                .find('.order-bump-accordion-content')
                .removeClass('show-content')
            $(this)
                .parents('.single-order-bump')
                .find('.order-bump-accordion-content')
                .toggleClass('show-content')
        },
    )

    $(document).ready(function () {
        const $stickyElement = $('.wpfnl-pvf-table-header');
        if($stickyElement.length === 0) return;
        const stickyOffset = $stickyElement.offset().top;
    
        $(window).on('scroll', function () {
            const scrollTop = $(this).scrollTop();
            if (scrollTop >= stickyOffset - 44) {
                $stickyElement.addClass('is-sticky');
            } else {
                $stickyElement.removeClass('is-sticky');
            }
        });
    });
    

    //------expand last orderbump when added a new orderbump----
    $(document).on('click', '.order-bump-header .add-new-orderbump', function (e) {
        e.preventDefault()
        $('.single-order-bump').siblings().removeClass('expanded-orderbump')
        $('.single-order-bump:last-child').addClass('expanded-orderbump')

        $('.single-order-bump')
            .siblings()
            .find('.order-bump-accordion-content')
            .removeClass('show-content')
        $('.single-order-bump:last-child')
            .find('.order-bump-accordion-content')
            .addClass('show-content')
    })

    /**
     * Automatic winner choose condition dropdown
     *
     */
    $(document).on('click', '.automatic-winner .set-condition .selected-item', function (e) {
        e.preventDefault()
        e.stopPropagation()
        $(this).parents('.wpfnl-selectbox').find('.selectable-option').slideToggle(300)
    })
    $(document).on('click', 'body', function () {
        $('.automatic-winner .set-condition .selectable-option').hide()
    })

    $(document).on('click', '.automatic-winner .set-condition .selectable-option li', function (e) {
        e.preventDefault()
        var selected_item_text = $(this).text()
        var selected_item_data = $(this).attr('data-value')

        $(this).siblings().removeClass('active')
        $(this).addClass('active')
        $('.automatic-winner .set-condition .selected-item')
            .text(selected_item_text)
            .attr('data-value', selected_item_data)
    })

    /**
     * Show step control button when clik on step (on drawflow window page)
     *
     */
    $(document).on('click', '.drawflow .drawflow-node .single-node', function (e) {
        e.stopPropagation()
        $(this)
            .parents('.parent-node')
            .siblings()
            .find('.single-node')
            .removeClass('im-selected-node')
        $(this).siblings().removeClass('im-selected-node')
        $(this)
            .parent('.single-node-wrapper')
            .siblings()
            .find('.single-node')
            .removeClass('im-selected-node')
        $(this).addClass('im-selected-node')
    })

    $(document).on('click', 'body', function () {
        $('.drawflow .drawflow-node .single-node').removeClass('im-selected-node')
    })

    /**
     * Add list, add tag dropdown show/hide
     *
     */
    $(document).on('click', '.wpfnl-custom-selectbox .selectbox-selected-value', function (e) {
        e.stopPropagation()
        $(this).parents('.wpfnl-custom-selectbox').toggleClass('show-options')
    })

    $(document).on('click', '.wpfnl-custom-selectbox .selectbox-options', function (e) {
        e.stopPropagation()
    })

    $(document).on('click', 'body', function () {
        $('.wpfnl-custom-selectbox').removeClass('show-options')
    })

    /**
     * Funnel Bulk select action dropdown
     *
     */
    $(document).on('click', '.bulk-delete-toggler', function (e) {
        e.stopPropagation()
        $(this).toggleClass('show-dropdown')
    })

    $(document).on('click', '.wpfnl-dropdown', function (e) {
        e.stopPropagation()
    })

    $(document).on('click', 'body', function () {
        $('.bulk-delete-toggler').removeClass('show-dropdown')
    })

    $(document).on('change', 'select#wpfnl_listing_page_offset', updateFunnelListingPageOffset)
    function updateFunnelListingPageOffset() {
        const offset = $(this).find('option:selected').val()
        const queryParams = new URLSearchParams(window.location.search)

        // Set new or modify existing parameter value.
        queryParams.set('pageno', '1')
        queryParams.set('per_page', offset)
        // Replace current querystring with the new one.
        history.replaceState(null, null, '?' + queryParams.toString())
        location.reload()
    }

    function selectedFunnelIds(params) {
        let ids = []
        if (params.length > 0) {
            for (let key = 0; key < params.length; key++) {
                const inputItem = params[key]?.id
                const id = inputItem.match(/\d+/)[0]
                ids.push(parseInt(id))
            }
        }
        selectedIds = ids
    }

    /**
     * Funnel Bulk select checkbox on change event
     *
     */
    function selectedFunnelCounter() {
        let totalSelectedFunnel = $('input[name=funnel-list-select]:checked').length

        selectedFunnelIds($('input[name=funnel-list-select]:checked'))

        if (totalSelectedFunnel > 0) {
            $('.funnel__single-list.list-header .bulk-action-wrapper').css('display', 'flex')
        } else {
            $('.funnel__single-list.list-header .bulk-action-wrapper').css('display', 'none')
        }

        if (totalSelectedFunnel > 1) {
            $('.funnel__single-list.list-header .bulk-action-wrapper .selected-funnel-count').text(
                totalSelectedFunnel + ' Funnels',
            )
        } else {
            $('.funnel__single-list.list-header .bulk-action-wrapper .selected-funnel-count').text(
                totalSelectedFunnel + ' Funnel',
            )
        }
    }

    $('.funnel__single-list.list-body input[name=funnel-list-select]').on('change', function () {
        $('.funnel-list__bulk-select.select-all-funnels input[name=funnel-list__bulk-select]').prop(
            'checked',
            false,
        )
        selectedFunnelCounter()
    })

    $('.funnel-list__bulk-select.select-all-funnels input[name=funnel-list__bulk-select]').on(
        'change',
        function () {
            if ($(this).is(':checked')) {
                $('.funnel__single-list.list-body input[name=funnel-list-select]').prop(
                    'checked',
                    true,
                )
                selectedFunnelCounter()
            } else {
                $('.funnel__single-list.list-header .bulk-action-wrapper').css('display', 'none')
                $('.funnel__single-list.list-body input[name=funnel-list-select]').prop(
                    'checked',
                    false,
                )
            }
        },
    )

    /**
     * Funnel window page and Empty funnel page help resource show/hide.
     *
     */
    $(document).on('click', '.wpfnl-helper-btn', function (e) {
        e.stopPropagation()
        $(this).parent('.wpfnl-canvas-helper').toggleClass('show-helpers')
    })

    $(document).on('click', 'body', function () {
        $('.wpfnl-canvas-helper').removeClass('show-helpers')
    })

    /**
     * add new step option dropdown show/hide.
     *
     */
    $(document).on('click', '.addstep .add-step-wrapper .icon', function (e) {
        e.stopPropagation()
        $(this).parent('.add-step-wrapper').toggleClass('show-options')
    })

    $(document).on('click', '.option-duplicate', function (e) {
        e.stopPropagation()
    })

    $(document).on('click', 'body', function () {
        $('.addstep .add-step-wrapper').removeClass('show-options')
    })

    /**
     * Showmore option hide/show
     * Three dot more option hide/show.
     *
     */
    $(document).on('click', '.on-showmore-options', function (e) {
        e.stopPropagation()
        $(this).toggleClass('show-dropdown')
    })
    $(document).on('click', 'body', function () {
        $('.on-showmore-options').removeClass('show-dropdown')
    })

    /*----mailmint actions dropdown------ */
    $(document).on('click', '.mailmint-automation .add-action > button', function (e) {
        e.stopPropagation()

        $(this).parents('.logical-decision-box').siblings('.add-action').removeClass('show-actions')
        $(this).parents('.single-decision').find('.add-action').removeClass('show-actions')
        $(this)
            .parents('.single-decision')
            .siblings()
            .find('.add-action')
            .removeClass('show-actions')

        $(this)
            .parents('.automation-single-step')
            .siblings()
            .find('.add-action')
            .removeClass('show-actions')

        $(this).parents('.add-action').toggleClass('show-actions')
    })

    $(document).on('click', '.mailmint-automation .mailmint-actions', function (e) {
        e.stopPropagation()
    })

    $(document).on('click', 'body, .mailmint-single-action, .automation-start-point', function (e) {
        $('.mailmint-automation .add-action').removeClass('show-actions')
    })

    //----when click first-action plus (+) button------
    $(document).on('click', '.mailmint-automation .add-action.first-action > button', function (e) {
        $('.automation-single-step .add-action').removeClass('show-actions')
    })

    //----when click automation-single-step's plus (+) button------
    $(document).on('click', '.automation-single-step .add-action > button', function (e) {
        $('.selected-trigger .add-action').removeClass('show-actions')
        //$('.logical-decision-box .add-action').removeClass('show-actions');
    })

    //--------select box placeholder color and active color--------
    $(document).on('change', 'select.has-placeholder-color', function () {
        var thisVal = $(this).find(':selected').val()
        if ('' != thisVal) {
            $(this).addClass('value-selected')
        } else {
            $(this).removeClass('value-selected')
        }
    })

    $(document).on('click', '.add-new-webhook', function () {
        $('select.has-placeholder-color').removeClass('value-selected')
    })

    /*----A|B testing modal header's more option's dropdown show/hide------ */
    $(document).on('click', '.ab-testing-drawer .header-right .more-options', function (e) {
        e.stopPropagation()
        $(this).toggleClass('show-options')
    })

    $(document).on('click', 'body', function () {
        $('.ab-testing-drawer .header-right .more-options').removeClass('show-options')
    })

    /*----A|B testing variations steps more option's dropdown show/hide------ */
    $(document).on('click', '.single-ab-step .more-option > button', function (e) {
        e.stopPropagation()
        $(this)
            .parents('.single-ab-step')
            .siblings()
            .find('.more-option')
            .removeClass('show-dorpdown')
        $(this).parents('li.more-option').toggleClass('show-dorpdown')
    })

    $(document).on('click', '.single-ab-step .more-option .options-dropdown', function (e) {
        e.stopPropagation()
    })

    $(document).on('click', 'body', function () {
        $('.ab-testing-drawer .single-ab-step li.more-option').removeClass('show-dorpdown')
    })

    /**
     * wpfnl custom selectbox dropdown option hide/show
     *
     */
    $(document).on('click', '.wpfnl-selectbox .selected-option', function (e) {
        e.stopPropagation()

        $(this)
            .parents('.single-rules')
            .siblings()
            .find('.wpfnl-selectbox')
            .removeClass('show-options')
        $(this).parents('.rules').siblings().find('.wpfnl-selectbox').removeClass('show-options')

        $(this)
            .parents('.condition-single-row')
            .siblings()
            .find('.wpfnl-selectbox')
            .removeClass('show-options')
        $(this)
            .parents('.single-condition')
            .siblings()
            .find('.wpfnl-selectbox')
            .removeClass('show-options')

        $(this).parent('.wpfnl-selectbox').toggleClass('show-options')
    })
    $(document).on('click', 'body', function () {
        $('.wpfnl-selectbox').removeClass('show-options')
    })

    /**
     * wpfnl custom selectbox sub dropdown option hide/show
     *
     */
    $(document).on('click', '.single-rules .selectable-options > li', function (e) {
        e.stopPropagation()

        $(this)
            .parents('.single-rules')
            .siblings()
            .find('.selectable-options > li')
            .removeClass('show-options')
        $(this)
            .parents('.rules')
            .siblings()
            .find('.selectable-options > li')
            .removeClass('show-options')
        $(this).siblings().removeClass('show-options')

        $(this).toggleClass('show-options')
    })
    $(document).on('click', 'body', function () {
        $('.single-rules .wpfnl-selectbox').removeClass('show-options')
        $('.single-rules .selectable-options > li').removeClass('show-options')
    })

    $(document).on(
        'click',
        '.single-rules .submenu > li, .rules-is .selectable-options > li',
        function () {
            $('.single-rules .wpfnl-selectbox').removeClass('show-options')
        },
    )

    //---------------------------
    $(document).on('click', '.single-rules .selected-option', function (e) {
        $('.single-rules .wpfnl-custom-selectbox').removeClass('show-options')
    })

    $(document).on('click', '.wpfnl-custom-selectbox .selectbox-selected-value', function (e) {
        $('.single-rules .wpfnl-selectbox ').removeClass('show-options')
        $(this)
            .parents('.single-rules')
            .siblings()
            .find('.wpfnl-custom-selectbox')
            .removeClass('show-options')
    })

    // ------------------ Pro Modal Open and Close ---------------------- //

    // close modal starts
    $(document).on('click', '#wpfnl-pro-modal-close', function (e) { 
        const html = '<strong>Small</strong> <span>License for 1 site</span>';
        $('#pro-modal-package-type').html(html)
        $('.wpfnl-footer-btn-wrapper .btn-default.confirmed').attr('href', 'https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/');
        $('#wpfnl-pro-modal').hide()
    })

    $(document).on('click', '#wpfnl-pro-modal-close-btn', function (e) {
        const html = '<strong>Small</strong> <span>License for 1 site</span>';
        $('#pro-modal-package-type').html(html)
        $('.wpfnl-footer-btn-wrapper .btn-default.confirmed').attr('href', 'https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/');
        $('#wpfnl-pro-modal').hide()
    })
    // close modal ends

    /* Configure the pro modal
     * config[object] - parameter
     * showSubHeading - boolean
     * subHeading - string
     * confirmLink - string
     * features - Array of Object
     * label - String
     * value - String
     */
    const configureProModal = (config) => {
        const { showSubHeading, subHeading, confirmLink, features } = config

        // Define Ratings and Users
        const ratings = 105
        const users = 8000

        // Define Elements
        const modalElement = document.querySelector('#wpfnl-pro-modal')
        const subHeadingElement = document.querySelector('#wpfnl-pro-modal .wpfnl-pro-sub-heading')
        const confirmBtnElement = document.querySelector('#wpfnl-pro-modal .btn-default.confirmed')
        const modalBodyElement = document.querySelector('#wpfnl-pro-modal .wpfnl-pro-modal-body')
        const featureFirstColElement = document.querySelector(
            '#wpfnl-pro-modal .wpfnl-pro-modal-body .wpfnl-pro-features.first-col',
        )
        const featureSecondColElement = document.querySelector(
            '#wpfnl-pro-modal .wpfnl-pro-modal-body .wpfnl-pro-features.second-col',
        )
        const footerTextElement = document.querySelector(
            '#wpfnl-pro-modal .wpfnl-pro-modal-footer-text span',
        )

        // Tic Icon for Features
        const ticIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1.45455 6.23252C1.17901 6.23329 0.909321 6.31206 0.676682 6.45971C0.444044 6.60736 0.257963 6.81786 0.139966 7.06685C0.0219701 7.31585 -0.0231188 7.59316 0.00991618 7.86671C0.0429511 8.14026 0.15276 8.39887 0.326638 8.61261L4.03332 13.1533C4.16548 13.3174 4.33489 13.4476 4.52748 13.5331C4.72006 13.6186 4.93026 13.6569 5.14063 13.6448C5.59054 13.6206 5.99673 13.38 6.2557 12.9842L13.9554 0.583858C13.9567 0.581801 13.958 0.579744 13.9593 0.577717C14.0316 0.46679 14.0082 0.246962 13.859 0.108864C13.8181 0.0709399 13.7698 0.0418041 13.7171 0.0232503C13.6645 0.00469649 13.6086 -0.00288247 13.5529 0.000980338C13.4972 0.00484315 13.4429 0.020066 13.3933 0.0457111C13.3437 0.0713562 13.2999 0.106881 13.2646 0.150097C13.2618 0.153494 13.259 0.156841 13.2561 0.160136L5.49078 8.93373C5.46124 8.96712 5.42535 8.9943 5.38521 9.0137C5.34507 9.0331 5.30147 9.04433 5.25695 9.04674C5.21243 9.04915 5.16788 9.04268 5.12588 9.02773C5.08388 9.01277 5.04527 8.98962 5.01229 8.95961L2.43514 6.61439C2.16749 6.36903 1.81765 6.23279 1.45455 6.23252Z" fill="#2FCF5C"></path></svg>`

        // Show or hide subheading
        if (showSubHeading) {
            subHeadingElement.innerHTML = subHeading
        } else {
            modalElement.removeChild(subHeadingElement)
        }

        // confirm link setting
        if (confirmLink) {
            confirmBtnElement.setAttribute('href', confirmLink)
        } else {
            confirmBtnElement.setAttribute('href', 'https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/')
        }

        // features setting
        if (Array.isArray(features) && features?.length > 0) {
            let middle = Math.ceil(features.length / 2)
            featureFirstColElement.innerHTML = ''
            featureSecondColElement.innerHTML = ''

            features.forEach((feature, index) => {
                if (index < middle) {
                    featureFirstColElement.innerHTML += `<li>${ticIcon}<span>${feature.label}</span></li>`
                } else {
                    featureSecondColElement.innerHTML += `<li>${ticIcon}<span>${feature.label}</span></li>`
                }
            })
        } else {
            modalElement.removeChild(modalBodyElement)
        }

        // Set Footer Text
        if (footerTextElement)
            footerTextElement.innerHTML = `Easiest Funnel Builder: <strong>${users}+</strong> Users, <strong>${ratings}+</strong> Five-Star Reviews</strong>`
    }

    // modal Open function
    const openModal = (config) => {
        configureProModal(config)
        $('#wpfnl-pro-modal').css('display', 'flex')
    }
    // open modal starts
    $(document).on('click', '#wpfnl-export-all-pro', function (e) {
        const config = {
            showSubHeading: true,
            subHeading: 'Upgrade to Export Funnels.',
            confirmLink: 'https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/',
            features: [
                {
                    label: 'Export Funnels',
                    value: 'export_funnels',
                },
                {
                    label: 'Download Funnel as JSON',
                    value: 'download_funnel_as_json',
                },
                {
                    label: 'Export Single Funnel',
                    value: 'export_single_funnels',
                },
                {
                    label: 'Bulk Export',
                    value: 'bulk_export',
                },
            ],
        }
        openModal(config)
    })

    $(document).on('click', '#wpfnl-import-funnels-pro, .pro-import-button', function (e) {
        const config = {
            showSubHeading: true,
            subHeading: 'Upgrade to Import Funnels.',
            confirmLink: 'https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/',
            features: [
               
                {
                    label: 'Import JSON File',
                    value: 'import_json_to_create_funnel',
                },
                {
                    label: 'Import Funnels',
                    value: 'import_funnels',
                },
                {
                    label: 'Bulk Import',
                    value: 'bulk_import_funnels',
                },
            ],
        }
        openModal(config)
    })

    $(document).on('click', '#wpfnl-offer-settings', function (e) {
        const config = {
            showSubHeading: true,
            subHeading: 'Upgrade to Unlock Offer Settings.',
            confirmLink: 'https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/',
            features: [
                {
                    label: 'Create Child Order',
                    value: 'create_child_order',
                },
                {
                    label: 'Show Supported Payment Gateway',
                    value: 'show_supported_payment_gateway',
                },
                {
                    label: 'Skip Offer',
                    value: 'skip_offer',
                },
            ],
        }
        openModal(config)
    })

    $('.duplicate.wpfnl-export-funnel-pro').on('click', function (e) {
        const config = {
            showSubHeading: true,
            subHeading: 'Upgrade to Export Funnels.',
            confirmLink: 'https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/',
            features: [
                {
                    label: 'Export Funnels',
                    value: 'export_funnels',
                },
                {
                    label: 'Download Funnel as JSON',
                    value: 'download_funnel_as_json',
                },
                {
                    label: 'Export Single Funnel',
                    value: 'export_single_funnels',
                },
                {
                    label: 'Bulk Export',
                    value: 'bulk_export',
                },
            ],
        }
        openModal(config)
    })

    $('#facebook-pixel-enable-pro').on('click', function (e) {
        const config = {
            showSubHeading: true,
            subHeading:
                'This feature is only available in the Pro version. Upgrade Now to continue all these awesome features',
            confirmLink: 'https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/',
            features: [
                {
                    label: 'Unlimited Contacts k',
                    value: 'unlimited_contacts',
                },
                {
                    label: 'Conditional Prancing k',
                    value: 'conditional_prancing',
                },
                {
                    label: '360 Contacts view',
                    value: '360_contacts_view',
                },
                {
                    label: 'Connect with From Plugins',
                    value: 'Connect_with_From_Plugins',
                },
                {
                    label: 'Over 60+ Integrations',
                    value: 'Over_60+_Integrations',
                },
            ],
        }
        openModal(config)
    })

    $(document).on('click', '#facebook-pixel-enable-pro', function (e) {
        e.preventDefault()
        e.target.checked = false
        const config = {
            showSubHeading: true,
            subHeading: 'Upgrade to Pro for FB Pixel Tracking.',
            confirmLink: 'https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/',
            features: [
                {
                    label: 'FB Pixel Tracking',
                    value: 'fb_pixel_tracking',
                },
                {
                    label: 'Enable/Disable for Single Funnel',
                    value: 'enable_disable_single_funnel',
                },
                {
                    label: 'Purchase Event Tracking',
                    value: 'purchase_event_tracking',
                },
                {
                    label: 'Page View Tracking',
                    value: 'page_view_tracking',
                },
                {
                    label: 'Add to Cart Tracking',
                    value: 'add_to_cart_tracking',
                },
                {
                    label: 'Upsell/Downsell Tracking',
                    value: 'offer_tracking',
                },
            ],
        }
        openModal(config)
    })

    $(document).on('click', '#gtm-enable-pro', function (e) {
        e.preventDefault()
        e.target.checked = false
        const config = {
            showSubHeading: true,
            subHeading: 'Upgrade to Pro for GTM Tracking.',
            confirmLink: 'https://useraccount.getwpfunnels.com/wpfunnels-annual/steps/annual-small-checkout/',
            features: [
                {
                    label: 'GTM Tracking',
                    value: 'gtm_tracking',
                },
                {
                    label: 'Enable/Disable for Single Funnel',
                    value: 'enable_disable_single_funnel',
                },
                {
                    label: 'Purchase Event Tracking',
                    value: 'purchase_event_tracking',
                },
                {
                    label: 'Page View Tracking',
                    value: 'page_view_tracking',
                },
                {
                    label: 'Add to Cart Tracking',
                    value: 'add_to_cart_tracking',
                },
                {
                    label: 'Shipping Info Tracking',
                    value: 'shipping_tracking',
                },
                {
                    label: 'OrderBump Tracking',
                    value: 'orderbump_tracking',
                },
                {
                    label: 'Upsell/Downsell Tracking',
                    value: 'offer_tracking',
                },
            ],
        }
        openModal(config)
    })

    // open modal ends

    // Close Toaster
    $(document).on('click', '#wpfnl-toaster-close-btn', function (e) {
        $('#wpfnl-toaster-wrapper').hide()
    })

    // Pro modal
    $(document).on('click', '#pro-modal-dropdown-btn', function () {
        $('#pro-modal-dropdown-body').toggleClass('show-dropdown')
        $('#pro-modal-dropdown-btn').toggleClass('btn-rotate')
    })
    
    $(document).on('click', '.wpfnl-pro-modal-dropdown li', function () {
        const selectedValue = $(this).attr('value')
        const url = $(this).data('url')
        const selectedHTML = $(this).html()
       
        $('#pro-modal-package-type').html(selectedHTML)
        $('#pro-modal-package-price').html(`<strong>$ ${selectedValue}</strong> <span>/year</span>`)
        $('.wpfnl-footer-btn-wrapper .btn-default.confirmed').attr('href', url);
        $('#pro-modal-dropdown-body').toggleClass('show-dropdown')
        $('#pro-modal-dropdown-btn').toggleClass('btn-rotate')
    })
})(jQuery)
