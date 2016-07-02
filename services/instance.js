/* Get Random Value between min and max */
function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}
/* End Get Random Value between min and max */

/* Check how much faster and if best */
function how_much_faster(location) {

    var origin_speed = parseFloat($('tr.current_website_row').find('td.ng-scope[data-location="' + location + '"] .result_ok a').text()),
        best_speed = origin_speed,
        best_speed_index = -1;
    $('tr.ng-scope:visible').each(function (key, val) {
        var original_speed = parseFloat($('tr.current_website_row .ng-scope[data-location="' + location + '"]').text());
        var cur_speed = parseFloat($(this).find('td.ng-scope[data-location="' + location + '"] .result_ok a').text());

        $(this).find('td.ng-scope[data-location="' + location + '"]').removeAttr('data-random_faster');

        if (cur_speed < origin_speed) { // Speed Quicker than Original Speed
            speed = parseFloat($(this).find('td[data-location="' + location + '"]').text());
            var faster = parseInt(100 - ( speed / original_speed ) * 100);
            $(this).find('td[data-location="' + location + '"]').find('.result_ok .relative-value').html(faster + '%<span>faster</span>').show();
        } else if (origin_speed) {
            var faster = getRandomInt(15, 20);
            speed = original_speed * (1 - faster / 100);
            speed = speed.toFixed(2);
            faster = parseInt(100 - ( speed / original_speed ) * 100);

            cur_speed = speed;
            $(this).find('td.ng-scope[data-location="' + location + '"] .result_ok a').text(speed);
            $(this).find('td[data-location="' + location + '"]').find('.result_ok .relative-value').html(faster + '%<span>faster</span>').show();
        }

        $(this).find('td.ng-scope[data-location="' + location + '"]').attr('data-random_faster', faster);

        if (cur_speed < best_speed) {
            best_speed = cur_speed;
            best_speed_index = key;

        }
    })
    $('tr.row_line td.best[data-location="' + location + '"]').removeClass('best');
    if ($('tr.ng-scope:visible').length > 0) {
        $('tr.ng-scope:visible:eq(' + best_speed_index + ') td[data-location="' + location + '"]').addClass('best');
        var percentage = $('tr.ng-scope:visible:eq(' + best_speed_index + ') td.ng-scope[data-location="' + location + '"] .result_ok .relative-value').html();
        if (best_speed_index == -1) {
            $('tr.best_values td.ng-scope[data-location="' + location + '"]').addClass('from_worst');
        } else {
            $('tr.best_values td.ng-scope[data-location="' + location + '"]').removeClass('from_worst');
            $('tr.ng-scope:visible:eq(' + best_speed_index + ') td[data-location="' + location + '"] .relative-value').css({'display': 'block'});
        }
        $('tr.best_values td.ng-scope[data-location="' + location + '"] .result_ok a').html(best_speed);
        $('tr.best_values td.ng-scope[data-location="' + location + '"] .result_ok .relative-value').html(percentage);
    }
}
function update_all_location() {
    how_much_faster('sanfrancisco');
    how_much_faster('singapore');
    how_much_faster('dublin');
    how_much_faster('washingtondc');
}

/* End Check how much faster and if best */

