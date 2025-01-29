let dollarUSLocale = Intl.NumberFormat('en-US');

function formatToCurrency(number, decPlaces = 2, decSep = '.', thouSep = ',') {

    var value = (number).toLocaleString(
        undefined, // leave undefined to use the visitor's browser
        // locale or a string like 'en-US' to override it.
        { minimumFractionDigits: 2 }
    );

    return value;
}

var App = function() {
    var MediaSize = {
        xl: 1200,
        lg: 992,
        md: 991,
        sm: 576
    };
    var ToggleClasses = {
        headerhamburger: '.toggle-sidebar',
        inputFocused: 'input-focused',
    };

    var Selector = {
        mainHeader: '.header.navbar',
        headerhamburger: '.toggle-sidebar',
        fixed: '.fixed-top',
        mainContainer: '.main-container',
        sidebar: '#sidebar',
        sidebarContent: '#sidebar-content',
        sidebarStickyContent: '.sticky-sidebar-content',
        ariaExpandedTrue: '#sidebar [aria-expanded="true"]',
        ariaExpandedFalse: '#sidebar [aria-expanded="false"]',
        contentWrapper: '#content',
        contentWrapperContent: '.container',
        mainContentArea: '.main-content',
        searchFull: '.toggle-search',
        overlay: {
            sidebar: '.overlay',
            cs: '.cs-overlay',
            search: '.search-overlay'
        }
    };

    var categoryScroll = {
        scrollCat: function() {
            var sidebarWrapper = document.querySelectorAll('.sidebar-wrapper [aria-expanded="true"]')[0];
            var sidebarWrapperTop = sidebarWrapper.offsetTop - 20;
            setTimeout(function() { $('.menu-categories').animate({ scrollTop: sidebarWrapperTop }, 500); }, 500);
        }
    }

    var toggleFunction = {
        sidebar: function($recentSubmenu) {
            $('.sidebarCollapse').on('click', function(sidebar) {
                sidebar.preventDefault();
                getSidebar = $('.sidebar-wrapper');
                console.log('drill 1')
                if ($recentSubmenu === true) {
                    console.log('drill 2')
                    if ($('.collapse.submenu').hasClass('show')) {
                        console.log('drill 3')
                        $('.submenu.show').addClass('mini-recent-submenu');
                        getSidebar.find('.collapse.submenu').removeClass('show');
                        getSidebar.find('.collapse.submenu').removeClass('show');
                        $('.collapse.submenu').parents('li.menu').find('.dropdown-toggle').attr('aria-expanded', 'false');
                    } else {
                        console.log('drill 4')
                        if ($(Selector.mainContainer).hasClass('sidebar-closed')) {
                            console.log('drill 5')
                            if ($('.collapse.submenu').hasClass('recent-submenu')) {
                                getSidebar.find('.collapse.submenu.recent-submenu').addClass('show');
                                $('.collapse.submenu.recent-submenu').parents('.menu').find('.dropdown-toggle').attr('aria-expanded', 'true');
                                $('.submenu').removeClass('mini-recent-submenu');
                                console.log('drill 6')

                            } else {
                                $('li.active .submenu').addClass('recent-submenu');
                                getSidebar.find('.collapse.submenu.recent-submenu').addClass('show');
                                $('.collapse.submenu.recent-submenu').parents('.menu').find('.dropdown-toggle').attr('aria-expanded', 'true');
                                $('.submenu').removeClass('mini-recent-submenu');
                                console.log('drill 7')
                            }
                        }
                    }
                    console.log('drill 2 end')
                }
                console.log('end drill')
                $(Selector.mainContainer).toggleClass("sidebar-closed");
                $(Selector.mainHeader).toggleClass('expand-header');
                $(Selector.mainContainer).toggleClass("sbar-open");
                $('.overlay').toggleClass('show');
                $('html,body').toggleClass('sidebar-noneoverflow');
            });
        },
        onToggleSidebarSubmenu: function() {
            $('.sidebar-wrapper').on('mouseenter mouseleave', function(event) {
                event.preventDefault();
                if ($('body').hasClass('alt-menu')) {
                    if ($('.main-container').hasClass('sidebar-closed')) {
                        if (event.type === 'mouseenter') {
                            $('li .submenu').removeClass('show');
                            $('li.active .submenu').addClass('recent-submenu');
                            $('li.active').find('.collapse.submenu.recent-submenu').addClass('show');
                            $('.collapse.submenu.recent-submenu').parents('.menu').find('.dropdown-toggle').attr('aria-expanded', 'true');
                        } else if (event.type === 'mouseleave') {
                            $('li').find('.collapse.submenu').removeClass('show');
                            $('.collapse.submenu.recent-submenu').parents('.menu').find('.dropdown-toggle').attr('aria-expanded', 'false');
                            $('.collapse.submenu').parents('.menu').find('.dropdown-toggle').attr('aria-expanded', 'false');
                        }
                    }
                } else {
                    if ($('.main-container').hasClass('sidebar-closed')) {
                        if (event.type === 'mouseenter') {
                            $(this).find('.submenu.recent-submenu').addClass('show');
                            $('.collapse.submenu.recent-submenu').parents('.menu').find('.dropdown-toggle').attr('aria-expanded', 'true');
                        } else if (event.type === 'mouseleave') {
                            $(this).find('.submenu.recent-submenu').removeClass('show');
                            $('.collapse.submenu.recent-submenu').parents('.menu').find('.dropdown-toggle').attr('aria-expanded', 'false');
                        }
                    }

                }
            })
        },
        offToggleSidebarSubmenu: function() {
            $('.sidebar-wrapper').off('mouseenter mouseleave');
        },
        overlay: function() {
            $('#dismiss, .overlay, cs-overlay').on('click', function() {
                // hide sidebar
                $(Selector.mainContainer).addClass('sidebar-closed');
                $(Selector.mainContainer).removeClass('sbar-open');
                // hide overlay
                $('.overlay').removeClass('show');
                $('html,body').removeClass('sidebar-noneoverflow');
            });
        },
        search: function() {
            $(Selector.searchFull).click(function(event) {
                $(this).parents('.search-animated').find('.search-full').addClass(ToggleClasses.inputFocused);
                $(this).parents('.search-animated').addClass('show-search');
                $(Selector.overlay.search).addClass('show');
                $(Selector.overlay.search).addClass('show');
            });

            $(Selector.overlay.search).click(function(event) {
                $(this).removeClass('show');
                $(Selector.searchFull).parents('.search-animated').find('.search-full').removeClass(ToggleClasses.inputFocused);
                $(Selector.searchFull).parents('.search-animated').removeClass('show-search');
            });
        }
    }

    var inBuiltfunctionality = {
        mainCatActivateScroll: function() {
            const ps = new PerfectScrollbar('.menu-categories', {
                wheelSpeed: .5,
                swipeEasing: !0,
                minScrollbarLength: 40,
                maxScrollbarLength: 300
            });
        },
        preventScrollBody: function() {
            $('#sidebar').bind('mousewheel DOMMouseScroll', function(e) {
                var scrollTo = null;

                if (e.type == 'mousewheel') {
                    scrollTo = (e.originalEvent.wheelDelta * -1);
                } else if (e.type == 'DOMMouseScroll') {
                    scrollTo = 40 * e.originalEvent.detail;
                }

                if (scrollTo) {
                    e.preventDefault();
                    $(this).scrollTop(scrollTo + $(this).scrollTop());
                }
            });
        },
        languageDropdown: function() {
            var getDropdownElement = document.querySelectorAll('.more-dropdown.language-dropdown .dropdown-item');
            for (var i = 0; i < getDropdownElement.length; i++) {
                getDropdownElement[i].addEventListener('click', function() {
                    document.querySelectorAll('.more-dropdown.language-dropdown .dropdown-toggle > span')[0].innerText = this.getAttribute('data-value');
                    document.querySelectorAll('.more-dropdown .dropdown-toggle > img')[0].setAttribute('src', 'assets/img/' + this.getAttribute('data-img-value') + '.png');
                })
            }
        },
        appsDropdown: function() {
            var getDropdownElement = document.querySelectorAll('.more-dropdown.apps-dropdown .dropdown-item');
            for (var i = 0; i < getDropdownElement.length; i++) {
                getDropdownElement[i].addEventListener('click', function() {
                    document.querySelectorAll('.more-dropdown.apps-dropdown .dropdown-toggle > span')[0].innerText = this.getAttribute('data-value');
                })
            }
        }
    }

    var _mobileResolution = {
        onRefresh: function() {
            var windowWidth = window.innerWidth;
            if (windowWidth <= MediaSize.md) {
                categoryScroll.scrollCat();
                toggleFunction.sidebar();
            }
        },

        onResize: function() {
            $(window).on('resize', function(event) {
                event.preventDefault();
                var windowWidth = window.innerWidth;
                if (windowWidth <= MediaSize.md) {
                    toggleFunction.offToggleSidebarSubmenu();
                }
            });
        }

    }

    var _desktopResolution = {
        onRefresh: function() {
            var windowWidth = window.innerWidth;
            if (windowWidth > MediaSize.md) {
                categoryScroll.scrollCat();
                toggleFunction.sidebar(true);
                toggleFunction.onToggleSidebarSubmenu();
            }
        },

        onResize: function() {
            $(window).on('resize', function(event) {
                event.preventDefault();
                var windowWidth = window.innerWidth;
                if (windowWidth > MediaSize.md) {
                    toggleFunction.onToggleSidebarSubmenu();
                }
            });
        }

    }

    function sidebarFunctionality() {
        function sidebarCloser() {

            if (window.innerWidth <= 991) {


                if (!$('body').hasClass('alt-menu')) {

                    $("#container").addClass("sidebar-closed");
                    $('.overlay').removeClass('show');
                } else {
                    $(".navbar").removeClass("expand-header");
                    $('.overlay').removeClass('show');
                    $('#container').removeClass('sbar-open');
                    $('html, body').removeClass('sidebar-noneoverflow');
                }

            } else if (window.innerWidth > 991) {

                if (!$('body').hasClass('alt-menu')) {

                    $("#container").removeClass("sidebar-closed");
                    $(".navbar").removeClass("expand-header");
                    $('.overlay').removeClass('show');
                    $('#container').removeClass('sbar-open');
                    $('html, body').removeClass('sidebar-noneoverflow');
                } else {
                    $('html, body').addClass('sidebar-noneoverflow');
                    $("#container").addClass("sidebar-closed");
                    $(".navbar").addClass("expand-header");
                    $('.overlay').addClass('show');
                    $('#container').addClass('sbar-open');
                    $('.sidebar-wrapper [aria-expanded="true"]').parents('li.menu').find('.collapse').removeClass('show');
                }
            }

        }

        function sidebarMobCheck() {
            if (window.innerWidth <= 991) {

                if ($('.main-container').hasClass('sbar-open')) {
                    return;
                } else {
                    sidebarCloser()
                }
            } else if (window.innerWidth > 991) {
                sidebarCloser();
            }
        }

        sidebarCloser();

        $(window).resize(function(event) {
            sidebarMobCheck();
        });

    }

    return {
        init: function() {
            toggleFunction.overlay();
            toggleFunction.search();
            /*
                Desktop Resoltion fn
            */
            _desktopResolution.onRefresh();
            _desktopResolution.onResize();

            /*
                Mobile Resoltion fn
            */
            _mobileResolution.onRefresh();
            _mobileResolution.onResize();

            sidebarFunctionality();

            /*
                In Built Functionality fn
            */
            inBuiltfunctionality.mainCatActivateScroll();
            inBuiltfunctionality.preventScrollBody();
            inBuiltfunctionality.languageDropdown();
            inBuiltfunctionality.appsDropdown();
        }
    }

}();



