@if (
    $page_name != 'coming_soon' &&
        $page_name != 'contact_us' &&
        $page_name != 'error404' &&
        $page_name != 'error500' &&
        $page_name != 'error503' &&
        $page_name != 'faq' &&
        $page_name != 'helpdesk' &&
        $page_name != 'maintenence' &&
        $page_name != 'privacy' &&
        $page_name != 'auth_boxed' &&
        $page_name != 'auth_default')
    @if (auth()->check())
        @php
            $auth = auth()->user();
        @endphp
        <!-- ln -s /wba/storage/ storage
 BEGIN SIDEBAR  -->
        <div class="sidebar-wrapper sidebar-theme">
            <nav id="sidebar">
                <div class="profile-info">
                    <figure class="user-cover-image"></figure>
                    <div class="user-info">
                        <?php $logo = url('storage/company/profile/14/finova_favicon.png'); ?>
                        <img src="{{ $auth->profile_pic == null ? 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png' : $logo }}"
                            alt="https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png" id="userImage"
                            onerror="">
                        {{-- <img src="{{$auth->profile_pic == null ? 'https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png' : $logo}}" alt="https://cdn.pixabay.com/photo/2020/07/01/12/58/icon-5359553_960_720.png" id="userImage" onerror="loadDefault2()" width='100'> --}}
                        <div class="col-md-12 mt-3">
                            <p class="text-center" style="font-size: 18px; font-weight: bold">
                                {{ $auth->first_name .
                                    '
                                                        ' .
                                    $auth->last_name }}</p>

                        </div>

                    </div>
                    @if (cando('root'))
                        <span class='badge badge-primary'><span>Bal</span>: {!! $auth->company->currency->symbol !!}
                            <span id="companyWalletBalance"> {{ moneyFormat($auth->company->wallet->balance) }}</span>
                        </span>
                    @endif
                </div>

                {{-- <div class="shadow-bottom"></div> --}}
                <ul class="list-unstyled menu-categories" id="accordionExample">

                    @if ($page_name != 'alt_menu' && $page_name != 'blank_page' && $page_name != 'boxed' && $page_name != 'breadcrumb')


                        <li class="menu {{ $category_name === 'dashboard' ? 'active' : '' }}">
                            <a href="{{ route('home-index') }}"
                                aria-expanded="{{ $category_name === 'dashboard' ? 'true' : 'false' }}"
                                class="dropdown-toggle">
                                <div class="">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="feather feather-home">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                    </svg>
                                    <span>Dashboard </span>

                                </div>
                            </a>
                        </li>

                        @if (cando('manage-branch|root'))
                            <li class="menu {{ $page_menu === 'company_branch_menu' ? 'active' : '' }}">
                                <a href="{{ url('company/' . $auth->company->short_name . '/branch') }}"
                                    aria-expanded="{{ $page_menu === 'company_branch_menu' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-pie-chart">
                                            <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                            <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                                        </svg>
                                        <span>Branch Management</span>
                                    </div>
                                </a>
                            </li>
                        @endif

                        @if (isset($auth->company->setting->access_fcm_messanger))
                            @if ($auth->company->setting->access_fcm_messanger)
                                @if (cando('fcm-message|root'))
                                    <li class="menu {{ $page_menu === 'fcm_message' ? 'active' : '' }}">
                                        <a href="{{ url('/fcm-messanger') }}"
                                            aria-expanded="{{ $page_menu === 'fcm_message' ? 'true' : 'false' }}"
                                            class="dropdown-toggle">
                                            <div class="">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="feather feather-pie-chart">
                                                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                                    <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                                                </svg>
                                                <span>Send App Notification</span>
                                            </div>
                                        </a>
                                    </li>
                                @endif
                            @endif
                        @endif
                        @if (isset($auth->company->setting->access_meeting))
                            @if ($auth->company->setting->access_meeting)
                                @if (cando('meeting|root'))
                                    <li class="menu {{ $page_menu === 'meeting' ? 'active' : '' }}">
                                        <a href="{{ url('/meeting') }}"
                                            aria-expanded="{{ $page_menu === 'meeting' ? 'true' : 'false' }}"
                                            class="dropdown-toggle">
                                            <div class="">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="feather feather-pie-chart">
                                                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                                    <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                                                </svg>
                                                <span>Live Meeting</span>
                                            </div>
                                        </a>
                                    </li>
                                @endif
                            @endif
                        @endif
                        @if (cando(
                                'broadcast|manage_caousel|view-customer-profile|edit-customer|manage-customer|root|viewreport|customer-restriction|import-customer'))
                            <li
                                class="menu {{ $page_menu === 'company_broadcast' || $page_menu === 'transfer_customer' || $page_menu === 'export_all_account' || $page_menu === 'all_mandates' || $page_menu === 'manage_caousel' || $page_menu === 'all_account' || $page_menu === 'customers_delete' || $page_menu === 'bvn_verification_menu' || $page_menu === 'customer_profile' || $page_menu === 'import_customer' || $page_menu === 'pending_entry_statement' || $page_menu === 'viewprofile' || $page_menu === 'create_customer' || $page_menu === 'customer_restriction' || $page_menu === 'customers_profile' || $page_menu === 'view_account_by_branch' ? 'active' : '' }}">
                                <a href="#customer_sidbarmenu" data-toggle="collapse"
                                    aria-expanded="{{ $page_menu === 'company_broadcast' || $page_menu === 'transfer_customer' || $page_menu === 'export_all_account' || $page_menu === 'all_mandates' || $page_menu === 'create_direct_debit_mandate' || $page_menu === 'manage_caousel' || $page_menu === 'all_account' || $page_menu === 'customers_delete' || $page_menu === 'bvn_verification_menu' || $page_menu === 'customer_profile' || $page_menu === 'import_customer' || $page_menu === 'pending_entry_statement' || $page_menu === 'viewprofile' || $page_menu === 'create_customer' || $page_menu === 'customer_restriction' || $page_menu === 'customers_profile' || $page_menu === 'view_account_by_branch' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-users">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                            <circle cx="9" cy="7" r="4" />
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                        </svg>
                                        <span>CRM</span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>
                                <ul class="collapse submenu list-unstyled {{ $page_menu === 'company_broadcast' || $page_menu === 'transfer_customer' || $page_menu === 'export_all_account' || $page_menu === 'all_mandates' || $page_menu === 'create_direct_debit_mandate' || $page_menu === 'manage_caousel' || $page_menu === 'all_account' || $page_menu === 'customers_delete' || $page_menu === 'bvn_verification_menu' || $page_menu === 'customer_profile' || $page_menu === 'pending_entry_statement' || $page_menu === 'viewprofile' || $page_menu === 'bvn_verification_menu' || $page_menu === 'create_customer' || $page_menu === 'customer_restriction' || $page_menu === 'customers_profile' || $page_menu === 'view_account_by_branch' ? 'show' : '' }}"
                                    id="customer_sidbarmenu" data-parent="#accordionExample">
                                    @if (cando('approve-customer|view-pending-customer|root'))
                                        <li class="{{ $page_menu === 'pending_entry_statement' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/pending-entry') }}">
                                                Pending
                                                Entry</a>
                                        </li>
                                    @endif
                                    @if (cando('view-all-customer|root'))
                                        <li class="{{ $page_menu === 'all_account' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/all-customer') }}">
                                                All Customer</a>
                                        </li>
                                        <li class="{{ $page_menu === 'export_all_account' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/export-all-customer') }}">Export
                                                All Customer</a>
                                        </li>
                                    @endif
                                    @if (cando('create-customer|root'))
                                        <li class="{{ $page_menu === 'create_customer' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/customer/create_account') }}">
                                                Create Account </a>
                                        </li>
                                    @endif
                                    @if (cando('edit-customer|view-customer-profile|root'))
                                        <li class="{{ $page_menu === 'customers_profile' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/customer/customer_profiles') }}">
                                                Customer Profile </a>
                                        </li>
                                        @if ($page_menu == 'customer_profile')
                                            <li class="{{ $page_menu === 'customer_profile' ? 'active' : '' }}">
                                                <a
                                                    href="{{ url('company/' . $auth->company->short_name . '/customer/customer_profiles') }}">
                                                    Single Profile </a>
                                            </li>
                                        @endif


                                        @if ($page_menu == 'viewprofile')
                                            <li class="{{ $page_menu == 'viewprofile' ? 'active' : '' }}">
                                                <a
                                                    href="{{ url('company/' . $auth->company->short_name . '/view-profile/' . $user->uuid) }}">View
                                                    Profile</a>
                                            </li>
                                        @endif
                                    @endif
                                    @if (cando('bvn-verification|root'))
                                        <li class="{{ $page_menu === 'bvn_verification_menu' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/bvn-verification') }}">KYC
                                                Verification</a>
                                        </li>
                                    @endif
                                    @if (cando('import-customer|root'))
                                        <li class="{{ $page_menu === 'import_customer' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/customer/import-account') }}">
                                                Import your Account </a>
                                        </li>
                                    @endif
                                    @if (cando('root|customer-restriction'))
                                        <li class="{{ $page_menu === 'customer_restriction' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/restriction') }}">Customer
                                                Restriction</a>
                                        </li>
                                    @endif

                                    @if (cando('root|delete-customer'))
                                        <li class="{{ $page_menu === 'customers_delete' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/customer/customer-delete ') }}">Delete
                                                Customer</a>
                                        </li>
                                    @endif

                                    @if (cando('root|create-customer-overdraft'))
                                        <li class="{{ $page_menu === 'customer_overdraft' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/customer-overdraft ') }}">Create
                                                Overdraft</a>
                                        </li>
                                    @endif

                                    @if (cando('root|direct-mandate'))
                                        <li
                                            class="{{ $page_menu === 'create_direct_debit_mandate' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/mandates ') }}">Create
                                                Debit Mandate</a>
                                        </li>
                                    @endif

                                    @if (cando('root|direct-mandate'))
                                        <li class="{{ $page_menu === 'all_mandates' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/all-mandates ') }}">View
                                                All Debit Mandates</a>
                                        </li>
                                    @endif
                                    @if (cando('manage_caousel|root'))
                                        <li class="{{ $page_menu === 'manage_caousel' ? 'active' : '' }}">
                                            <a href="{{ url('company/advert-banner') }}">
                                                App Banner Ads</a>
                                        </li>
                                    @endif
                                    @if (cando('root'))
                                        <li class="{{ $page_menu === 'transfer_customer' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transfer-customer') }}">Customer
                                                Ownership Transfer</a>
                                        </li>
                                    @endif
                                    @if (cando('root'))
                                        <li class="{{ $page_menu === 'view_account_by_branch' ? 'active' : '' }}">
                                            <a href="{{ url('company/view-customer-by-branch') }}">View Customer By
                                                Currency</a>
                                        </li>
                                    @endif
                                    @if (cando('broadcast|root'))
                                        <li class="{{ $page_menu === 'company_broadcast' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/broadcast') }}">Broadcast
                                                Message</a>
                                        </li>
                                    @endif

                                </ul>
                            </li>
                        @endif

                        @if (isset($auth->company->setting->access_esusu_schedule))

                            @if ($auth->company->setting->access_esusu_schedule == 1)
                                @if (cando('foodstash-product|manage-foodstash|root'))

                                    <li
                                        class="menu {{ $page_menu === 'schedule_esusu_product' || $page_menu === 'schedule_esusu_operation' ? 'active' : '' }}">
                                        <a href="#foodStash-mgt" data-toggle="collapse"
                                            aria-expanded="{{ $page_menu === 'schedule_esusu_product' || $page_menu === 'schedule_esusu_operation' ? 'true' : 'false' }}"
                                            class="dropdown-toggle">
                                            <div class="">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="feather feather-trending-up">
                                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                                                    <polyline points="17 6 23 6 23 12" />
                                                </svg>
                                                <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                                <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                                                </svg>
                                                <span>Food-Stash Management</span>
                                            </div>
                                            <div>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="feather feather-chevron-right">
                                                    <polyline points="9 18 15 12 9 6"></polyline>
                                                </svg>
                                            </div>
                                        </a>

                                        <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $page_menu === 'schedule_esusu_product' || $page_menu === 'schedule_esusu_operation' ? 'show' : '' }} "
                                            id="foodStash-mgt" data-parent="#accordionExample">

                                            @if (cando('foodstash-product|manage-foodstash|root'))

                                                <li
                                                    class="{{ $page_menu === 'schedule_esusu_operation' ? 'active' : '' }}">
                                                    <a href="{{ url('company/esusu/schedule/operation') }}">Food
                                                        Stash</a>
                                                </li>
                                                @if (cando('foodstash-product|root'))
                                                    <li
                                                        class="{{ $page_menu === 'schedule_esusu_product' ? 'active' : '' }}">
                                                        <a href="{{ url('company/esusu/schedule/product') }}">Food
                                                            Stash Product</a>
                                                    </li>
                                                @endif
                                            @endif

                                        </ul>
                                    </li>
                                @endif
                            @endif

                        @endif


                        @if (cando('root'))
                            <li class="menu {{ $category_name === 'mobile_esusu' ? 'active' : '' }}">
                                <a href="#mobile-esusu" data-toggle="collapse"
                                    aria-expanded="{{ $category_name === 'mobile_esusu' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-smartphone">
                                            <rect x="5" y="2" width="14" height="20" rx="2"
                                                ry="2" />
                                            <line x1="12" y1="18" x2="12.01" y2="18" />
                                        </svg>
                                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                                        </svg>
                                        <span>Customer Mobile Esusu</span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>

                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $category_name === 'mobile_esusu' ? 'show' : '' }} "
                                    id="mobile-esusu" data-parent="#accordionExample">
                                    <li class="{{ $page_menu === 'manage_mobile_esusu_product' ? 'active' : '' }}">
                                        <a href="{{ url('company/' . $auth->company->short_name . '/mobile-esusu') }}">
                                            Agent </a>
                                    </li>
                                    <li class="{{ $page_menu === 'manage_mobile_esusu_withdraw' ? 'active' : '' }}">
                                        <a
                                            href="{{ url('company/' . $auth->company->short_name . '/mobile-customer-withdrawal') }}">
                                            Withdraw Request </a>
                                    </li>
                                    <li class="{{ $page_menu === 'manage_mobile_esusu' ? 'active' : '' }}">
                                        <a
                                            href="{{ url('company/' . $auth->company->short_name . '/mobile-customer-esusu') }}">
                                            Customer Esusu </a>
                                    </li>

                                </ul>
                            </li>
                        @endif
                        @if (cando('manage-saving|credit-customer|debit-customer|root|manage-dom-account|pending-credit-debit'))
                            <li
                                class="menu {{ $page_menu === 'BuckTransactions' || $page_menu === 'pending_saving' || $page_menu === 'savings_withdraw' || $page_menu === 'sub_invoice' || $page_menu == 'account_statement' || $page_menu == 'general_statement' || $page_menu == 'customer_statement' || $page_menu == 'dom_account' || $page_menu == 'reverse_statement' ? 'active' : '' }}">
                                <a href="#savings-management" data-toggle="collapse"
                                    aria-expanded="{{ $page_menu === 'BuckTransactions' || $page_menu === 'pending_saving' || $page_menu === 'savings_withdraw' || $page_menu == 'general_statement' || $page_menu === 'sub_invoice' || $page_menu == 'account_statement' || $page_menu == 'customer_statement' || $page_menu == 'dom_account' || $page_menu == 'reverse_statement' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-globe">
                                            <circle cx="12" cy="12" r="10" />
                                            <line x1="2" y1="12" x2="22" y2="12" />
                                            <path
                                                d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                                        </svg>
                                        <span>Account Management </span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>

                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $page_menu === 'BuckTransactions' || $page_menu === 'pending_saving' || $page_menu === 'savings_withdraw' || $page_menu == 'general_statement' || $page_menu === 'sub_invoice' || $page_menu == 'dom_account' || $page_menu == 'account_statement' || $page_menu == 'customer_statement' || $page_menu == 'reverse_statement' ? 'show' : '' }} "
                                    id="savings-management" data-parent="#accordionExample">
                                    @if (cando('manage-saving|credit-customer|debit-customer|root'))
                                        <li class="{{ $page_menu === 'savings_withdraw' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/saving') }}">
                                                Savings Manager</a>
                                        </li>
                                        <li class="{{ $page_menu === 'BuckTransactions' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/bulk-transaction') }}">
                                                Bulk Transaction</a>
                                        </li>

                                        @if (isset($txnid))
                                            <li class="{{ $page_menu === 'sub_invoice' ? 'active' : '' }}">
                                                <a href="{{ url('transaction-receipt/' . $txnid) }}">
                                                    TXN Invoice</a>
                                            </li>
                                        @endif
                                    @endif
                                    @if (cando('pending-credit-debit|root'))
                                        <li class="{{ $page_menu === 'pending_saving' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/saving/pending') }}">
                                                Pending Transaction Request</a>
                                        </li>
                                    @endif
                                    @if (cando('manage-dom-account|root'))
                                        <li class="{{ $page_menu == 'dom_account' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/user-dom') }}">
                                                Dom Account</a>
                                        </li>
                                    @endif

                                    @if (cando('view-customer-statement|root'))
                                        @if ($page_menu == 'account_statement')
                                            <li class="{{ $page_menu == 'account_statement' ? 'active' : '' }}">
                                                <a
                                                    href="{{ url('company/' . $auth->company->short_name . '/user-statement/' . $user->uuid) }}">Account
                                                    Statement </a>
                                            </li>
                                        @endif

                                        <li class="{{ $page_menu == 'customer_statement' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/user-statement') }}">View
                                                Statement</a>
                                        </li>
                                    @endif
                                    @if (cando('reverse-customer-statement|root'))
                                        <li class="{{ $page_menu == 'reverse_statement' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction-reversal/') }}">Reverse
                                                Transaction</a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        @if (cando('view-pos-terminals|root|add-pos'))
                            <li class="menu {{ $page_menu === 'pos_management' ? 'active' : '' }}">
                                <a href="#pos_management" data-toggle="collapse"
                                    aria-expanded="{{ $page_menu === 'pos_management' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-globe">
                                            <circle cx="12" cy="12" r="10" />
                                            <line x1="2" y1="12" x2="22" y2="12" />
                                            <path
                                                d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                                        </svg>
                                        <span>Terminal Management</span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>

                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $page_menu === 'pos_management' ? 'show' : '' }} "
                                    id="pos_management" data-parent="#accordionExample">
                                    @if (cando('manage-saving|root'))
                                        <li class="{{ $page_menu === 'pos_management' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/pos/index') }}">
                                                All POS</a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if (cando(
                                'view-group-loan|assign-group-loan|view-loan-repayment|app-loan-setting|bvn-loan-verification|loan-all|loan-guarantor|loan-book|loan-pending-approve|loan-rejected|loan-disbursement|view-loan-repayment|loan-repayment|loan-ongoing|loan-complete|loan-rollover|loan-writeoff|loan-delete-repayment|root'))
                            <li
                                class="menu {{ $page_menu === 'bulk_loan_upload' || $page_menu === 'loan_print_out' || $page_menu === 'assign-group' || $page_menu === 'view-group-loan' || $page_menu === 'pending_loan_repayment' || $page_menu === 'loan_edit' || $page_menu === 'loan_main_setting' || $page_menu === 'bvn_loan_verification_menu' || $page_menu === 'loan_all' || $page_menu === 'loan_guarantor' || $page_menu === 'loan_Complete' || $page_menu == 'pay_loan' || $page_menu === 'loan_ongoing' || $page_menu === 'loan_Rejected' || $page_menu === 'loan_disbursment' || $page_menu === 'loan_main' || $page_menu === 'loan_pending' ? 'active' : '' }}">
                                <a href="#loan-management" data-toggle="collapse"
                                    aria-expanded="{{ $page_menu === 'bulk_loan_upload' || $page_menu === 'loan_print_out' || $page_menu === 'assign-group' || $page_menu === 'view-group-loan' || $page_menu === 'pending_loan_repayment' || $page_menu === 'loan_edit' || $page_menu === 'loan_main_setting' || $page_menu === 'bvn_loan_verification_menu' || $page_menu === 'loan_all' || $page_menu === 'loan_guarantor' || $page_menu === 'loan_Complete' || $page_menu == 'pay_loan' || $page_menu === 'loan_ongoing' || $page_menu === 'loan_Rejected' || $page_menu === 'loan_disbursment' || $page_menu === 'loan_product' || $page_menu === 'loan_main' || $page_menu === 'loan_pending' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-globe">
                                            <circle cx="12" cy="12" r="10" />
                                            <line x1="2" y1="12" x2="22" y2="12" />
                                            <path
                                                d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                                        </svg>
                                        <span>Asset Management</span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>
                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $page_menu === 'bulk_loan_upload' || $page_menu === 'loan_print_out' || $page_menu === 'assign-group' || $page_menu === 'view-group-loan' || $page_menu === 'pending_loan_repayment' || $page_menu === 'loan_edit' || $page_menu === 'loan_main_setting' || $page_menu === 'bvn_loan_verification_menu' || $page_menu === 'loan_all' || $page_menu === 'loan_guarantor' || $page_menu === 'loan_Complete' || $page_menu == 'pay_loan' || $page_menu === 'loan_ongoing' || $page_menu === 'loan_Rejected' || $page_menu === 'loan_disbursment' || $page_menu === 'loan_main' || $page_menu === 'loan_pending' ? 'show' : '' }} "
                                    id="loan-management" data-parent="#accordionExample">
                                    @if (cando('app-loan-setting|root'))
                                        <li class="{{ $page_menu === 'loan_main_setting' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/app-setting') }}">
                                                Loan App Settings</a>
                                        </li>
                                    @endif

                                    @if (cando('bvn-loan-verification|root'))
                                        <li
                                            class="{{ $page_menu === 'bvn_loan_verification_menu' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/loan-bvn-check') }}">
                                                Loan Look-Up</a>
                                        </li>
                                    @endif

                                    @if (cando('loan-guarantor|root'))
                                        <li class="{{ $page_menu === 'loan_guarantor' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/guarantor') }}">
                                                Loan Guarantor</a>
                                        </li>
                                    @endif
                                    @if (cando('loan-book|root'))
                                        <li class="{{ $page_menu === 'loan_main' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/loan/book') }}">
                                                Book Loan</a>
                                        </li>
                                        <li class="{{ $page_menu === 'bulk_loan_upload' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/bulk-upload') }}">Bulk
                                                Loan Upload</a>
                                        </li>
                                    @endif
                                    @if (cando('loan-all|root'))
                                        <li class="{{ $page_menu === 'loan_all' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/all-loan') }}">
                                                All Loan</a>
                                        </li>
                                    @endif
                                    @if (cando('loan-pending-approve|root'))
                                        <li class="{{ $page_menu === 'loan_pending' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/pending') }}">Loan
                                                Pending</a>
                                        </li>
                                    @endif
                                    @if (cando('view-loan-pending-repayment|root'))
                                        <li class="{{ $page_menu === 'pending_loan_repayment' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/pending-repayment') }}">
                                                Pending Repayment</a>
                                        </li>
                                    @endif

                                    @if (cando('loan-edit|root'))
                                        <li class="{{ $page_menu === 'loan_edit' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/edit-loan#') }}">
                                                Edit Loan</a>
                                        </li>
                                    @endif
                                    @if (cando('loan-disbursement|root'))
                                        <li class="{{ $page_menu === 'loan_disbursment' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/disbursment') }}">
                                                Loan Disbursment</a>
                                        </li>
                                    @endif

                                    @if (cando('loan-rejected|root'))
                                        <li class="{{ $page_menu === 'loan_Rejected' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/rejected') }}">
                                                Loan Rejected</a>
                                        </li>
                                    @endif

                                    @if (cando('loan-ongoing|root'))
                                        <li class="{{ $page_menu === 'loan_ongoing' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/Ongoing') }}">
                                                Ongoing Loan</a>
                                        </li>
                                    @endif

                                    @if (cando('loan-complete|root'))
                                        <li class="{{ $page_menu === 'loan_Complete' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/complete-loan') }}">
                                                Complete Loan</a>
                                        </li>
                                    @endif

                                    @if (isset($loanID))
                                        @if ($loanID)
                                            <li class="{{ $page_menu == 'pay_loan' ? 'active' : '' }}">
                                                <a
                                                    href="{{ url('company/' . $auth->company->short_name . '/loan/pay-loan/' . $loanID) }}">
                                                    Payloan Loan</a>
                                            </li>
                                            @if ($page_menu === 'loan_print_out')
                                                <li class="{{ $page_menu === 'loan_print_out' ? 'active' : '' }}">
                                                    <a
                                                        href="{{ url('company/' . $auth->company->short_name . '/loan/offer/' . $loanID) }}">Print
                                                        Offer</a>
                                                </li>
                                            @endif
                                        @endif
                                    @endif
                                    @if (cando('view-group-loan|root'))
                                        <li class="{{ $page_menu === 'view-group-loan' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/loan/group') }}">
                                                Create Loan Group</a>
                                        </li>
                                    @endif
                                    @if (cando('assign-group-loan|root'))
                                        <li class="{{ $page_menu === 'assign-group' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/assign-group') }}">
                                                Assign Loan Group</a>
                                        </li>
                                    @endif



                                </ul>
                            </li>
                        @endif


                        @if (isset($loanID))
                            @if ($loanID)
                                <li class="{{ $page_menu == 'pay_loan' ? 'active' : '' }}">
                                    <a
                                        href="{{ url('company/' . $auth->company->short_name . '/loan/pay-loan/' . $loanID) }}">
                                        Payloan Loan</a>
                                </li>
                            @endif
                        @endif
                        {{-- --}}


                        @if (cando('manage-investment|book-investment|root'))
                            <li
                                class="menu {{ $page_menu === 'user_viewinvestment_booking' || $page_menu === 'investment_booking' || $page_menu === 'viewinvestment_booking' ? 'active' : '' }}">
                                <a href="#investment-menu" data-toggle="collapse"
                                    aria-expanded="{{ $page_menu === 'user_viewinvestment_booking' || $page_menu === 'investment_booking' || $page_menu === 'viewinvestment_booking' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-trending-up">
                                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                                            <polyline points="17 6 23 6 23 12" />
                                        </svg>
                                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                                        </svg>
                                        <span>Wealth Management</span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>

                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $page_menu === 'user_viewinvestment_booking' || $page_menu === 'investment_booking' || $page_menu === 'viewinvestment_booking' ? 'show' : '' }} "
                                    id="investment-menu" data-parent="#accordionExample">


                                    <li class="{{ $page_menu === 'investment_booking' ? 'active' : '' }}">
                                        <a
                                            href="{{ url('company/' . $auth->company->short_name . '/investment-booking') }}">
                                            Book Investment </a>
                                    </li>
                                    @if (isset($accountid))
                                        <li
                                            class="{{ $page_menu === 'user_viewinvestment_booking' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/view-user-investment/' . $accountid) }}">
                                                User Investments </a>
                                        </li>
                                    @endif


                                    <li class="{{ $page_menu === 'viewinvestment_booking' ? 'active' : '' }}">
                                        <a
                                            href="{{ url('company/' . $auth->company->short_name . '/view-investment') }}">
                                            View Investment </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                        @if (isset($auth->company->setting->access_talent))

                            @if ($auth->company->setting->access_talent == 1)
                                {{-- @if ($auth->company->setting->access_schedule_esusu == 1) --}}


                                {{--  --}}
                                @if (cando('root'))
                                    <li
                                        class="menu {{ $page_menu === 'competition_category' || $page_menu === 'competition' || $page_menu === 'competition_talents' || $page_menu === 'competition_votes' ? 'active' : '' }}">
                                        <a href="#talent-menu" data-toggle="collapse"
                                            aria-expanded="{{ $page_menu === 'competition_category' || $page_menu === 'competition' || $page_menu === 'competition_talents' || $page_menu === 'competition_votes' ? 'true' : 'false' }}"
                                            class="dropdown-toggle">
                                            <div class="">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="feather feather-trending-up">
                                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                                                    <polyline points="17 6 23 6 23 12" />
                                                </svg>
                                                <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                                <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                                                </svg>
                                                <span>Competition</span>
                                            </div>
                                            <div>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="feather feather-chevron-right">
                                                    <polyline points="9 18 15 12 9 6"></polyline>
                                                </svg>
                                            </div>
                                        </a>

                                        <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $page_menu === 'competition_category' || $page_menu === 'competition' || $page_menu === 'competition_talents' || $page_menu === 'competition_votes' ? 'show' : '' }} "
                                            id="talent-menu" data-parent="#talent-menu">


                                            <li class="{{ $page_menu === 'competition' ? 'active' : '' }}">
                                                <a href="{{ url('company/competition') }}">Settings </a>
                                            </li>
                                            <li
                                                class="{{ $page_menu === 'competition_category' ? 'active' : '' }}">
                                                <a href="{{ url('company/competition/category') }}">Category</a>
                                            </li>
                                            <li class="{{ $page_menu === 'competition_talents' ? 'active' : '' }}">
                                                <a href="{{ url('company/competition/talents') }}">Talents</a>
                                            </li>
                                            <li class="{{ $page_menu === 'competition_votes' ? 'active' : '' }}">
                                                <a href="{{ url('company/competition/votes') }}">Votes</a>
                                            </li>




                                        </ul>
                                    </li>
                                @endif
                            @endif

                        @endif

                        @if (cando('manage-target-savings|root'))
                            <li class="menu {{ $page_menu === 'custom-target-savings' ? 'active' : '' }}">
                                <a href="{{ url('company/customer-saving') }}"
                                    aria-expanded="{{ $page_menu === 'custom-target-savings' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-pie-chart">
                                            <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                            <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                                        </svg>
                                        <span>Target Savings</span>
                                    </div>
                                </a>

                            </li>
                        @endif

                        @if (cando(
                                'buy-airtime-data|mobile-money-transfer|business-transfer|view-company-transaction-history|root|wallet-to-wallet'))
                            <li
                                class="menu {{ $category_name === 'funds_transfer' || $page_menu === 'airtime_menu' || $page_menu === 'data_menu' || $page_menu === 'transaction_list_menu' || $page_menu === 'verify_and_reverse_transactions' ? 'active' : '' }}">
                                <a href="#funds-transfer" data-toggle="collapse"
                                    aria-expanded="{{ $category_name === 'funds_transfer' || $page_menu === 'airtime_menu' || $page_menu === 'data_menu' || $page_menu === 'transaction_list_menu' || $page_menu === 'verify_and_reverse_transactions' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-globe">
                                            <circle cx="12" cy="12" r="10" />
                                            <line x1="2" y1="12" x2="22" y2="12" />
                                            <path
                                                d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                                        </svg>
                                        <span>Electronic Payment Service </span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>
                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $category_name === 'funds_transfer' || $page_menu === 'airtime_menu' || $page_menu === 'data_menu' || $page_menu === 'transaction_list_menu' || $page_menu === 'verify_and_reverse_transactions' ? 'show' : '' }} "
                                    id="funds-transfer" data-parent="#accordionExample">
                                    @if (cando('wallet-to-wallet|root'))
                                        <li class="{{ $page_name === 'wallet_to_wallet' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/wallet-to-wallet-transfer') }}">
                                                Wallet to Wallet </a>
                                        </li>
                                    @endif
                                    @if (cando('business-transfer|root'))
                                        <li class="{{ $page_name === 'company_wallet_transfer' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/company-wallet-transfer') }}">
                                                Business Transfer </a>
                                        </li>
                                    @endif
                                    @if (cando('mobile-money-transfer|root'))
                                        <li class="{{ $page_name === 'nigerian_bank_transfer' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/nigerian-bank-transfer') }}">
                                                Nigerian Bank Transfer </a>
                                        </li>
                                    @endif


                                    @if (cando('approve-bank-transfer|root'))
                                        <li class="{{ $page_name === 'pending_transactions' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/pending-transaction') }}">
                                                Pending NG BankTransfer </a>
                                        </li>
                                    @endif
                                    @if (cando('mobile-money-transfer|root'))
                                        <li class="{{ $page_name === 'mobile_money_transfer' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/mobile-money-transfer') }}">
                                                International Transfer </a>
                                        </li>
                                    @endif
                                    @if (cando('view-company-transaction-history|root'))
                                        <li class="{{ $page_name === 'transaction_list_menu' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/list') }}">
                                                Transactions History</a>
                                        </li>
                                    @endif

                                    @if (cando('view-company-transaction-history|root'))
                                        <li
                                            class="{{ $page_name === 'verify_and_reverse_transactions' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/verify-and-reverse-transaction') }}">
                                                TSQ reverse transaction</a>
                                        </li>
                                    @endif

                                    @if (cando('view-company-transaction-history|root'))
                                        <li
                                            class="{{ $page_name === 'other_transaction_list_menu' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/tier-settlement') }}">
                                                Pending Tier Settlement</a>
                                        </li>
                                    @endif

                                    @if (cando('view-company-transaction-history|root'))
                                        <li
                                            class="{{ $page_name === 'other_transaction_list_menu' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/other-list') }}">
                                                Other Transactions History</a>
                                        </li>
                                    @endif
                                    @if (cando('buy-airtime-data|root'))
                                        <li class="{{ $page_menu === 'airtime_menu' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/buy-airtime') }}">
                                                Buy Airtime </a>
                                        </li>
                                        @if ($auth->company->currency->id == 1)
                                            <li class="{{ $page_name === 'data_menu' ? 'active' : '' }}">
                                                <a
                                                    href="{{ url('company/' . $auth->company->short_name . '/transaction/buy-data') }}">
                                                    Buy Data </a>
                                            </li>
                                        @endif
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if (cando('physical-card-request|card-management|physical-card-management|root'))
                            <li
                                class="menu {{ $page_menu === 'card_management_menu' || $page_menu === 'card_request_menu' || $page_menu === 'physical_card_management' || $page_menu === 'card_requests_menu' || $page_menu == 'physical_card_management' ? 'active' : '' }}">
                                <a href="#card-management" data-toggle="collapse"
                                    aria-expanded="{{ $page_menu === 'card_management_menu' || $page_menu === 'card_requests_menu' || $page_menu == 'physical_card_management' || $page_menu === 'card_request_menu' || $page_menu === 'physical_card_management' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">

                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-credit-card">
                                            <rect x="1" y="4" width="22" height="16" rx="2"
                                                ry="2"></rect>
                                            <line x1="1" y1="10" x2="23" y2="10">
                                            </line>
                                        </svg>
                                        <span>Payment Card Management</span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>

                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $page_menu === 'card_management_menu' || $page_menu === 'card_requests_menu' || $page_menu == 'physical_card_management' || $page_menu === 'card_request_menu' || $page_menu === 'physical_card_management' ? 'show' : '' }} "
                                    id="card-management" data-parent="#accordionExample">

                                    @if (cando('card-management|root'))
                                        <li class="{{ $page_menu === 'card_management_menu' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/card-management/company-card-management') }}">My
                                                Virtual Card</a>
                                        </li>
                                    @endif
                                    @if (cando('physical-card-request|root'))
                                        <li class="{{ $page_menu === 'card_requests_menu' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/customer/card_requests') }}">
                                                Request Card History </a>
                                        </li>
                                    @endif
                                    @if (cando('physical-card-management|root'))
                                        <li
                                            class="{{ $page_menu === 'physical_card_management' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/customer/physical-card-management') }}">
                                                Card Management </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        @if (cando('expenses-type|view-expenses|add-expenses|view-pending-expenses|rejected-expenses|root'))

                            <li class="menu {{ $category_name === 'expenses' ? 'active' : '' }}">
                                <a href="#expenses" data-toggle="collapse"
                                    aria-expanded="{{ $category_name === 'expenses' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">

                                        <svg width="22" height="16" viewBox="0 0 512 512"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill="rgb(176, 174, 174)"
                                                d="M268.383 22.168l-55.918 84.482 29.717 3.733c-9.22 30.13-11.095 50.878-8.885 92.12 14.138-2.23 25.56-3.025 40.586 1.39-9.877-36.84-8.844-49.427-4.88-89.768l32.622 2.277-33.242-94.234zm218.482 2.21l-108.36 30.03 20.915 25.975c-49.512 31.019-80.331 55.548-104.74 123.164 13.201-.152 28.098 2.921 44.174 9.004 5.728-44.666 33.74-76.14 79.302-108.918l19.983 24.816 48.726-104.07zm-463.574 2.31L89.17 129.173l19.084-28.711c35.554 32.44 58.145 76.33 57.308 107.43 18.568-8.696 29.927-9.527 49.735-3.778-8.105-31.203-43.577-108.722-91.639-129.103l16.57-26.037L23.292 26.687zm276.117 214.667c-5.28.12-10.21 2.415-16.937 9.594l-6.565 6.969-6.812-6.72c-7.387-7.28-13.216-9.29-19.125-9.03-5.908.26-12.855 3.367-20.625 9.656l-6.217 5.03-5.906-5.374c-8.9-8.052-16.485-10.439-23.75-10.064-5.288.274-10.775 2.266-16.25 5.75l40.966 73.69c15.454 9.451 47.034 13.006 68.75 2.062l39.594-73.344c-7.51-3.062-14.26-6.202-20.094-7.406-2.112-.437-4.07-.756-5.968-.813-.354-.01-.71-.008-1.06 0zm-89.97 96.188v.002c-18.035 12.742-32.516 34.717-38.125 66.904-5.435 31.196 3.129 52.266 18.283 66.625 15.155 14.36 37.902 21.736 61 21.436 23.1-.3 46.136-8.31 61.625-22.936 15.49-14.627 24.249-35.425 19.281-65.187-5.137-30.757-18.4-52.148-35.19-65.094-28.482 15.056-64.095 11.856-86.875-1.75z" />
                                        </svg>

                                        <span>Expenditure Control</span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>
                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $category_name === 'expenses' ? 'show' : '' }} "
                                    id="expenses" data-parent="#expenses">
                                    @if (cando('expenses-type|root'))
                                        <li class="{{ $page_menu === 'expenses_type' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/expenses/type') }}">
                                                Expenses Type</a>
                                        </li>
                                    @endif
                                    @if (cando('view-expenses|add-expenses|root'))
                                        <li class="{{ $page_menu === 'expenses' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/expenses/main') }}">
                                                Expenses </a>
                                        </li>
                                    @endif
                                    @if (cando('view-pending-expenses|root'))
                                        <li class="{{ $page_menu === 'PendingExpenses' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/expenses/pending') }}">
                                                Pending Expenses </a>
                                        </li>
                                    @endif
                                    @if (cando('rejected-expenses|root'))
                                        <li class="{{ $page_menu === 'RejectedExpenses' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/expenses/reject') }}">
                                                Rejected Expenses </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        @if (cando('View-Pending-otherincome|otherincome-type|view-otherincome|add-otherincome|root'))

                            <li class="menu {{ $category_name === 'OtherIncome' ? 'active' : '' }}">
                                <a href="#OtherIncome" data-toggle="collapse"
                                    aria-expanded="{{ $category_name === 'OtherIncome' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">

                                        <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg"
                                            xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24"
                                            height="24" viewBox="0 0 964.556 964.556"
                                            style="enable-background:new 0 0 964.556 964.556;" xml:space="preserve">
                                            <g>
                                                <g>
                                                    <path d="M378.1,761.828c25.301,0,50-2.5,73.9-7.2c-1.2-13.7,2.7-27.5,11.6-38.8L680,441.228c10.5-13.3,26.2-21,43.2-21
   c11,0,21.399,3.2,30.3,9.101c1.8-14.9,2.7-30.101,2.7-45.5c0-208.8-169.3-378.1-378.101-378.1c-208.8,0-378.1,169.2-378.1,378
   S169.3,761.828,378.1,761.828z M448.6,436.628c-15.199-18.7-41.3-26.601-64.8-32c-29.1-6.601-55.399-13.2-81.8-28
   c-16.9-9.4-30.2-22.7-39.3-39.5c-9.1-16.6-13.7-35.8-13.7-56.8c0-37.4,13.5-68.2,40.1-91.5c15.7-13.8,37.2-23.3,61.5-27.8v-20.4
   c0-13.8,11.2-25,25-25H389.2c13.8,0,25,11.2,25,25v20.7c22.5,4.4,42.6,13.2,57.6,25.7c15.9,13.2,27.601,30,34.8,50.1
   c5.301,14.8-4.399,30.7-19.899,33l-13.2,2c-12,1.8-23.5-5.2-27.4-16.7c-3.199-9.3-7.399-17-12.699-22.9
   c-11.9-13.4-30.7-21-51.5-21c-22.2,0-43.101,8.6-56,23.1c-10.601,11.9-15.7,25.5-15.7,41.8c0,15.9,4.5,29.4,13.3,40
   c18.4,22.2,48.6,28.2,77.7,34.1c17,3.4,34.6,7,49.899,13.7c16.301,7.2,29.801,16.1,40.101,26.4c10.399,10.399,18.5,23,24.1,37.3
   c5.601,14.2,8.4,29.8,8.4,46.4c0,36.3-11.8,67.1-34.9,91.399c-19,20-44.899,33.2-74.6,38.5v15.101c0,13.8-11.2,25-25,25h-13.7
   c-13.8,0-25-11.2-25-25v-15.7c-16.4-3.101-32.1-8.4-46.2-15.7c-16.6-8.6-31.1-22.5-43.1-41.4c-8-12.6-13.8-27.199-17.3-43.6
   c-3-13.8,6.3-27.3,20.2-29.7l13.5-2.399c12.9-2.2,25.3,5.8,28.5,18.5c3.801,14.899,8.9,26.6,15.301,34.8
   c14.3,18.399,38,29.399,63.3,29.399l0,0c4.3,0,8.6-0.3,12.8-1c18.1-2.699,34.3-11.3,45.6-24.1c11.9-13.6,18-30.7,18-50.8
   C461.1,460.628,456.9,446.828,448.6,436.628z" />
                                                    <path d="M583.6,958.828H862.7c13.8,0,25-11.2,25-25v-159h51.8c20.9,0,32.6-24.101,19.6-40.5l-211.8-268.9l-4.5-5.7
   c-5-6.399-12.3-9.5-19.6-9.5s-14.601,3.2-19.601,9.5L487.2,734.328c-3.2,4.1-4.9,8.6-5.3,13.1c-1.301,13.7,9.199,27.4,24.899,27.4
   h51.8v159C558.6,947.628,569.8,958.828,583.6,958.828z" />
                                                </g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                            <g>
                                            </g>
                                        </svg>
                                        <span>Other Income Management</span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>
                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $category_name === 'OtherIncome' ? 'show' : '' }} "
                                    id="OtherIncome" data-parent="#OtherIncome">
                                    @if (cando('otherincome-type|root'))
                                        <li class="{{ $page_menu === 'OtherIncome_type' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/income/type') }}">
                                                Income Type</a>
                                        </li>
                                    @endif
                                    @if (cando('otherincome-type|view-otherincome|add-otherincome|root'))
                                        <li class="{{ $page_menu === 'OtherIncome' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/income/main') }}">
                                                Other Income </a>
                                        </li>
                                    @endif
                                    @if (cando('View-Pending-otherincome|root'))
                                        <li class="{{ $page_menu == 'PendingOtherIncome' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/income/pending') }}">
                                                Pending Other Income </a>
                                        </li>
                                    @endif
                                    @if (cando('reject-Pending-otherincome|root'))
                                        <li class="{{ $page_menu == 'RejectedOtherIncome' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/income/reject') }}">
                                                Rejected Other Income </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>

                        @endif

                        @if (isset($auth->company->setting->access_ecommerce))
                            @if ($auth->company->setting->access_ecommerce)
                                @if (cando('ecommerce-orderproduct|root'))
                                    <li
                                        class="menu {{ $page_menu === 'ecommerce_category' || $page_menu === 'ecommerce_orderproduct' || $page_menu === 'ecommerce_orderproduct_other' ? 'active' : '' }}">
                                        <a href="#ecommerce" data-toggle="collapse"
                                            aria-expanded="{{ $page_menu === 'ecommerce_category' || $page_menu === 'ecommerce_orderproduct' || $page_menu === 'ecommerce_orderproduct_other' ? 'true' : 'false' }}"
                                            class="dropdown-toggle">
                                            <div class="">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="feather feather-credit-card">
                                                    <rect x="1" y="4" width="22" height="16" rx="2"
                                                        ry="2"></rect>
                                                    <line x1="1" y1="10" x2="23"
                                                        y2="10"></line>
                                                </svg>
                                                <span>E-commerce</span>
                                            </div>
                                            <div>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="feather feather-chevron-right">
                                                    <polyline points="9 18 15 12 9 6"></polyline>
                                                </svg>
                                            </div>
                                        </a>
                                        <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $page_menu === 'ecommerce_category' || $page_menu === 'ecommerce_orderproduct' || $page_menu === 'ecommerce_orderproduct_other' ? 'show' : '' }} "
                                            id="ecommerce" data-parent="#accordionExample">
                                            <li
                                                class="{{ $page_menu === 'ecommerce_orderproduct' ? 'active' : '' }}">
                                                <a
                                                    href="{{ url('company/' . $auth->company->short_name . '/ecommerce/pending-orders#') }}">Pending
                                                    Orders</a>
                                            </li>

                                            <li
                                                class="{{ $page_menu === 'ecommerce_orderproduct_other' ? 'active' : '' }}">
                                                <a
                                                    href="{{ url('company/' . $auth->company->short_name . '/ecommerce/orders#') }}">Orders</a>
                                            </li>
                                            <li class="{{ $page_menu === 'ecommerce_category' ? 'active' : '' }}">
                                                <a
                                                    href="{{ url('company/' . $auth->company->short_name . '/ecommerce/category#') }}">Category</a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif
                            @endif
                        @endif
                        @if (cando('manage-staff|root|manage-payroll|role-list||manage-role|manage-vault'))
                            <li
                                class="menu {{ $page_menu === 'manage_vault' || $page_menu === 'role_permisson' || $page_menu === 'company_staff_menu' || $page_menu === 'staff_payroll_report' || $page_menu === 'staff_payroll_menu' ? 'active' : '' }}">
                                <a href="#staff-management" data-toggle="collapse"
                                    aria-expanded="{{ $page_menu === 'manage_vault' || $page_menu === 'role_permisson' || $page_menu === 'company_staff_menu' || $page_menu === 'staff_payroll_report' || $page_menu === 'staff_payroll_menu' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-users">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                            <circle cx="9" cy="7" r="4" />
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                        </svg>
                                        <span>Staff|Vault Management</span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>
                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $page_menu === 'manage_vault' || $page_menu === 'role_permisson' || $page_menu === 'company_staff_menu' || $page_menu === 'staff_payroll_menu' || $page_menu === 'staff_payroll_report' ? 'show' : '' }} "
                                    id="staff-management" data-parent="#accordionExample">
                                    @if (cando('manage-staff|root'))
                                        <li class="{{ $page_menu === 'company_staff_menu' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/staff') }}">
                                                Manage Staff </a>
                                        </li>
                                    @endif

                                    @if (cando('root|role-list|manage-role'))
                                        <li class="{{ $page_menu === 'role_permisson' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/role') }}"> Role
                                                & Permission</a>
                                        </li>
                                    @endif
                                    @if (cando('root|manage-payroll'))
                                        <li class="{{ $page_menu === 'staff_payroll_menu' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/staff-payroll') }}">
                                                Staff
                                                Payroll </a>
                                        </li>

                                        <li class="{{ $page_menu === 'staff_payroll_report' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/staff-payroll-report') }}">
                                                Staff
                                                Payroll Logs </a>
                                        </li>
                                    @endif

                                    @if (cando('root|manage-vault'))
                                        <li class="{{ $page_menu === 'manage_vault' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/vault') }}">Manage
                                                Vault</a>
                                        </li>
                                    @endif

                                </ul>
                            </li>
                        @endif
                        <li class="menu {{ $page_menu === 'submit_vault' ? 'active' : '' }}">
                            <a href="{{ url('company/' . $auth->company->short_name . '/transfer-voucher') }}"
                                aria-expanded="{{ $page_menu === 'submit_vault' ? 'true' : 'false' }}"
                                class="dropdown-toggle">
                                <div class="">

                                    <svg xmlns="http://www.w3.org/2000/svg" fill='#000' width="24"
                                        height="24" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1"
                                        id="Layer_1" x="0px" y="0px" viewBox="0 0 502 502"
                                        style="enable-background:new 0 0 502 502;" xml:space="preserve">
                                        <g>
                                            <g>
                                                <g>
                                                    <path
                                                        d="M492,107.295H10c-5.523,0-10,4.478-10,10v267.41c0,5.522,4.477,10,10,10h482c5.523,0,10-4.478,10-10v-267.41     C502,111.772,497.523,107.295,492,107.295z M482,356.857l-41.028-41.028C446.04,308.116,449,298.899,449,289     c0-27.019-21.981-49-49-49h-29v-29c0-27.019-21.981-49-49-49c-27.019,0-49,21.981-49,49s21.981,49,49,49h29v29     c0,27.019,21.981,49,49,49c9.899,0,19.116-2.96,26.829-8.028l44.733,44.733H20v-247.41h204.152l38.774,38.774     c1.953,1.952,4.512,2.929,7.071,2.929s5.119-0.977,7.071-2.929c3.905-3.905,3.905-10.237,0-14.143l-24.632-24.632H482V356.857z      M406.071,280.929L385.143,260H400c15.991,0,29,13.01,29,29s-13.009,29-29,29c-15.991,0-29-13.01-29-29v-14.857l20.929,20.929     c1.953,1.952,4.512,2.929,7.071,2.929s5.119-0.977,7.071-2.929C409.976,291.167,409.976,284.835,406.071,280.929z      M315.928,219.071L336.857,240H322c-15.991,0-29-13.01-29-29s13.009-29,29-29c15.991,0,29,13.01,29,29v14.857l-20.929-20.929     c-3.905-3.904-10.237-3.904-14.143,0C312.023,208.833,312.023,215.165,315.928,219.071z" />
                                                    <path
                                                        d="M159.116,238.924c-22.595-9.35-32.204-16.102-32.204-29.608c0-10.389,7.531-22.336,27.529-22.336     c10.842,0,19.143,2.431,25.118,4.987c4.242,1.814,9.138-0.202,10.773-4.517l0.766-2.022c1.515-3.999-0.34-8.524-4.267-10.216     c-6.822-2.939-15.565-5.254-26.935-5.633v-17.141c0-4.447-3.605-8.051-8.051-8.051c-4.447,0-8.051,3.605-8.051,8.051v18.18     c-23.894,3.895-39.217,20.258-39.217,41.814c0,22.595,16.362,34.542,42.853,44.931c19.219,7.791,29.348,16.622,29.348,30.906     c0,14.804-12.466,25.452-30.906,25.452c-10.871,0-21.163-2.758-29.572-6.753c-4.306-2.046-9.43,0.059-11.037,4.547l-0.786,2.194     c-1.368,3.82,0.3,8.093,3.934,9.898c9.126,4.531,21.512,7.566,33.566,7.774v18.18c0,4.447,3.605,8.051,8.051,8.051h0.258     c4.447,0,8.051-3.605,8.051-8.051v-18.959c26.751-4.415,41.035-23.634,41.035-44.151     C199.372,263.337,185.867,249.831,159.116,238.924z" />
                                                    <path
                                                        d="M392,229c2.559,0,5.119-0.977,7.071-2.929l11-11c3.905-3.905,3.905-10.237,0-14.143c-3.905-3.904-10.237-3.904-14.143,0     l-11,11c-3.905,3.905-3.905,10.237,0,14.143C386.881,228.023,389.441,229,392,229z" />
                                                    <path
                                                        d="M318,303.001c2.559,0,5.119-0.977,7.071-2.929l11-11c3.905-3.905,3.905-10.237,0-14.143     c-3.905-3.905-10.237-3.905-14.142,0l-11,11c-3.905,3.905-3.905,10.237,0,14.143C312.882,302.024,315.441,303.001,318,303.001z" />
                                                    <path
                                                        d="M48,213c-5.523,0-10,4.478-10,10v121c0,5.522,4.477,10,10,10s10-4.478,10-10V223C58,217.478,53.523,213,48,213z" />
                                                    <path
                                                        d="M48,148c-5.523,0-10,4.478-10,10v24c0,5.522,4.477,10,10,10s10-4.478,10-10v-24C58,152.478,53.523,148,48,148z" />
                                                </g>
                                            </g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                        <g>
                                        </g>
                                    </svg>
                                    <span>Voucher</span>
                                </div>
                            </a>
                        </li>

                        @if (cando(
                                'ecommerce-product|loan-product|loan-update-product|manage-company-settings|root|manage-product|manage-timeout|manage-investment-product|manage-esusu'))
                            <li
                                class="menu {{ $page_menu === 'ecommerce_product' || $page_menu === 'loan_product' || $page_menu === 'manage_esusu_product' || $page_menu === 'company_settings' || $page_menu === 'sub_plan' || $page_menu === 'subscrption' || $page_menu === 'company_payment_link' || $page_menu === 'savings_product' || $page_menu === 'openingclose' || $page_menu === 'investment_product' ? 'active' : '' }}">
                                <a href="#systemsetup" data-toggle="collapse"
                                    aria-expanded="{{ $page_menu === 'ecommerce_product' || $page_menu === 'loan_product' || $page_menu === 'manage_esusu_product' || $page_menu === 'company_settings' || $page_menu === 'sub_plan' || $page_menu === 'subscrption' || $page_menu === 'savings_product' || $page_menu === 'company_payment_link' || $page_menu === 'openingclose' || $page_menu === 'investment_product' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-settings">
                                            <circle cx="12" cy="12" r="3" />
                                            <path
                                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                                        </svg>
                                        <span>Controls</span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>


                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $page_menu === 'ecommerce_product' || $page_menu === 'loan_product' || $page_menu === 'manage_esusu_product' || $page_menu === 'company_settings' || $page_menu === 'subscrption' || $page_menu === 'savings_product' || $page_menu === 'investment_product' || $page_menu === 'openingclose' || $page_menu === 'company_payment_link' || $page_menu === 'sub_plan' ? 'show' : '' }} "
                                    id="systemsetup" data-parent="#accordionExample">

                                    @if (cando('manage-company-settings|root'))
                                        <li class="{{ $page_menu === 'company_settings' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/settings/setup') }}">System
                                                Settings</a>
                                        </li>
                                    @endif
                                    @if (cando('ecommerce-product|root'))
                                        <li class="{{ $page_menu === 'ecommerce_product' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/ecommerce/product') }}">Ecomerce
                                                Product</a>
                                        </li>
                                    @endif
                                    {{-- $page_menu =='ecommerce_product' --}}
                                    @if (cando('loan-product|loan-update-product|root'))
                                        <li class="{{ $page_menu === 'loan_product' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/product') }}">
                                                Loan Product</a>
                                        </li>
                                    @endif
                                    @if (cando('manage-investment-product|root'))
                                        <li class="{{ $page_menu === 'investment_product' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/product/investment') }}">Add
                                                Investment Product</a>
                                        </li>
                                    @endif
                                    @if (cando('manage-esusu|root'))
                                        <li class="{{ $page_menu === 'manage_esusu_product' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/esusu/product') }}">Esusu
                                                Product</a>
                                        </li>
                                    @endif

                                    @if (cando('manage-product|root'))
                                        <li class="{{ $page_menu === 'savings_product' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/product/savings') }}">Savings
                                                Product</a>
                                        </li>
                                    @endif




                                    @if (cando('manage-timeout|root'))
                                        <li class="{{ $page_menu === 'openingclose' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/opening') }}">Open
                                                - Close -
                                                Time</a>
                                        </li>
                                    @endif
                                    @if (cando('manage-company-settings|root'))
                                        <li class="{{ $page_menu === 'subscrption' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/plan') }}">Subscription
                                                Manager</a>
                                        </li>
                                        <li class="{{ $page_menu === 'sub_plan' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/plan/transaction') }}">Subscription
                                                History</a>
                                        </li>

                                        <li class="{{ $page_menu === 'company_payment_link' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/settings/show-payment-link') }}">Payment
                                                Link</a>
                                        </li>
                                    @endif

                                </ul>
                            </li>

                        @endif


                        @if (cando(
                                'view-teller-transaction|loanOfficer-performance-report|loan-arrear-report|repayment-report|view-callover|view-all-callover|root|view-end-of-day'))
                            <li
                                class="menu {{ $page_menu === 'staff_txn_table' || $page_menu === 'loan_Due_collection' || $page_menu === 'loan_officer_performance' || $page_menu === 'loan_area' || $page_menu == 'loan_repayment_data' || $page_menu === 'company_broadcast_log' || $page_menu === 'manage_esusu_statement' || $page_menu === 'loan_Due' || $page_menu === 'loan_missed' || $page_menu === 'loan_disbursed' || $page_menu === 'callover_report' || $page_menu === 'customer_overdraft_list' || $page_menu === 'end_of_day_report' ? 'active' : '' }}">
                                <a href="#report" data-toggle="collapse"
                                    aria-expanded="{{ $page_menu === 'staff_txn_table' || $page_menu === 'loan_Due_collection' || $page_menu === 'loan_officer_performance' || $page_menu === 'loan_area' || $page_menu === 'loan_repayment_data' || $page_menu === 'company_broadcast_log' || $page_menu === 'manage_esusu_statement' || $page_menu === 'loan_Due' || $page_menu === 'loan_missed' || $page_menu === 'loan_disbursed' || $page_menu === 'callover_report' || $page_menu === 'customer_overdraft_list' || $page_menu === 'end_of_day_report' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-settings">
                                            <circle cx="12" cy="12" r="3" />
                                            <path
                                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                                        </svg>
                                        <span>Report</span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>
                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $page_menu === 'staff_txn_table' || $page_menu === 'loan_Due_collection' || $page_menu === 'loan_officer_performance' || $page_menu === 'loan_area' || $page_menu === 'loan_repayment_data' || $page_menu === 'company_broadcast_log' || $page_menu === 'manage_esusu_statement' || $page_menu === 'loan_Due' || $page_menu === 'loan_missed' || $page_menu === 'loan_disbursed' || $page_menu === 'callover_report' || $page_menu === 'customer_overdraft_list' || $page_menu === 'end_of_day_report' ? 'show' : '' }} "
                                    id="report" data-parent="#accordionExample">
                                    @if (cando('view-callover|view-all-callover|root'))
                                        <li class="{{ $page_menu === 'callover_report' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/callover-report') }}">Call-Over</a>
                                        </li>
                                    @endif


                                    @if (cando('root|view-end-of-day'))
                                        <li class="{{ $page_menu === 'end_of_day_report' ? 'active' : '' }}">
                                            <a href="{{ url('company/' . $auth->company->short_name . '/end-of-day') }}">End
                                                Of Day
                                                Report</a>
                                        </li>
                                    @endif
                                    @if (cando('root|view-customer-overdraft'))
                                        <li class="{{ $page_menu === 'customer_overdraft_list' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/transaction/customer-overdraft-list') }}">Customer
                                                Overdraft List</a>
                                        </li>
                                    @endif
                                    @if (cando('root|view-loan-missed'))
                                        <li class="{{ $page_menu === 'loan_missed' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/missed-loan-report') }}">
                                                Missed Loan</a>
                                        </li>
                                    @endif
                                    @if (cando('root|loan-arrear-report'))
                                        <li class="{{ $page_menu === 'loan_area' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/loan-arrears-report') }}">Loan
                                                Arrear Report</a>
                                        </li>
                                    @endif
                                    @if (cando('view-teller-transaction|root'))
                                        <li class="{{ $page_menu === 'staff_txn_table' ? 'active' : '' }}">
                                            <a href="{{ url('company/staff-transaction') }}">Staff Transaction</a>
                                        </li>
                                    @endif
                                    @if (cando('root|view-loan-disbursed'))
                                        <li class="{{ $page_menu === 'loan_disbursed' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/disbursed-loan-report') }}">
                                                Disbursed Loan</a>
                                        </li>
                                    @endif
                                    @if (cando('loan-due-report|root'))
                                        <li class="{{ $page_menu === 'loan_Due' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/due-loan') }}">
                                                Due Loan</a>
                                        </li>
                                        <li class="{{ $page_menu === 'loan_Due_collection' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/collection-sheet') }}">
                                                Loan CollectionSheet</a>
                                        </li>
                                    @endif
                                    @if (cando('loanOfficer-performance-report|root'))
                                        <li
                                            class="{{ $page_menu === 'loan_officer_performance' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/loan-officer-performance') }}">
                                                Officer Performance s</a>
                                        </li>
                                    @endif
                                    @if (cando('repayment-report|root'))
                                        <li class="{{ $page_menu === 'loan_repayment_data' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/loan/loan-repayment-report') }}">Loan
                                                Repayment</a>
                                        </li>
                                    @endif

                                    @if ($page_menu == 'manage_esusu_statement')
                                        <li class="{{ $page_menu === 'manage_esusu_statement' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/esusu/pending') }}">
                                                Esusu
                                                Statement </a>
                                        </li>
                                    @endif
                                    @if (cando('root'))
                                        <li class="{{ $page_menu === 'company_broadcast_log' ? 'active' : '' }}">
                                            <a
                                                href="{{ url('company/' . $auth->company->short_name . '/notification-log') }}">
                                                Notification Log</a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        @if (cando('balancesheet|root'))

                            <li class="menu {{ $category_name === 'accounting' ? 'active' : '' }}">
                                <a href="#accounting" data-toggle="collapse"
                                    aria-expanded="{{ $category_name === 'accounting' ? 'true' : 'false' }}"
                                    class="dropdown-toggle">
                                    <div class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-credit-card">
                                            <rect x="1" y="4" width="22" height="16" rx="2"
                                                ry="2"></rect>
                                            <line x1="1" y1="10" x2="23" y2="10">
                                            </line>
                                        </svg>
                                        <span>Accounting Report</span>
                                    </div>
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-chevron-right">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </div>
                                </a>
                                <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled {{ $category_name === 'accounting' ? 'show' : '' }} "
                                    id="accounting" data-parent="#accordionExample">
                                    @if (cando('root'))
                                        <li class="{{ $page_menu === 'balancesheet' ? 'active' : '' }}">
                                            <a href="{{ url('/company/accounting/view-ledger-account') }}">Manage
                                                Ledger Account</a>
                                        </li>
                                        <li class="{{ $page_menu === 'ledgerAccountSetting' ? 'active' : '' }}">
                                            <a href="{{ url('/company/accounting/ledger-setting') }}">Ledger
                                                Settings</a>
                                        </li>
                                    @endif
                                    <li class="{{ $page_menu === 'LedgerDetils' ? 'active' : '' }}">
                                        <a href="{{ url('/company/accounting/ledger-details') }}">Ledger Transaction
                                            Details</a>
                                    </li>
                                    <li class="{{ $page_menu === 'profitAndloss' ? 'active' : '' }}">
                                        <a href="{{ url('/company/accounting/general-ledger') }}">General Ledger</a>
                                    </li>
                                    <li class="{{ $page_menu === 'trialBalance' ? 'active' : '' }}">
                                        <a href="{{ url('/company/accounting/trial-balance') }}">Trial Balance</a>
                                    </li>
                                    <li class="{{ $page_menu === 'balancesheet' ? 'active' : '' }}">
                                        <a href="{{ url('/company/accounting/balance-sheet-page') }}">Balance
                                            Sheet</a>
                                    </li>
                                    <li class="{{ $page_menu === 'cashflow' ? 'active' : '' }}">
                                        <a href="{{ url('/company/accounting/view-cash-flow') }}">CashFlow
                                            Accumulated</a>
                                    </li>
                                    <li class="{{ $page_menu === 'profitAndloss' ? 'active' : '' }}">
                                        <a href="{{ url('/company/accounting/view-profit-and-loss') }}">Profit and
                                            Loss</a>
                                    </li>
                                    <li class="{{ $page_menu === 'closingbalance' ? 'active' : '' }}">
                                        <a href="{{ url('/company/accounting/closing-balance') }}">Close/Open
                                            Accounting</a>
                                    </li>





                                </ul>
                            </li>
                        @endif
                        <li class="menu {{ $page_menu === 'userprofile' ? 'active' : '' }}">
                            <a href="{{ url('company/' . $auth->company->short_name . '/staff-profile') }}"
                                aria-expanded="{{ $page_menu === 'userprofile' ? 'true' : 'false' }}"
                                class="dropdown-toggle">
                                <div class="">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="feather feather-users">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                    <span>Profile Settings</span>
                                </div>
                            </a>
                        </li>

                        <li class="menu">
                            <a href="{{ url('logout') }}" aria-expanded="false" class="dropdown-toggle">
                                <div class="">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round"
                                        class="feather feather-pie-chart">
                                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                                    </svg>
                                    <span>Logout</span>
                                </div>
                            </a>

                        </li>
                    @endif
                </ul>

            </nav>

        </div>
        <!--  END SIDEBAR  -->

    @endif
@endif
