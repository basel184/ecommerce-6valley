<div class="position-relative nav--tab-wrapper mb-4">
    <ul class="nav nav-pills nav--tab" id="pills-tab" role="tablist">

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/system-setup/language') ? 'active' : '' }}"
               href="{{ route('admin.system-setup.language.index') }}">
                {{ translate('Language') }}
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/system-setup/currency/*') ? 'active' : '' }}"
               href="{{ route('admin.system-setup.currency.view') }}">
                {{ translate('Currency') }}
            </a>
        </li>

    </ul>
    <div class="nav--tab__prev">
        <button class="btn btn-circle border-0 bg-white text-primary">
            <i class="fi fi-sr-angle-left"></i>
        </button>
    </div>
    <div class="nav--tab__next">
        <button class="btn btn-circle border-0 bg-white text-primary">
            <i class="fi fi-sr-angle-right"></i>
        </button>
    </div>

</div>