Number.prototype.format = function(n, x) {
    var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
    return this.toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$&,');
}

function ajax_operation(send_method, pageUrl, data_info, btn = null, txt = null, redirectlink = null) {
    if (btn != null) {
        disableBtn(btn)
        startLoadingButton(btn)
    }
    $('.responseMessage').html()
    $.ajax({
        url: pageUrl,
        type: send_method,
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $('.toast').removeClass("bg-success");
            $('.toast').removeClass("bg-danger");
            $(".toast-msg").html(data.message);
            var status;
            if (data.status == 1) {
                data.status = 'bg-success'
                status = 'success';
                $complete = 'Success';
                swal(
                    $complete,
                    data.message,
                    status
                )
                if (data.redirectlink) {
                    if (typeof data.redirectlink !== 'undefined' && data.redirectlink.length > 5) {
                        window.location.href = data.redirectlink;
                    }
                }
            } else if (data.status == 0) {
                data.status = 'bg-danger';
                status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    status
                )
                $('.responseMessage').html('<div class="alert alert-danger">' + data.message + '</div>');
            }
            $('.toast').addClass(data.status);
            $('.token').val(data.token);
            if (btn != null) {
                stopLoadingButton(btn, txt);
                enableBtn(btn);
            }
            return data;

        },
        error: function(jqXHR, status, error) {


            $('.toast').addClass("bg-danger");
            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connected\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else {
                msg = 'Uncaught Error.\n' + error;
            }

            $('.toast-msg').html(msg);
        },
        xhr: function() {
            var xhr = $.ajaxSettings.xhr();
            xhr.onprogress = function e() {
                // For downloads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                    $(".toast-msg").html(e.loaded / e.total);
                }
            };
            xhr.upload.onprogress = function(e) {
                // For uploads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                }
            };
            return xhr;
        }
    }).done(function() {
        $('.toast').addClass("bg-success");
    });
    $(".toast").toast({
        delay: 5000
    });
    $('.toast').toast('show');

    //return retunStatus;
}

