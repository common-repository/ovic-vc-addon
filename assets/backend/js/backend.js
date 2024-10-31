;(function ($) {
    "use strict";

    var serializeObject = function (form) {
        var o = {};
        var a = form.serializeArray();
        $.each(a, function () {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

    $.fn.ovic_vc_dependency_data = function () {
        $(this).each(function () {
            var $this       = $(this),
                $data       = $this.data(),
                $parent     = $this.closest('form'),
                $value      = $this.find('.value_input'),
                $dependency = $parent.find($data.dependency);

            if ($data.compare === 'not') {
                if (JSON.parse($value.val()) != $data.value || $value.val() != $data.value) {
                    $dependency.slideDown(300);
                } else {
                    $dependency.slideUp(300);
                }
            } else if ($data.compare === 'check') {
                if ($value.is(':checked')) {
                    $dependency.slideDown(300);
                } else {
                    $dependency.slideUp(300);
                }
            } else {
                if (JSON.parse($value.val()) == $data.value || $value.val() == $data.value) {
                    $dependency.slideDown(300);
                } else {
                    $dependency.slideUp(300);
                }
            }
        });
    };

    $.fn.ovic_vc_generate_data = function () {
        $(this).each(function () {
            var obj    = $(this).serialize(),
                parent = $(this).closest('.grid-field-settings');

            parent.find('.wpb_vc_param_value').val(obj);
        });
    };

    $.fn.ovic_vc_select_preview = function () {
        $(this).each(function () {
            var $this      = $(this),
                $selected  = $this.val(),
                $container = $this.closest('.container-select_preview'),
                $button    = $container.find('.vc_general'),
                $modal     = $('#' + $button.val()),
                $trigger   = 'trigger_' + $this.attr('id');

            $this.on($trigger, function () {
                $this.each(function () {
                    var url = $(this).find(':selected').data('preview');
                    $container.find('.image-preview img').attr('src', url);
                });
            }).trigger($trigger);

            $container.on('click', '.vc_general', function () {
                var $html        = '',
                    $count       = 1,
                    $count_total = 0,
                    $button      = $(this),
                    $total       = $this.find('option').length,
                    $load        = $modal.find('.ovic-modal-load');

                $load.html('');

                $this.find('option').each(function () {
                    var value   = $(this).val(),
                        url     = $(this).data('preview'),
                        text    = $(this).text(),
                        classes = 'select-preview equal-elem';

                    if ($count == 1) {
                        $html += '<div>';
                    }
                    if (value == $selected) {
                        classes += ' selected';
                    }

                    $count++;
                    $count_total++;

                    $html += '<a href="#' + $this.attr('id') + '" class="' + classes + '" data-value="' + value + '">';
                    $html += '   <img src="' + url + '">';
                    $html += '   <span class="title">' + text + '</span>';
                    $html += '</a>';

                    if ($count == 5 || $total == $count_total) {
                        $count = 1;
                        $html += '</div>';
                    }

                });

                $load.append($html);

                $modal.show();

            });

            $modal.on('click', 'a.select-preview', function () {

                var id    = $(this).attr('href'),
                    value = $(this).attr('data-value');

                $('select' + id).val(value).trigger($trigger);

                $modal.hide();

                return false;
            });

            $modal.on('click', '.ovic-modal-close, .ovic-modal-overlay', function () {
                $modal.hide();
            });

            $this.on('change', function () {
                $this.trigger($trigger);
            });
        });
    };

    $.fn.ovic_vc_datepicker = function () {
        var self = $(this);
        self.on('ovic_vc_datepicker', function () {
            self.each(function () {
                var $this   = $(this),
                    $input  = $this.find('input'),
                    options = JSON.parse($this.find('.ovic-vc-datepicker-options').val()),
                    wrapper = '<div class="ovic-vc-datepicker-wrapper"></div>',
                    $datepicker;

                var defaults = {
                    beforeShow: function (input, inst) {
                        $datepicker = $('#ui-datepicker-div');
                        $datepicker.wrap(wrapper);
                    },
                    onClose   : function () {
                        var cancelInterval = setInterval(function () {
                            if ($datepicker.is(':hidden')) {
                                $datepicker.unwrap(wrapper);
                                clearInterval(cancelInterval);
                            }
                        }, 100);
                    }
                };

                options = $.extend({}, options, defaults);

                $input.datepicker(options);
            })
        }).trigger('ovic_vc_datepicker');
        $(document).on('change', function () {
            self.trigger('ovic_vc_datepicker');
        });
    };

    $.fn.ovic_vc_datetime = function () {
        var $this = $(this);
        $this.on('ovic_vc_datetime', function () {
            $this.each(function () {
                var $date  = $(this).find('.vc-field-date').val(),
                    $time  = $(this).find('.vc-field-time').val(),
                    $value = $(this).find('.wpb_vc_param_value');

                $value.val($date + ' ' + $time);
            })
        }).trigger('ovic_vc_datetime');
        $(document).on('change', function () {
            $this.trigger('ovic_vc_datetime');
        });
    };

    $.fn.ovic_vc_chosen = function () {
        return this.each(function () {

            var $this       = $(this),
                $inited     = $this.parent().find('.chosen-container'),
                is_multiple = $this.attr('multiple') || false,
                set_options = $.extend({
                    allow_single_deselect   : true,
                    disable_search_threshold: 10,
                    width                   : '100%',
                }, $this.data('chosen-settings'));

            if ($inited.length) {
                $inited.remove();
            }

            $this.chosen(set_options);

            // Chosen keep options order
            if (is_multiple) {
                var $hidden_select = $this.parent().find('.ovic-hidden-select'),
                    $hidden_value  = $hidden_select.val() || [];

                $this.on('change', function (obj, result) {

                    if (result && result.selected) {
                        $hidden_select.append('<option value="' + result.selected + '" selected="selected">' + result.selected + '</option>');
                    } else if (result && result.deselected) {
                        $hidden_select.find('option[value="' + result.deselected + '"]').remove();
                    }

                    $hidden_select.trigger('change');

                });
                // Chosen order abstract
                ChosenOrder.setSelectionOrder($this, $hidden_value, true);
            }

        });
    };

    /* FRAMEWORK JS */
    $(document).on('click', '.vc_edit-form-tab .tab-item', function () {
        var $this     = $(this),
            $content  = $this.closest('.vc_edit-form-tab'),
            $parent   = $this.closest('.vc_shortcode-param'),
            $data_tab = $this.data('tabs');

        $content.find('.vc_shortcode-param').not($parent).css('display', 'none');
        $this.addClass('active').siblings().removeClass('active');
        $content.find('.vc_shortcode-param.' + $data_tab).css('display', 'block');
    });

    $(document).on('click', '.ovic_vc_options button[type="submit"]', function (e) {
        e.preventDefault();
        var $reset = 0,
            $this  = $(this),
            $form  = $this.closest('form'),
            $data  = serializeObject($form),
            $alert = $form.find('.alert-tool');

        $form.addClass('loading');
        if ($this.hasClass('reset'))
            $reset = 1;
        $.ajax({
            type   : 'POST',
            url    : ovic_vc_params.ajaxurl,
            data   : {
                action: 'ovic_vc_submit',
                data  : $data,
                reset : $reset,
            },
            success: function (xhr, textStatus) {
                if (textStatus === 'success') {
                    $form.find('tbody').html(xhr['html']);
                    $alert.html('<div class="notice notice-success is-dismissible" style="margin: 5px 0 10px;"><p>Updated Success!</p></div>');
                } else {
                    $alert.html('<div class="notice notice-error is-dismissible" style="margin: 5px 0 10px;"><p>Updated Failed!</p></div>');
                }
                $form.removeClass('loading');
            }
        });
    });

    $(document).on('click', '.ovic_vc_options .field-item.export span.dashicons', function (e) {
        e.preventDefault();
        var $parent = $(this).closest('.field-item');

        $parent.toggleClass('open');
    });

    $(document).on('click', '.ovic_vc_options .field-item.export button.import', function (e) {
        e.preventDefault();
        var $this = $(this),
            $form = $this.closest('form'),
            $data = $form.find('.import-field').val();

        $form.addClass('loading');
        $.ajax({
            type   : 'POST',
            url    : ovic_vc_params.ajaxurl,
            data   : {
                action: 'ovic_vc_import_options',
                data  : $data,
            },
            success: function (xhr, textStatus) {
                $form.find('.import-field').val('');
                if (textStatus === 'success') {
                    window.location.reload();
                }
                $form.removeClass('loading');
            }
        });
    });

    $(document).on('click', '.ovic_vc_options .add-screen a', function (e) {
        e.preventDefault();
        var $html = '',
            $this = $(this),
            $form = $this.closest('form');

        $html = '<tr class="item-vc">' +
                '<td><label><input type="text" name="name" placeholder="Name *"></label></td>' +
                '<td><label><select type="text" name="media"><option value="max-width">max-width</option><option value="min-width">min-width</option></select></label></td>' +
                '<td><label><input type="text" name="screen" placeholder="Screen *"></label></td>' +
                '<td style="text-align:center;vertical-align:middle;"><a href="#" class="remove">Remove</a></td></tr>';
        $form.find('tbody').append($html);
    });

    $(document).on('click', '.ovic_vc_options .item-vc .remove', function (e) {
        e.preventDefault();
        $(this).closest('.item-vc').remove();
    });

    $(document).on('change', '.form-grid-data .dependency', function () {
        $(this).ovic_vc_dependency_data();
    });

    $(document).on('change', '.form-grid-data', function () {
        $(this).ovic_vc_generate_data();
    });

    $(document).on('click', '.form-grid-data .vc_param_group-add_content', function () {
        var ID      = function () {
                // Math.random should be unique because of its seeding algorithm.
                // Convert it to base 36 (numbers + letters), and grab the first 9 characters
                // after the decimal.
                return '_' + Math.random().toString(36).substr(2, 9);
            },
            ids     = 'responsive_' + ID(),
            name    = 'new_screen_' + ID(),
            content = '<p class="field-item ' + name + '">' +
                      '<span class="wpb_element_label">New Screen</span>' +
                      '<label data-tip="Screen Responsive"><input style="width:calc(100% - 180px);" name="responsive[' + name + '][breakpoint]" type="text" class="value_input" value="1024"></label>' +
                      '<label data-tip="Item to Show"><input style="width:60px;" name="responsive[' + name + '][settings][slidesToShow]" type="text" class="value_input" value="4"></label>' +
                      '<label data-tip="Margin Items"><input style="width:60px;" name="responsive[' + name + '][settings][slidesMargin]" type="text" class="value_input" value="30"></label>' +
                      '<label data-tip="Number Rows"><input style="width:60px;" name="responsive[' + name + '][settings][rows]" type="text" class="value_input" value="1"></label>' +
                      '<label for="' + ids + '" class="disable-vertical"><input id="' + ids + '" name="responsive[' + name + '][settings][vertical]" type="checkbox" class="value_input" value="false"> Disable Vertical</label>' +
                      '<span class="vc_description vc_clearfix"></span>' +
                      '<span class="remove button">Remove</span>' +
                      '</p>';
        $(content).insertBefore(this);
    });

    $(document).on('click', '.form-grid-data .field-item .remove', function () {
        var $field = $(this).closest('.field-item'),
            $form  = $(this).closest('.form-grid-data');

        $field.remove();
        $form.ovic_vc_generate_data();
    });

    $(document).on('vcPanel.shown', function (event) {
        if ($(event.target).find('.ovic-vc-chosen').length) {
            $(event.target).find('.ovic-vc-chosen').ovic_vc_chosen();
        }
        if ($(event.target).find('.ovic_select_preview').length) {
            $(event.target).find('.ovic_select_preview').ovic_vc_select_preview();
        }
        if ($(event.target).find('.ovic-vc-field-date').length) {
            $(event.target).find('.ovic-vc-field-date').ovic_vc_datepicker();
            $(event.target).find('.vc-date-time-picker').ovic_vc_datetime();
        }
        if ($(event.target).find('form.form-grid-data').length) {
            $(event.target).find('.form-grid-data .dependency').ovic_vc_dependency_data();
            $(event.target).find('.form-grid-data').ovic_vc_generate_data();
        }
        if (wp.media) {
            wp.media.view.Modal.prototype.on('close', function () {
                setTimeout(function () {
                    $('.supports-drag-drop').hide();
                }, 1000)
            });
        }
    });

})(jQuery, window, document);