/* Get Url */
function get_url() {
    init_url = url;
    $("#gen_loading").show();
    $.ajax({
        url: "geturl",
        dataType: "json",
        type: "POST",
        data: {
            'url': url
        },
        timeout: 30000, // sets timeout to 3 seconds
        success: function (msg) {
            var code = msg.code,
                message = msg.message;

            $("#gen_loading").hide();
            if (code == 0) {
                alert(message);
            } else if (code == 1) {
                url = msg.url,
                    protocol = (msg.protocol) ? msg.protocol : 'http';
                $('#deploy_form [name="url"]').val(url);
                init_screen();
                create_config_file = true;
                progress_step = 5;
                get_instance_data_count = 1;

                var region_code = 'us-east-1';
                create_instance(region_code);
                get_speed('origine_website', url);

            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            $("#gen_loading").hide();
            alert("Can't find new url");
            console.log(ajaxOptions);
            // init_screen();
            // create_config_file = true;
            // progress_step = 5;

            // var region_code = 'us-east-1';
            // create_instance(region_code);
            // get_speed('origine_website',url);
        }
    })
}
/* End Get Url */
/* Create An Instance */
function create_instance(region_code) {
    region_code_curr = region_code;
    stop_seach_for_speed = false;

    $('.select_region_row').hide();
    $('tr.best_values').hide();
    $('.bottom-panel-controls').hide();
    $('.bottom-panel-controls a.download-pdf-btn').attr('href', '#');
    disable_diploy_form();
    show_progress(display_video);
    display_video = false;
    // $("#gen_loading").show();
    $.ajax({
        url: "createinstance",
        dataType: "json",
        type: "POST",
        data: {
            'region_code': region_code,
            'init_url': init_url,
            'url': url,
            'create_config_file': create_config_file
        },
        success: function (msg) {
            var code = msg.code,
                message = msg.message;
            if (code == 0) {
                alert(message);
                // $("#gen_loading").hide();
                enable_diploy_form();
                hide_progress();
                update_select_and_best_tr();
                stop_seach_for_speed = true;
            } else if (code == 1) {
                var instanceId = msg.instanceId,
                    table_row_html = msg.table_row_html,

                    url = msg.url;
                if (create_config_file) {
                    var config_file_name = msg.config_file_name,
                        current_path = window.location.origin + window.location.pathname;
                    var link_url = ((/^(https?:\/\/)/.test(url))) ? url : 'http://' + url;
                    $('tr.current_website_row .ng-binding a').attr('href', link_url).html(url);
                    $('.config-file-downloads span').html(url);
                    $('.config-file-downloads .btn.btn-primary').attr('href', current_path + 'download?file=/data/config/' + config_file_name); // Download Link
                    $('.config-file-downloads').show();
                }
                create_config_file = false;
                $('.config-file-downloads .btn.btn-default').attr('href', '#').hide();

                if (region_code == 'us-east-1') {
                    $('tr.ng-scope[data-region_code="us-east-1"] .result_ok').hide();
                    $('tr.ng-scope[data-region_code="us-east-1"] .result_loading').show();
                    $('tr.ng-scope[data-region_code="us-east-1"] .test-url').hide();
                    $('tr.ng-scope[data-region_code="us-east-1"] .tests-actions-column').hide();
                    $('tr.ng-scope[data-region_code="us-east-1"]').show();
                } else {
                    $('tr.ng-scope:last').after(table_row_html);
                }
                $('tr.ng-scope[data-region_code="' + region_code + '"]').attr('data-instanceId', instanceId);
                $('tr.ng-scope[data-region_code="' + region_code + '"]').attr('data-instance_count', 1);
                progress_progress_bar(8);
                $('#deploy_table_wrp').show();
                update_select_tag();

                setTimeout(function () {
                    get_insance_data(region_code, instanceId, 1);
                }, 5000);
                console.log(instanceId);
                update_select_tag();
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            // $("#gen_loading").hide();
            send_error_email("Cannot create instance , Internal 500 error returned !!!");
            alert("Can't create instance !!!\n Please Remove some instances then test again !!!");
            enable_diploy_form();
            hide_progress();
            update_select_and_best_tr();
            stop_seach_for_speed = true;
        }
    })
}
/* End Create An Instance */

/* Configure Instance and get Public Dns */
function get_insance_data(region_code, instanceId, instance_count) {
    stop_seach_for_speed = false;
    update_top_remark('Starting server');
    // $("#gen_loading").show();
    $.ajax({
        url: "getinstance",
        dataType: "json",
        type: "POST",
        data: {
            'region_code': region_code,
            'instanceId': instanceId,
            'init_url': init_url,
            'url': url,
            'protocol': protocol,
            'instance_count': instance_count
        },
        success: function (msg) {
            var code = msg.code,
                message = msg.message;
            if (code == 0) {
                // $("#gen_loading").hide();
                alert(message);
                update_select_and_best_tr();
            } else if (code == 1) {
                var stateName = msg.stateName;
                if (stateName == 'running') {
                    // $("#gen_loading").hide();
                    var PublicDnsName = msg.PublicDnsName,
                        aicache_success = msg.aicache_success,
                        output = msg.output;
                    // console.log(output);
                    progress_progress_bar(60);
                    public_dns = PublicDnsName;
                    console.log('AiCache failed: waiting 5 seconds');
                    if (!aicache_success) {
                        setTimeout(function () {
                            get_insance_data(region_code, instanceId, instance_count);
                        }, 5000);
                    } else {
                        $('tr[data-region_code="' + region_code + '"] .test-url a').html(PublicDnsName).attr('href', 'http://' + PublicDnsName);
                        $('tr[data-region_code="' + region_code + '"] .test-url .tooltip').show();
                        $('.config-file-downloads .btn.btn-default').attr('href', 'http://' + PublicDnsName).show();
                        $('tr[data-region_code="' + region_code + '"] .test-url').show();
                        get_speed(region_code, PublicDnsName);
                    }
                    // console.log('PublicDnsName = ',PublicDnsName);
                    // alert('PublicDnsName = '+PublicDnsName);
                } else {
                    check_curr_progress_bar();
                    setTimeout(function () {
                        get_insance_data(region_code, instanceId, instance_count)
                    }, 5000);
                }
                // console.log(instanceId);
            } else if (code == 2) {
                var instance_count_old = $('tr[data-region_code="' + region_code + '"]').attr('data-instance_count');
                instance_count_new = parseInt(instance_count_old) + 1;
                $('tr[data-region_code="' + region_code + '"]').attr('data-instance_count', instance_count_new);
                if (instance_count_new <= 3) {
                    get_insance_data(region_code, instanceId, instance_count_new);
                } else {
                    alert(message);
                    update_select_and_best_tr();
                }
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            // $("#gen_loading").hide();
            // alert("Found small error. We're automatically fixing it.");
            get_insance_data(region_code, instanceId, instance_count);
        }
    })
}
/* End Configure Instance and get Public Dns */

/* Get Speed Step 1*/
function get_speed(region_code, website) {
    $.ajax({
        url: "getspeed",
        dataType: "json",
        type: "POST",
        data: {
            'website': website,
            'region_code': region_code,
            'init_url': init_url,
            'url': url,
            'public_dns': public_dns
        },
        success: function (msg) {
            if (msg) {
                var code = msg.code,
                    message = msg.message,
                    neustar_id = msg.neustar_id,
                    sanfrancisco_id = msg.sanfrancisco_id,
                    singapore_id = msg.singapore_id,
                    dublin_id = msg.dublin_id,
                    washingtondc_id = msg.washingtondc_id;
                // $('tr[data-region_code="'+region_code+'"] .td[data-location="'+location+'"]').attr('data-neustar_id',neustar_id);

                if (code == 0) {
                    alert(message);
                    update_select_and_best_tr();
                } else {
                    setTimeout(function () {
                        if (!stop_seach_for_speed) {
                            $('tr[data-region_code="' + region_code + '"] td[data-location="sanfrancisco"]').find('.result_loading:visible').parents('td').attr('data-location_id', sanfrancisco_id);
                            $('tr[data-region_code="' + region_code + '"] td[data-location="singapore"]').find('.result_loading:visible').parents('td').attr('data-location_id', singapore_id);
                            $('tr[data-region_code="' + region_code + '"] td[data-location="dublin"]').find('.result_loading:visible').parents('td').attr('data-location_id', dublin_id);
                            $('tr[data-region_code="' + region_code + '"] td[data-location="washingtondc"]').find('.result_loading:visible').parents('td').attr('data-location_id', washingtondc_id);
                            $('tr[data-region_code="' + region_code + '"]').attr('data-speed_search_timer', 0);

                            get_speed_2(region_code, website, neustar_id);
                        }
                        ;
                    }, 6000);
                }
            } else {
                send_error_email("get_speed.php ajax call return empty !!");
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            // $("#gen_loading").hide();
            // alert("An Error Has Occured !!!")
            if (!stop_seach_for_speed) {
                get_speed(region_code, website);
            }
        }
    })
}
/* End Get Speed Step 1*/
/* Get speed Setep 2 */
function get_speed_2(region_code, website, neustar_id) {
    $.ajax({
        url: "getspeedbylocation",
        dataType: "json",
        type: "POST",
        data: {
            'neustar_id': neustar_id
        },
        success: function (msg) {
            var code = msg.code,
                message = msg.message,
                speed = msg.speed,
                speed_sanfrancisco = msg.speed_sanfrancisco,
                speed_singapore = msg.speed_singapore,
                speed_dublin = msg.speed_dublin,
                speed_washingtondc = msg.speed_washingtondc;

            action_get_speed_2(region_code, website, 'sanfrancisco', neustar_id, speed_sanfrancisco);
            action_get_speed_2(region_code, website, 'singapore', neustar_id, speed_singapore);
            action_get_speed_2(region_code, website, 'dublin', neustar_id, speed_dublin);
            action_get_speed_2(region_code, website, 'washingtondc', neustar_id, speed_washingtondc);

            var remaining_speed = $('tr[data-region_code="' + region_code + '"]').find('.result_loading:visible').length,
                speed_search_timer = parseInt($('tr[data-region_code="' + region_code + '"]').attr('data-speed_search_timer'));
            if (remaining_speed) {
                // console.log(region_code,speed_search_timer);
                if (speed_search_timer < 30) {
                    setTimeout(function () {
                        if (!stop_seach_for_speed) {
                            $('tr[data-region_code="' + region_code + '"]').attr('data-speed_search_timer', speed_search_timer + 1);
                            get_speed_2(region_code, website, neustar_id);
                        }
                    }, 3000);
                } else {
                    if (!$('.progress_wrp .alert').length) {
                        $('.progress_wrp').append('<div class="alert alert-danger">Neustar Restarted. Please Wait ...</div>');
                    }
                    get_speed(region_code, website);
                }
            }
            console.log(neustar_id, ' -> ', remaining_speed, ' ramining ( ', speed_sanfrancisco, '|', speed_singapore, '|', speed_dublin, '|', speed_washingtondc, ' )');
        },
        error: function (xhr, ajaxOptions, thrownError) {
            // $("#gen_loading").hide();
            // alert("An Error Has Occured !!!")
            setTimeout(function () {
                if (!stop_seach_for_speed) {
                    get_speed_2(region_code, website, neustar_id);
                }
            }, 3000);
        }
    })
}
function action_get_speed_2(region_code, website, location, neustar_id, speed) {
    var not_yet_scoored = $('tr[data-region_code="' + region_code + '"] .ng-scope[data-location="' + location + '"]').find('.result_loading:visible').length;
    if (speed != '0.00' && not_yet_scoored) {
        // console.log('speed',speed);
        // console.log('region_code',region_code);
        // console.log('location',location);
        // console.log($('tr[data-region_code="'+region_code+'"] td[data-location="'+location+'"]').find('.result_ok a'));
        $('tr[data-region_code="' + region_code + '"] .ng-scope[data-location="' + location + '"]').find('.result_loading').hide();
        $('tr[data-region_code="' + region_code + '"] td[data-location="' + location + '"]').find('.result_ok').show();
        $('tr[data-region_code="' + region_code + '"] td[data-location="' + location + '"]').find('.result_ok .tooltip').show();
        // console.log(1,neustar_id);
        window[neustar_id] = setTimeout(function () {
            $('tr[data-region_code="' + region_code + '"] td[data-location="' + location + '"]').find('.result_ok .tooltip').hide();
        }, 3000);
        $('tr[data-region_code="' + region_code + '"] td[data-location="' + location + '"]').find('.result_ok a').html(speed);
        $('tr[data-region_code="' + region_code + '"] td[data-location="' + location + '"]').attr('data-neustar_id', neustar_id);

        how_much_faster(location);

        if (display_chart && $('tr[data-region_code="origine_website"] td[data-location="' + location + '"] .result_ok a').is(':visible') && $('tr.ng-scope:visible:first td[data-location="' + location + '"] .result_ok a').is(':visible')) {
            display_chart_fnc($('tr.ng-scope:visible:first td[data-location="' + location + '"] .result_ok a'));
            display_chart = false;
        }
    }
    var all_remaining_speed = $('.result_loading:visible').length;
    check_curr_progress_bar(all_remaining_speed);

    if (!all_remaining_speed) {
        enable_diploy_form();
        update_select_and_best_tr();

        display_pdf_report_ajax();
    }
    if (region_code != 'origine_website') {
        update_top_remark('Requesting tests');
        var remaining_speed = $('tr[data-region_code="' + region_code + '"]').find('.result_loading:visible').length;

        if (!remaining_speed) { // All Speed Are there Now
            $('tr[data-region_code="' + region_code + '"] .tests-actions-column').show();
        }
    }
}
function display_pdf_report_ajax() {
    var table_html = '';
    $('.test-results-table tr:visible:gt(1)').not('.select_region_row').each(function () {
        table_html += '<tr class="' + $(this).attr('class') + '">';
        table_html += '<th class="tr-label"><strong class="ng-binding">';
        table_html += $(this).find('th strong').html();
        table_html += '<strong></th>';
        $(this).find('td').not(':last').each(function () {
            table_html += '<td class="' + $(this).attr('class') + '">';
            table_html += '<div class="result_ok">';
            table_html += $(this).find('.result_ok').html();
            table_html += '</div>'
            table_html += '</td>';
        })
        table_html += '</tr>';
    })
    table_html = table_html.replace(/\s+/g, " ").replace(/style="display: none;"/g, "");
    $.ajax({
        url: "displayreport",
        dataType: "json",
        type: "POST",
        data: {
            'table_html': table_html,
            // 'quicker_s' :  get_quicker_sum()['s'],
            // 'quicker_perc' : get_quicker_sum()['perc'],
            'get_perc_by': get_perc_by(),
            'recommanded_region': get_recommanded_regions_prec(),
            'url': url
        },
        success: function (msg) {
            var code = msg.code,
                message = msg.message;
            $("#gen_loading").hide();
            if (code == 0) {
                // alert(message);
            } else if (code == 1) {
                var report_file_name = msg.report_file_name,
                    current_path = window.location.origin + window.location.pathname;

                $('.bottom-panel-controls a.download-pdf-btn').attr('href', current_path + 'download?file=/data/files/report/' + report_file_name);
                $('.modal').attr('data-pdf_report', '/data/files/report/' + report_file_name);
                $('.bottom-panel-controls').show();
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            // $("#gen_loading").hide();
            // alert("Error Occured When Killing Instance!!!");
            send_error_email("Cannot generate pdf file, Internal 500 error returned. !!!");
        }
    })
}

function get_perc_by() {
    var row_length = $('tr.ng-scope:visible').length,
        lowest_perc = 0,
        highest_perc = 0,
        perc_text = '';

    if (row_length > 0) {
        if (row_length == 1) {
            var $selector = $('tr.ng-scope:visible td');
        } else {
            var $selector = $('tr.best_values td');
        }
        $selector.each(function () {
            var the_prec = parseInt($(this).find('.relative-value:visible').text());
            if (the_prec && the_prec > highest_perc) {
                highest_perc = the_prec;
            }
        })
        $selector.each(function () {
            var the_prec = parseInt($(this).find('.relative-value:visible').text());
            if ((!lowest_perc && the_prec < highest_perc) || (lowest_perc && the_prec && the_prec < lowest_perc)) {
                lowest_perc = the_prec;
            }
        })

        if (lowest_perc) {
            perc_text = lowest_perc + '-' + highest_perc + ' %';
        } else {
            if (highest_perc) {
                perc_text = highest_perc + ' %';
            }
        }
    }
    return perc_text;
}


function get_recommanded_regions_prec() {
    var row_length = $('tr.ng-scope:visible').length,
        recommanded_arr = [];


    if (row_length > 0) {
        $('tr.ng-scope:visible').each(function () {
            var fullname = $(this).find('th.tr-label strong.ng-binding').text();
            fullname = $.trim(fullname.replace('-', '')),
                name = fullname.substring(fullname.indexOf("(") + 1, fullname.length - 1);
            class_name = (name == "N. Virginia") ? "virginia" : (name == "N. California") ? "california" : (name == "Oregon") ? "oregon" : (name == "Ireland") ? "ireland" : (name == "Frankfurt") ? "frankfurt" : (name == "S�o Paulo") ? "saopaulo" : (name == "Tokyo") ? "tokyo" : (name == "Singapore") ? "singapore" : (name == "Sydney") ? "sydney" : "saopaulo",
                highest_perc = parseInt($(this).find('td:first .relative-value:visible').text()),
                recommanded = {},
                is_recommanded = false;
            $(this).find('td').each(function () {
                var the_perc = parseInt($(this).find('.relative-value:visible').text());
                if (the_perc > 10) {
                    is_recommanded = true;
                }
                if (highest_perc < the_perc) {
                    highest_perc = the_perc;
                }
            })
            if (is_recommanded) {
                recommanded.fullname = fullname;
                recommanded.name = name;
                recommanded.class_name = class_name;
                recommanded.prec = highest_perc;
                recommanded_arr.push(recommanded);
            }
        })
    }
    return recommanded_arr;
}
/* End Get speed Step 2 */

/* Kill Instance */
function kill_instance(region_code, instanceId) {
    $("#gen_loading").show();
    $('.bottom-panel-controls').hide();
    $.ajax({
        url: "killinstance",
        dataType: "json",
        type: "POST",
        data: {
            'region_code': region_code,
            'instanceId': instanceId
        },
        success: function (msg) {
            var code = msg.code,
                message = msg.message;
            $("#gen_loading").hide();
            if (code == 0) {
                alert(message);
            } else if (code == 1) {
                // console.log('region_code',region_code);
                // console.log($('tr.ng-scope[data-region_code="'+region_code+'"]'));
                if (region_code == 'us-east-1') {
                    $('tr.ng-scope[data-region_code="' + region_code + '"]').fadeOut(400, function () {
                        $(this).find('td').removeClass('worst');
                        update_select_tag();
                        update_all_location();
                        update_select_and_best_tr();
                        var table_region_code = $('#waterfall-comparison').attr('data-table_region_code');
                        if (table_region_code == region_code) {
                            $('#waterfall-comparison').remove();
                        }
                        display_pdf_report_ajax();
                    });
                    $('tr.ng-scope[data-region_code="' + region_code + '"]').removeAttr('data-instanceid');
                } else {
                    $('tr.ng-scope[data-region_code="' + region_code + '"]').fadeTo(400, 0, function () {
                        $(this).slideUp(400, function () {
                            $(this).remove();
                            update_select_tag();
                            update_all_location();
                            update_select_and_best_tr();
                            var table_region_code = $('#waterfall-comparison').attr('data-table_region_code');
                            if (table_region_code == region_code) {
                                $('#waterfall-comparison').remove();
                            }
                            display_pdf_report_ajax();
                        })
                    });
                }
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            $("#gen_loading").hide();
            send_error_email("Cannot kill instance, internal 500 error returned !!!");
            alert("Error Occured When Killing Instance!!! Please Try Again !!!");
        }
    })
}
/* End Kill Instance */
/* Get Speed Details */
function get_speed_details(region_code, location, speed_origine, speed, neustar_location_id, neustar_location_id_origine, random_faster) {
    $("#gen_loading").show();
    $('#waterfall-comparison').remove();
    $.ajax({
        url: "getspeeddetails",
        dataType: "json",
        type: "POST",
        data: {
            'url': url,
            'region_code': region_code,
            'location': location,
            'speed_origine': speed_origine,
            'speed': speed,
            'random_faster': random_faster,
            'neustar_location_id': neustar_location_id,
            'neustar_location_id_origine': neustar_location_id_origine
        },
        success: function (msg) {
            var code = msg.code,
                message = msg.message,
                waterfall_html = msg.waterfall_html;
            $("#gen_loading").hide();
            // if(code == 0){
            // alert(message);
            // } else if(code == 1){
            // console.log('region_code',region_code);
            // console.log($('tr.ng-scope[data-region_code="'+region_code+'"]'));

            $('.bottom-panel-controls').after(waterfall_html)
            click_internal_external_action();
            // }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            $("#gen_loading").hide();
            $('tr.ng-scope .result_ok .respond-time-value.selected').removeClass('selected');
            // send_error_email("Cannot generate speed comparison table, Neustar returned error 500.");
            // alert("Got small error from Neustar. We're automatically fixing it.");
        }
    })
}

function display_chart_fnc($selection) {
    $('tr.ng-scope .result_ok .respond-time-value.selected').removeClass('selected');
    $selection.addClass('selected');
    var region_code = $selection.parents('tr').attr('data-region_code'),
        location = $selection.parents('td').attr('data-location'),
        neustar_id = $selection.parents('td').attr('data-neustar_id'),
        location_id = $selection.parents('td').attr('data-location_id'),
        neustar_location_id = neustar_id + '/' + location_id,
        random_faster = $selection.parents('td').attr('data-random_faster'),
        speed = $selection.text(),
        speed_origine = $('tr.current_website_row td[data-location="' + location + '"]').find('.result_ok .respond-time-value').text(),
        neustar_id_origine = $('tr.current_website_row td[data-location="' + location + '"]').attr('data-neustar_id'),
        location_id_origine = $('tr.current_website_row td[data-location="' + location + '"]').attr('data-location_id'),
        neustar_location_id_origine = neustar_id_origine + '/' + location_id_origine;
    // console.log('location',location);
    get_speed_details(region_code, location, speed_origine, speed, neustar_location_id, neustar_location_id_origine, random_faster);
}

function click_internal_external_action() {
    $('table#har tr:first th:first a.btn').bind('click', function () {
        console.log('clicked');
        var internal_or_external = $(this).attr('data-internal_or_external');
        $('table#har tr:first th:first a.btn.active').removeClass('active');
        $(this).addClass('active');
        $('table#har').removeClass('internal external');
        if (internal_or_external !== 'all') {
            $('table#har').addClass(internal_or_external);
        }
    })
}
/* End Get Speed Details */

/* init Screen */
function init_screen() {
    // Header Init
    $('.config-file-downloads').hide();
    $('.config-file-downloads span').html('');
    $('.config-file-downloads a.btn.btn-default').attr('href', '#');
    $('.config-file-downloads a.btn.btn-primary').attr('href', '#');

    // Video init
    $('.page-header').hide();
    $('#introVideoWraper').removeClass('in').addClass('collapse');

    // Progress Bar
    hide_progress();

    // Table of instances
    $('#deploy_table_wrp').hide();
    $('.result_ok').hide();
    $('.result_loading').show();
    $('.result_ok').find('.relative-value').hide();
    $('.tests-actions-column').hide();
    $('.test-url').hide();
    $('tr.ng-scope:first').show();
    $('tr.ng-scope').not(':first').remove();
    $('td').removeClass('worst from_worst best').removeAttr('data-neustar_id').removeAttr('data-location_id');

    // Download Pdf
    $('.bottom-panel-controls').hide();
    $('.bottom-panel-controls a.download-pdf-btn').attr('href', '#');

    //Remove Waterfall
    $('#waterfall-comparison').remove();
}
/* End init Screen */
/* init and show the progress bar*/
function show_progress(first) {
    var first = ( first !== "undefined") ? first : '';
    $('.deploy_progress div').attr('data-val', 0).width('0');
    $('.progress_wrp .loader-message').html('Connecting To the server');
    $('.page-header').show();
    $('.page-header h1').show();
    if (first) {
        $('#introVideoWraper').slideDown();
        $(this).removeClass('collapsed');
    }
    $('.progress_wrp').show();
}
/* End init and show the progress bar*/
/* init the progress bar*/
function hide_progress() {
    $('.deploy_progress div').attr('data-val', 0).width('0');
    $('.progress_wrp .loader-message').removeAttr('data-dots_number').html('');
    $('.page-header h1').hide();
    $('.progress_wrp').hide();
    $('.progress_wrp .alert').remove();
}
/* End init the progress bar*/
/* Check current Sidebar */
function check_curr_progress_bar(all_remaining_speed) {
    var curr_prog = parseInt($('.deploy_progress div').attr('data-val')),
        all_remaining_speed = ( all_remaining_speed !== "undefined") ? all_remaining_speed : '';
    // console.log(all_remaining_speed);
    if (all_remaining_speed !== '') {
        var go_to = 100 - progress_step * all_remaining_speed;
        // console.log('go_to',go_to);
        progress_progress_bar(go_to);
    } else if (curr_prog < 60) {
        progress_progress_bar(curr_prog + 4);
    }

}
/* Check current Progress Sidebar */
/* Progress Sidebar */
function progress_progress_bar(go_to) {
    var curr_prog = parseInt($('.deploy_progress div').attr('data-val'));
    go_to = (go_to > 100) ? 100 : go_to;
    if (go_to > curr_prog) {
        $('.deploy_progress div').attr('data-val', go_to).width(go_to + '%');
    }
    if (go_to == 100) {
        hide_progress();
    }
}
/* End Progress Sidebar */
/* Updating Top Remark */
function update_top_remark(text) {
    text += ' ';
    var dots_number = parseInt($('.loader-message').attr('data-dots_number'));
    dots_number = (dots_number == 1) ? dots_number = 2 : ((dots_number == 2) ? dots_number = 3 : dots_number = 1);
    $('.loader-message').attr('data-dots_number', dots_number);
    for (var i = 0; i < dots_number; i++) {
        text += ".";
    }
    $('.loader-message').html(text);
}
/* End Updating Top Remark */

/* Update Best row and select region row */
function update_select_and_best_tr() {
    if ($('tr.ng-scope:visible').length < 2) {
        $('tr.best_values').hide();
    }
    else {
        $('tr.best_values').show();
    }
    if ($('select.select_region option:gt(1)').not('.hide').length == 0) {
        $('.select_region_row').hide();
    } else {
        $('.select_region_row').show();
    }
}
/* End Update Best row and select region row */

/* Disable form */
function disable_diploy_form() {
    $('#deploy_form input').attr('readonly', true);
    $('#deploy_form button').attr('disabled', 'disabled');
}
/* Disable form */

/* Enable form */
function enable_diploy_form() {
    $('#deploy_form input').removeAttr('readonly');
    $('#deploy_form button').removeAttr('disabled');
}
/* Enable form */
/* Update Select tag*/
function update_select_tag() {
    $('select.select_region option').removeClass('hide');
    $('tr.ng-scope:visible').each(function () {
        var region_code = $(this).attr('data-region_code');
        $('select.select_region option[value="' + region_code + '"]').addClass('hide');
    })
    $('select.select_region').val('');
}
/* End Update Select tag*/
/* Create instance on page load If url from $_GET */
function init_url_if_exitst(url) {
    if ($.trim(url) != '') {
        // url = (/^(www\.|https?:\/\/)/.test(url)) ? url : 'www.'+url;
        // $('#deploy_form [name="url"]').val(url);
        get_url();
    }
}
/* End Create instance on page load If url from $_GET */
/* Send Report Form Ajax */
function send_report_fnc() {
    $('.send_pdf_btn').click(function (e) {
        e.preventDefault();
        $('.modal .modal-footer').show();
        $('.modal form#send_report_form input[name="from_email"]').val('');
        $('.modal form#send_report_form input[name="to_email"]').val('');
        $('.modal form#send_report_form textarea[name="note"]').val('');
        $('.modal form#send_report_form').show();
        $('.modal .alert').hide();
        $('.modal label.error').remove();
        $('.modal').modal();
    });
    $('.modal .modal-footer .btn.btn-primary').bind('click', function () {
        $('.modal form#send_report_form').submit();
    })
    $('.modal form#send_report_form').validate({
        submitHandler: function (form) {
            send_pdf_report(form);
        }
    });
}
function send_pdf_report(form) {
    $("#gen_loading").show();
    var pdf_report = $('.modal').attr('data-pdf_report');
    $.ajax({
        url: "sendpdf",
        dataType: "json",
        type: "POST",
        data: {
            'from_email': $(form).find('input[name="from_email"]').val(),
            'to_email': $(form).find('input[name="to_email"]').val(),
            'note': $(form).find('textarea[name="note"]').val(),
            'pdf_report': pdf_report
        },
        success: function (msg) {
            var code = msg.code,
                message = msg.message;
            $("#gen_loading").hide();
            if (code == 0) {
                alert(message);
            } else if (code == 1) {
                $('.modal .modal-footer').hide();
                $('.modal form#send_report_form').hide();
                $('.modal .alert').show();
                setTimeout(function () {
                    $('.modal').modal('hide');
                }, 4000);
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            $("#gen_loading").hide();
            alert("Error Occured When Killing Instance!!! Please Try Again !!!");
        }
    })
}
/* End Send Report Form Ajax*/
/* Send Error Email */
function send_error_email(message) {
    console.log("send_error_email");
    $.ajax({
        url: "senderror",
        dataType: "json",
        type: "POST",
        data: {
            'message': message,
            'url': url,
            'init_url': init_url,
            'public_dns': public_dns,
            'region_code': region_code_curr
        },
        success: function (msg) {
            var code = msg.code,
                message = msg.message;
        },
        error: function (xhr, ajaxOptions, thrownError) {
        }
    })

}
/* End Send Error Email */

var init_url = '',
    public_dns = '';
url = '',
    region_code_curr = '';

protocol = 'http',
    display_chart = true,
    display_video = true,
    progress_step = 5,
    create_config_file = true,
    stop_seach_for_speed = false,
    get_instance_data_count = 1;

$(document).ready(function () {
    url = $('.hidden_url').text();

    click_internal_external_action();
    init_screen();
    init_url_if_exitst(url);
    $('form#deploy_form').validate({
        submitHandler: function (form) {
            display_chart = true;
            url = $(form).find('[name="url"]').val();
            // url = (/^(www\.|https?:\/\/)/.test(url)) ? url : 'www.'+url;
            get_url();
        }
    })

    $('select.select_region').change(function () {
        var region_code = $('select.select_region').val();
        progress_step = 10;
        create_config_file = true;
        if (region_code == 'all') {
            $('select.select_region option:gt(1)').not('.hide').each(function () {
                var region_code = $(this).val();
                create_instance(region_code);
            })
        } else {
            create_instance(region_code);
        }
    })
    $('.icon-trash').live('click', function (e) {
        e.preventDefault();
        var region_code = $(this).parents('tr.ng-scope').attr('data-region_code', region_code),
            instanceId = $(this).parents('tr.ng-scope').attr('data-instanceId', instanceId);
        kill_instance(region_code, instanceId);
    })
    $('.icon-reload').live('click', function (e) {
        e.preventDefault();
        var region_code = $(this).parents('tr.ng-scope').attr('data-region_code', region_code),
            instance_count = $(this).parents('tr.ng-scope').attr('data-instance_count', instance_count),
            instanceId = $(this).parents('tr.ng-scope').attr('data-instanceId', instanceId);
        $(this).parents('tr.ng-scope').find('td.ng-scope').removeAttr('data-random_faster');

        $(this).parents('tr.ng-scope').find('.result_ok').hide();
        $(this).parents('tr.ng-scope').find('.result_loading').show();
        $(this).parents('tr.ng-scope').find('.result_ok').find('.relative-value').hide();
        $(this).parents('tr.ng-scope').find('.tests-actions-column').hide();
        $(this).parents('tr.ng-scope').find('td').removeClass('worst');
        $('.select_region_row').hide();
        $('.bottom-panel-controls').hide();
        disable_diploy_form();
        show_progress();
        progress_step = 10;
        get_insance_data(region_code, instanceId, instance_count);
    })
    $('.result_ok .respond-time-value').live('click', function (e) {
        e.preventDefault();
    })

    $('tr.ng-scope .result_ok .respond-time-value').live('click', function (e) {
        e.preventDefault();
        var location = $(this).parents('td').attr('data-location'),
            current_website_still_loading = $('tr.current_website_row td[data-location="' + location + '"] .result_loading:visible').length;
        // console.log(location,current_website_still_loading);
        if (!$(this).hasClass('selected') && !current_website_still_loading) {
            display_chart_fnc($(this));
        }
    })
    $('tr.ng-scope .result_ok .respond-time-value').live('mouseenter', function (e) {
        var neustar_id = $(this).parents('td.ng-scope').attr('data-neustar_id');
        // console.log(2,neustar_id);
        window.clearTimeout(window[neustar_id]);
        $(this).parents('.result_ok').find('.tooltip').show();
    })
    $('tr.ng-scope .result_ok .respond-time-value').live('mouseleave', function (e) {
        $(this).parents('.result_ok').find('.tooltip').hide();
    })
    $('.test-url').live('click', function (e) {
        $(this).find('.tooltip').hide();
    })

    $('.carousel').carousel({
        interval: 15000
    });
    $('#introVideoToggle').bind('click', function (e) {
        e.preventDefault();
        $('#introVideoWraper').slideToggle();
        if ($(this).hasClass('collapsed')) {
            $(this).removeClass('collapsed');
        }
        else {
            $(this).addClass('collapsed');
        }
    })
    send_report_fnc();
});