function ajax_fetch(send_method, pageUrl, data_info) {
    $.ajax({
        url: pageUrl,
        type: send_method,
        data: data_info,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {

            $(".render_page").html(data);
        },
        error: function(jqXHR, status, error) {


            $('.render_page').addClass("bg-danger");
            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connected\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else {
                msg = 'Uncaught Error.\n' + error;
            }

            $('.render_page').html(msg);
        },
        xhr: function() {
            var xhr = $.ajaxSettings.xhr();
            xhr.onprogress = function e() {
                // For downloads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                    $(".render_page").html(e.loaded / e.total);
                }
            };
            xhr.upload.onprogress = function(e) {
                // For uploads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                }
            };
            return xhr;
        }
    }).done(function() {
        $('.render_page').removeClass("bg-danger");
    });

}

function ajax_fetch_tabledata(send_method, pageUrl, data_info) {
    $.ajax({
        url: pageUrl,
        type: send_method,
        data: data_info,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(da_ta) {
            $data_page = da_ta.pagetable;
            $(".render_page").html('');
            $data_page.forEach(function(datas) {
                $(".render_page").append(datas);
            });
        },
        error: function(jqXHR, status, error) {
            $('.render_page').addClass("bg-danger");
            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connected\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else {
                msg = 'Uncaught Error.\n' + error;
            }
            $('.render_page').html(msg);
        },
        xhr: function() {
            var xhr = $.ajaxSettings.xhr();
            xhr.onprogress = function e() {
                // For downloads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                    $(".render_page").html(e.loaded / e.total);
                }
            };
            xhr.upload.onprogress = function(e) {
                // For uploads
                if (e.lengthComputable) {
                    console.log(e.loaded / e.total);
                }
            };
            return xhr;
        }
    }).done(function() {
        // $('.render_page').addClass( "bg-success" );
    });

}

