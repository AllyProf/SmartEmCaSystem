@extends('layouts.app')

@section('title', 'Customers')
@section('icon', 'fa-users')
@section('page-title', 'Customers')
@section('page-description', 'Manage your customers')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item">Customers</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="widget-small primary coloured-icon">
            <i class="icon fa fa-users fa-3x"></i>
            <div class="info">
                <h4>Total Customers</h4>
                <p><b>{{ $stats['total'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small info coloured-icon">
            <i class="icon fa fa-user-plus fa-3x"></i>
            <div class="info">
                <h4>Added Today</h4>
                <p><b>{{ $stats['today'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small warning coloured-icon">
            <i class="icon fa fa-calendar fa-3x"></i>
            <div class="info">
                <h4>This Week</h4>
                <p><b>{{ $stats['this_week'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small danger coloured-icon">
            <i class="icon fa fa-calendar-check-o fa-3x"></i>
            <div class="info">
                <h4>This Month</h4>
                <p><b>{{ $stats['this_month'] }}</b></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">All Customers</h3>
                <p><a class="btn btn-primary icon-btn" href="{{ route('customers.create') }}"><i class="fa fa-plus"></i>Add Customer</a></p>
            </div>
            <div class="tile-body">
                <div class="customers-toolbar">
                    <form method="GET" action="{{ route('customers.index') }}" id="customersFilterForm" class="customers-filters">
                        <input type="hidden" name="search" id="customers_search_hidden" value="{{ $search }}">
                        <div class="cf-date-range" title="Created date range">
                            <span class="cf-date-icon" aria-hidden="true"><i class="fa fa-calendar"></i></span>
                            <label class="sr-only" for="customers_date_from">From date</label>
                            <input id="customers_date_from" class="cf-date-input" type="date" name="date_from" value="{{ $dateFrom }}" placeholder="From">
                            <span class="cf-date-sep" aria-hidden="true">–</span>
                            <label class="sr-only" for="customers_date_to">To date</label>
                            <input id="customers_date_to" class="cf-date-input" type="date" name="date_to" value="{{ $dateTo }}" placeholder="To">
                        </div>

                        <div class="cf-region">
                            <span class="cf-region-icon" aria-hidden="true"><i class="fa fa-map-marker"></i></span>
                            <label class="sr-only" for="customers_region">Region</label>
                            <select id="customers_region" class="cf-region-select" name="region">
                                <option value="">All regions</option>
                                @foreach($regions as $regionOption)
                                    <option value="{{ $regionOption }}" {{ (string) $region === (string) $regionOption ? 'selected' : '' }}>
                                        {{ $regionOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="cf-actions">
                            <button type="submit" class="cf-btn cf-btn-filter" title="Filter" aria-label="Filter">
                                <i class="fa fa-filter"></i>
                            </button>
                            <a href="{{ route('customers.index') }}" class="cf-btn cf-btn-reset" title="Reset" aria-label="Reset">
                                <i class="fa fa-undo"></i>
                            </a>
                        </div>
                    </form>

                    <div id="customersTable_filter" class="dataTables_filter">
                        <label>Search:
                            <input
                                type="search"
                                id="customers_search"
                                class=""
                                placeholder=""
                                aria-controls="customersTable"
                                value="{{ $search }}"
                                autocomplete="off"
                            >
                        </label>
                    </div>
                </div>

                <div id="customersResults">
                    @include('customers._results')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .customers-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem 1rem;
        margin-bottom: 1rem;
    }
    .customers-filters {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
    }
    .cf-date-range,
    .cf-region {
        display: inline-flex;
        align-items: center;
        height: 34px;
        background: #fff;
        border: 1px solid #d8dee6;
        border-radius: 6px;
        overflow: hidden;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .cf-date-range:focus-within,
    .cf-region:focus-within {
        border-color: #940000;
        box-shadow: 0 0 0 3px rgba(148, 0, 0, 0.12);
    }
    .cf-date-icon,
    .cf-region-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 100%;
        color: #6c757d;
        background: #f7f8fa;
        border-right: 1px solid #e6ebf0;
        flex-shrink: 0;
        font-size: 13px;
    }
    .cf-date-input,
    .cf-region-select {
        border: 0;
        outline: 0;
        background: transparent;
        height: 100%;
        padding: 0 0.55rem;
        font-size: 13px;
        color: #2f3640;
        line-height: 1;
        box-shadow: none !important;
    }
    .cf-date-input {
        width: 8.6rem;
        min-width: 8.6rem;
        color-scheme: light;
    }
    .cf-date-input::-webkit-calendar-picker-indicator {
        opacity: 0.55;
        cursor: pointer;
        padding: 0;
        margin: 0;
    }
    .cf-date-input:hover::-webkit-calendar-picker-indicator {
        opacity: 0.85;
    }
    .cf-date-sep {
        color: #9aa3af;
        font-size: 13px;
        font-weight: 600;
        padding: 0 0.1rem;
        user-select: none;
    }
    .cf-region-select {
        min-width: 8.75rem;
        max-width: 11rem;
        padding-right: 1.6rem;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%236c757d' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.65rem center;
        background-size: 10px 6px;
        cursor: pointer;
    }
    .cf-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .cf-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        padding: 0;
        border-radius: 6px;
        border: 1px solid transparent;
        line-height: 1;
        font-size: 13px;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
        text-decoration: none !important;
    }
    .cf-btn-filter {
        background: #940000;
        border-color: #940000;
        color: #fff;
    }
    .cf-btn-filter:hover,
    .cf-btn-filter:focus {
        background: #7a0000;
        border-color: #7a0000;
        color: #fff;
        box-shadow: 0 0 0 3px rgba(148, 0, 0, 0.16);
    }
    .cf-btn-reset {
        background: #fff;
        border-color: #d8dee6;
        color: #5c6773;
    }
    .cf-btn-reset:hover,
    .cf-btn-reset:focus {
        background: #f7f8fa;
        border-color: #c5ccd6;
        color: #2f3640;
        box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.08);
    }
    .customers-toolbar > .dataTables_filter {
        float: none;
        text-align: right;
        margin: 0;
    }
    .customers-toolbar > .dataTables_filter label {
        margin-bottom: 0;
        font-weight: normal;
    }
    .customers-toolbar > .dataTables_filter input {
        margin-left: 0.5em;
        display: inline-block;
        width: auto;
    }
    #customersTable_wrapper > .row:first-child {
        display: none;
    }
    #customersResults.is-loading {
        opacity: 0.55;
        pointer-events: none;
        transition: opacity .15s ease;
    }
    @media (max-width: 767.98px) {
        .cf-date-input {
            width: 7.6rem;
            min-width: 7.6rem;
        }
        .cf-region-select {
            min-width: 7.5rem;
        }
        .customers-toolbar > .dataTables_filter {
            width: 100%;
            text-align: left;
        }
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('vali-master/docs/js/plugins/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vali-master/docs/js/plugins/dataTables.bootstrap.min.js') }}"></script>
<script>
(function () {
    var searchTimer = null;
    var requestId = 0;

    function initCustomersTable() {
        if ($.fn.DataTable.isDataTable('#customersTable')) {
            $('#customersTable').DataTable().destroy();
        }

        if ($('#customersTable').length) {
            $('#customersTable').DataTable({
                paging: false,
                info: false,
                searching: false,
                lengthChange: false,
                order: [[0, 'desc']]
            });
        }
    }

    function buildCustomersUrl(pageUrl) {
        var form = document.getElementById('customersFilterForm');
        var search = $('#customers_search').val() || '';
        $('#customers_search_hidden').val(search);

        var params = new URLSearchParams(new FormData(form));

        if (search === '') {
            params.delete('search');
        }

        params.delete('page');

        if (pageUrl) {
            var pageParams = new URL(pageUrl, window.location.origin).searchParams;
            if (pageParams.has('page')) {
                params.set('page', pageParams.get('page'));
            }
        }

        var query = params.toString();
        return form.action + (query ? ('?' + query) : '');
    }

    function loadCustomers(pageUrl) {
        var url = buildCustomersUrl(pageUrl);
        var currentRequest = ++requestId;
        var $results = $('#customersResults');

        $results.addClass('is-loading');

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Failed to load customers');
                }
                return response.text();
            })
            .then(function (html) {
                if (currentRequest !== requestId) {
                    return;
                }
                $results.html(html);
                initCustomersTable();
                history.replaceState(null, '', url);
            })
            .catch(function () {
                if (currentRequest === requestId) {
                    window.location.href = url;
                }
            })
            .finally(function () {
                if (currentRequest === requestId) {
                    $results.removeClass('is-loading');
                }
            });
    }

    initCustomersTable();

    $('#customers_search').on('input', function () {
        $('#customers_search_hidden').val(this.value);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            loadCustomers();
        }, 300);
    });

    $('#customersFilterForm').on('submit', function () {
        $('#customers_search_hidden').val($('#customers_search').val() || '');
    });

    $('#customersResults').on('click', '.pagination a.page-link', function (event) {
        event.preventDefault();
        loadCustomers(this.href);
    });
})();
</script>
@endpush
