@extends('layouts.blank')

@section('content')
    <div class="text-center text-white mb-4">
        <h2>{{ "6valley Software Installation" }}</h2>
        <h6 class="fw-normal">
            {{ "Please proceed step by step with proper data according to instructions" }}
        </h6>
    </div>

    <div class="pb-2">
        <div class="progress cursor-pointer" role="progressbar" aria-label="6valley Software Installation"
             aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip"
             data-bs-placement="top" data-bs-custom-class="custom-progress-tooltip" data-bs-title="Second Step!"
             data-bs-delay='{"hide":1000}'>
            <div class="progress-bar width-40"></div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="p-4 mb-md-3 mx-xl-4 px-md-5">
            <div class="d-flex justify-content-end mb-2">
                <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-"
                   class="d-flex align-items-center gap-1" target="_blank">
                    {{ "Where to get this information ?" }}
                    <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                          data-bs-title="Purchase code information">
                        <img src="{{ dynamicAsset(path: 'public/assets/installation/assets/img/svg-icons/info.svg') }}" alt=""
                             class="svg">
                    </span>
                </a>
            </div>

            <div class="d-flex align-items-center column-gap-3 flex-wrap">
                <h5 class="fw-bold fs text-uppercase">{{ "Step 2." }}</h5>
                <h5 class="fw-normal">{{ "Installation Ready" }}</h5>
            </div>
            <p class="mb-4">
                {{ "Ready to proceed with the installation. Click continue to proceed to database setup." }}
            </p>

            <form method="POST" action="{{ route('purchase.code') }}">
                @csrf
                <div class="bg-light p-4 rounded mb-4 text-center">
                    <div class="alert alert-success">
                        <h6 class="mb-2">{{ "License Check Bypassed" }}</h6>
                        <p class="mb-0">{{ "This installation is configured for unrestricted use." }}</p>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-dark px-sm-5">{{ "Continue" }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