function formatMoney(mynumber) {
    var value = (+mynumber).toLocaleString(
        undefined, // leave undefined to use the visitor's browser
        // locale or a string like 'en-US' to override it.
        { minimumFractionDigits: 2 }
    );

    console.log(value);
    return value;
}

function ViewCustomerStatus($searchval) {

    $.ajax({
        url: '/user-restriction-status/' + $searchval,
        type: 'post',

        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $("#customer_RestricTpermission").empty();
            $("#customer_RestricTsavings_product").empty();
            $("#customer_RestricTremark").empty();
            $main = data.main;
            $permission = data.permission;

            console.log($main);
            $main.forEach(function(account) {
                $("#customer_RestricTName").html(account.first_name + ' ' + account.last_name);
                $("#customer_RestricTphone").html(account.phone);
                if (account.profile_pic) {
                    $("#customer_RestricTprofile_pic").attr('src', window.location.origin + '/' + account.profile_pic.replace('public', 'storage'));
                }
                if (account.signature) {
                    $("#customer_RestricTsignature").attr('src', window.location.origin + '/' + account.signature.replace('public', 'storage'));
                }

                $("#customer_RestricTusername").html(account.username);
                $("#customer_RestricTemail").html(account.email);
                $("#customer_RestricTaddress").html(account.address);

                $("#customer_RestricTsavings_product").append('<hr><h6>' + account.virtual_accnum + '-' + account.savings_product + '</h6> <br>Balance: ' + account.balance + '<br> Max-Deposit: ' + account.maximum_deposit + '<br> Min-Withdraw:' + account.minimum_withdraw);



                $("#customer_RestricTcreatedBy").html(account.created_by);
                $("#customer_RestricTcreatedat").html(account.created_at);
            });
            $("#customer_RestricTsavings_product").append('<hr>');
            $("#customer_RestricTpermission").html("<h6>Restriction</h6>");
            $permission.forEach(function(account) {
                // console.log(account);

                $("#customer_RestricTpermission").append(
                    '<p>' + account.permission + ' <br>' + account.remark + '</p>'

                );

            });
        }
    });
}

//account password reset
function userreset(uuid) {
    $.ajax({
        url: '/company/reset_psw/' + uuid,
        type: 'post',

        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            if (data.status == 1) {

                $status = 'success';
                $complete = 'Done!';
                swal(
                    $complete,
                    data.message,
                    $status
                )
            } else {
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                )
            }

        }
    });
}

function viewdata($url, $method, paginateYa = true) {

    $('#html5-extension').DataTable({
        "responsive": true,
        "processing": true,
        "serverSide": true,
        "ajax": {
            url: $url,
            method: $method
        },
        "columnDefs": [{
            'targets': [1],
            'orderable': false
        }],

        dom: '<"row"<"col-md-12"<"row"<"col-md-6"B><"col-md-6"f> > ><"col-md-12"rt> <"col-md-12"<"row"<"col-md-5"i><"col-md-7"p>>> >',
        buttons: {
            buttons: [{
                extend: 'copy',
                className: 'btn btn-primary'
            }, {
                extend: 'csv',
                className: 'btn btn-primary'
            }, {
                extend: 'excel',
                className: 'btn btn-primary'
            }, {
                extend: 'print',
                className: 'btn btn-primary'
            }]
        },
        "oLanguage": {
            "oPaginate": paginateYa ? {
                "sPrevious": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
                "sNext": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>'
            } : {},
            "sInfo": "Showing page _PAGE_ of _PAGES_",
            "sSearch": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
            "sSearchPlaceholder": "Search...",
            "sLengthMenu": "Results :  _MENU_",
        },
        "stripeClasses": [],
        "lengthMenu": paginateYa ? [7, 10, 20, 50] : [],
        "pageLength": paginateYa ? 10 : 100000
    });
}


function fetchinvestmentproduct() {
    $('select[name="investmentproduct"]').empty();
    $.ajax({
        url: '/company/investmentDataProduct/',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            data.forEach(function(product) {
                $('select[name="investmentproduct"]').append('<option value="' + product.id + '">' + product.product + ' - ' + product.interest_rate + '%</option>')
            });
        }
    });
}


function startLoadingButton(buttonId) {
    $('#' + buttonId).prop("disabled", true);
    $('#' + buttonId).html('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin mr-2"> <line x1="12" y1="2" x2="12" y2="6"></line> <line x1="12" y1="18" x2="12" y2="22"></line> <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line> <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line> <line x1="2" y1="12" x2="6" y2="12"></line> <line x1="18" y1="12" x2="22" y2="12"></line> <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line> <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Please wait...');
}

function disableBtn(buttonId) {
    $('#' + buttonId).prop("disabled", true);
}

function enableBtn(buttonId) {
    $('#' + buttonId).prop("disabled", false);
}

function stopLoadingButton(buttonId, text) {
    $('#' + buttonId).prop("disabled", false);
    $('#' + buttonId).html(' ' + text + ' ');
}

function loadDefault() {
    $('#userImage').attr('src', '/assets/images/default_image.jpg');
}

function loadDefault2() {
    $('#userImage').attr('src', '/assets/images/default_image.jpg');
}

function loadDefaultLogo() {
    $('#companyLogo').attr('src', '/assets/images/default_logo.png');
}

function loadDefaultLogo2() {
    $('#companyLogo').attr('src', '/assets/images/default_logo.png');
}

function fetchproduct($searchval) {



    $('select[name="account_type"]').empty();

    $.ajax({
        url: '/userDataProduct/' + $searchval,
        type: 'GET',
        dataType: 'json',
        success: function(data) {

            data.forEach(function(account) {


                $('select[name="account_type"]').append('<option value="' + account.virtual_accnum + '">' + account.virtual_accnum + ' - ' + formatToCurrency(account.user_wallet.balance) + ' - ' + account.account_type.savings_product + '</option>')

            });
            fetchproductinfo($('select[name="account_type"]').val());
        }
    });
    SMSstatus($searchval);

}
$('select[name="account_type"]').change(function() {
    fetchproductinfo($('select[name="account_type"]').val());
});

function fetchproductinfo($vacc) {
    $('#vaccinfo').empty();

    $('#vaccinfo').removeAttr("class");


    $.ajax({
        url: "/wbaccountdetails/" + $vacc,
        type: 'post',
        data: {},
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $('#vaccinfo').attr("class", "alert ");
            $('#vaccinfo').append("<ul style='list-style-type: none;'>");
            $('#vaccinfo').append("<li>name: " + data.name + "</li>");
            $('#vaccinfo').append("<li>account: " + data.account + "</li>");
            $('#vaccinfo').append("<li>Account Type: <span class='badge badge-warning'>" + data.product + "</span></li>");
            $('#vaccinfo').append("<li>Balance: <span class='badge badge-success'>" + data.balance + "</span></li>");
            $('#vaccinfo').append("</ul>");
        }
    });
}

function updateSMSWhatsapp(indcators) {
    $searchval = $('#uuid').val();
    $url = '';
    if (indcators == 'sms') {
        $url = '/userDataSMS/' + $searchval;
    } else {
        $url = '/userDataWhatsApp/' + $searchval;
    }
    $.ajax({
        url: $url,
        type: 'post',
        data: {},
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            SMSstatus($searchval);
        }
    });
}
$('#whatsappcheck').change(function() {
    updateSMSWhatsapp('whatsapp');
});
$('#smscheck').change(function() {
    updateSMSWhatsapp('sms');
});

function SMSstatus($searchval) {


    $.ajax({
        url: '/GetUserDataSMS/' + $searchval,
        type: 'post',
        data: {},
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            if (data.status == 1) {
                $('#whatsappcheck').prop('checked', data.whatsapp);
                $('#smscheck').prop('checked', data.sms);
            }

            // $('input[value="' + item.permission_id + '"]:checkbox').prop('checked', true);

        }
    });
}

function moneyFormat(newRate) {

    return newRate.format(0)
}

function calculateinvestment() {
    $plan = $('#investmentproduct').val();
    $amount = $('#amount').val();
    $created_at = $('#created_at').val();
    $.ajax({
        url: '/company/investment-calculation/',
        type: 'GET',
        data: { plan: $plan, amount: $amount, created_at: $created_at },
        dataType: 'json',
        success: function(data) {

            $('.planname').html(data.product);
            $('.termination').html('%' + data.termination);
            $('.max').html(data.max);
            $('.min').html(data.min);
            $('.start').html(data.start);
            $('.end').html(data.end);
            $('.totalcash').html(data.totalcash);
            $('.interest').html('%' + data.interest);
            $('.plan_interest').html('%' + data.plan_interest);
            $('.plantype').html(data.plantype);
            $('.duration').html(data.duration);
            $('.percentvalue').html(data.percentvalue);


            $('#percentvalue').css("width", data.percentvalue + "%");

            $('#percentvalue').removeAttr("aria-valuenow");
            $('#percentvalue').attr("aria-valuenow", data.percentvalue);

            $('#amount').removeAttr("min");
            $('#amount').attr("min", data.min);
            $('#amount').removeAttr("max");
            $('#amount').attr("max", data.max);

        }
    });
}

function isNumberKey(evt) {
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode != 46 && charCode > 31 &&
        (charCode < 48 || charCode > 57))
        return false;

    return true;
}

function get_user_profile_details(user_id) {
    $.ajax({
        type: 'get',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: '/company/customer/get_customer_profile/' + user_id,

        cache: false,
        contentType: false,
        processData: false,
        success: (data) => {
            $('#exampleModal').modal('show');
            $('#view_profile').html(data);
        }
    });
}

function userpswreset(uuid) {
    disableBtn('srpassword')
    startLoadingButton('srpassword')
    $.ajax({
        url: '/company/customer/customer-psw-reset/' + uuid,
        type: 'post',

        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            if (data.status == 1) {

                $status = 'success';
                $complete = 'Done!';
                swal(
                    $complete,
                    data.message,
                    $status
                )
            } else {
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                )
            }
            stopLoadingButton('srpassword', 'Send Reset Password');
            enableBtn('srpassword');
        }
    });
}

function userpinreset(uuid) {
    disableBtn('srpin')
    startLoadingButton('srpin')
    $.ajax({
        url: '/company/customer/customer-pin-reset/' + uuid,
        type: 'post',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            if (data.status == 1) {
                $status = 'success';
                $complete = 'Done!';
                swal(
                    $complete,
                    data.message,
                    $status
                )
            } else {
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                )
            }
            stopLoadingButton('srpin', 'Send Reset Pin');
            enableBtn('srpin');
        }
    });
}


function userresetEmail(uuid) {
    disableBtn('verify-email')
    startLoadingButton('verify-email')
    $.ajax({
        url: '/company/send-emailverification/' + uuid,
        type: 'post',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            if (data.status == 1) {

                $status = 'success';
                $complete = 'Done!';
                swal(
                    $complete,
                    data.message,
                    $status
                )
            } else {
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                )
            }
            stopLoadingButton('verify-email', 'Verify Email');
            enableBtn('verify-email');
        }
    });
}

$(document).ready(function() {
    $("#checkedAll").change(function() {
        if (this.checked) {
            $(".checkSingle").each(function() {
                this.checked = true;
            });
        } else {
            $(".checkSingle").each(function() {
                this.checked = false;
            });
        }
    });

    $(".checkSingle").click(function() {
        if ($(this).is(":checked")) {
            var isAllChecked = 0;

            $(".checkSingle").each(function() {
                if (!this.checked)
                    isAllChecked = 1;
            });

            if (isAllChecked == 0) {
                $("#checkedAll").prop("checked", true);
            }
        } else {
            $("#checkedAll").prop("checked", false);
        }
    });
});

function get_vaults() {
    $.ajax({
        url: '/company/vault/view-vault',
        type: 'post',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $('.vault-card').html(data);
        }
    });
}

function formatToCurrencyAll(number, decPlaces = 2, decSep = '.', thouSep = ',') {
    return dollarUSLocale.format(number);
}


function viewLoan(uuid) {
    $('#loanID').val(uuid);
    $.ajax({
        url: '/company/loan/view-Loan-Details-data/' + uuid,
        type: 'post',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(data) {
            $('#remark').html(data.remark);
            $('#borrower').html('Borrower: ' + data.user_id);
            $('#principal').html('Principal: ' + data.principal);
            $('#loan_balance').html('Loan Balance: ' + data.loanBalance);
            $('#borrowerAccount').html(data.borrowerAccount);
            $('#Loantype').html((data.Loantype == 1) ? 'Loan Type: ' + data.Loantype : 'Loan Type: ' + data.Loantype);
            $('#LoanBranch_id').html('Branch: ' + data.LoanBranch_id);
            $('#LoanOfficer_id').html('Officer: ' + data.LoanOfficer_id);
            $('#remark').html(data.remark);
            $('#duration').html('Duration: ' + data.duration + '/' + data.DurationPeriod);
            $('#loan_product_id').html('Product: ' + data.loan_product_id);
            $('#rate').html('Rate: ' + data.rate + '%');
            $('.sessions').html('<li>Author: ' + data.created_by + '</li><li> Editor: ' + data.modified_by + '</li>');
            $('#repaymentcycle').html(data.period_id);
            $('#start_at').html('Start Date: ' + data.start_at);
            $('#grace_at').html('Grace Date: ' + data.grace_at);
            $('#LoanFeeMethod').html(data.LoanFeeMethod + '/' + data.LoanFee + '%');
            $('#mrgtFeeMethod').html(data.mrgtFeeMethod + '/' + data.mrgtFee + '%');
            $('#auto_pay').html(data.auto_pay);
            $('#on_guarantor').html(data.on_guarantor);
            $('#keep_as_past_onMissed_onExtend').html(data.keep_as_past_onMissed_onExtend);
            $('#ExtendDuration').html(data.ExtendDuration);
            $('#missedFee').html(data.missedFee + '% Missed Fee');
            $('.sessions').html("<strong><p>Created By:" + data.created_by + "</p><p>Modified By: " + data.modified_by + "</p></strong>");
            $('.guarantorCard').empty();
            $guarantor = data.pickguarantor;
            $guarantor.forEach(function(guarantor) {
                console.log(guarantor);
                $avatar = guarantor.passport;
                $signature = guarantor.signature;
                $avatar = ($avatar) ? $avatar.replace('public', 'storage') : null;
                $signature = ($signature) ? $signature.replace('public', 'storage') : null;
                $avtarURL = ($avatar) ? window.location.origin + '/' + $avatar : 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png';
                $signatureURL = ($avatar) ? window.location.origin + '/' + $signature : 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png';
                $('.guarantorCard').append('\
                <div class="col-md-4 col-sm-6">\
                <div class="card" style="width: 10rem;">\
                <img class="card-img-top" src="' + $avtarURL + '" alt="' + guarantor.first_name + '">\
                <div class="card-body"><h5 class="card-title">' + guarantor.owner + '</h5>\
                <p class="card-text">' + guarantor.first_name + ' ' + guarantor.last_name + '</p>\
                <p class="card-text">Salary: ' + guarantor.salary + '</p>\
                <p class="card-text">Relationship: ' + guarantor.relationship + '</p>\
                <p class="card-text">Phone: ' + guarantor.phone + '</p>\
                <p class="card-text">occupation: ' + guarantor.occupation + '</p>\
                <p class="card-text">BVN: ' + guarantor.bvn + '</p>\
                <p class="card-text">Remark: ' + guarantor.other_remarks + '</p>\
                <p class="card-text">Address: ' + guarantor.address + '</p>\
                <a target="_blank" href="' + $signature + '" class="btn btn-warning"> Signature</a>\
                </ul></p>\
                </div></div></div>');
            });
            $('#viewdetails').show();
            $('#rollOver').hide();
            $('#exampleModal').modal('show');
            // GeneraLoanRepayment(uuid);
        }
    });
}

function onlyNumberKey(evt) {
    // Only ASCII character in that range allowed
    var ASCIICode = (evt.which) ? evt.which : evt.keyCode
    if (ASCIICode > 31 && (ASCIICode < 48 || ASCIICode > 57))
        return false;
    return true;
}

function verifyOTPfactor() {

    let otp = $('#otp').val();
    let object = { otp: otp }
    $.ajax({
        type: 'post',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: '/security/2factor',
        data: object,
        dataType: 'json',
        success: (data) => {
            if (data.status == 1) {
                data.status = 'bg-success'
                data.status = 'bg-success';
                $status = 'success';
                $complete = 'Done!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
                window.location.href = window.location.origin + '/company-dashboard';
            } else if (data.status == 0) {
                data.status = 'bg-danger';
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
            }
        },
        error: function(jqXHR, status, error) {
            $('.toast').addClass("bg-danger");
            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connected\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else {
                msg = 'Uncaught Error.\n' + error;
            }

            $('.toast-msg').html(msg);
        }
    }).done(function() {
        $('.toast').addClass("bg-success");
    });
    $(".toast").toast({
        delay: 5000
    });
    $('.toast').toast('show');
    return false;
};

function resendOTPfactor() {

    $.ajax({
        type: 'post',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        url: '/security/2factor-resend',
        dataType: 'json',
        success: (data) => {
            if (data.status == 1) {
                data.status = 'bg-success'

                data.status = 'bg-success';
                $status = 'success';
                $complete = 'Done!';
                swal(
                    $complete,
                    data.message,
                    $status
                );

            } else if (data.status == 0) {
                data.status = 'bg-danger';
                $status = 'error';
                $complete = 'Error!';
                swal(
                    $complete,
                    data.message,
                    $status
                );
            }
        },
        error: function(jqXHR, status, error) {
            $('.toast').addClass("bg-danger");
            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connected\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else {
                msg = 'Uncaught Error.\n' + error;
            }

            $('.toast-msg').html(msg);
        }
    }).done(function() {
        $('.toast').addClass("bg-success");
    });
    $(".toast").toast({
        delay: 5000
    });
    $('.toast').toast('show');
    return false;
};