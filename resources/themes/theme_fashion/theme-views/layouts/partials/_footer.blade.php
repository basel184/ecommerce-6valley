<footer class="footer">
    <div class="footer-top">
        <div class="container"></div>
    </div>

    <div class="footer-bottom" style="padding-top: 30px;">
        <div class="container">
            <div class="pb-3">
                <div class="row">

                    <div class="col-12">
                        <div class="">
                            <div class="footer-accordion-wrapper">
                                
                                <!-- Professional Accordion for Footer Sections -->
                                <div class="accordion footer-professional-accordion" id="footerAccordion">
                                    
                                    <!-- قسم معلومات الحساب -->
                                    <div class="accordion-item footer-accordion-item">
                                        <h2 class="accordion-header" id="accountsHeading">
                                            <button class="accordion-button footer-accordion-button collapsed" 
                                                    type="button" data-bs-toggle="collapse" data-bs-target="#accountsCollapse"
                                                    aria-expanded="false" aria-controls="accountsCollapse">
                                                <i class="bi bi-person-circle me-2"></i>
                                                {{ translate('accounts') }}
                                            </button>
                                        </h2>
                                        <div id="accountsCollapse" class="accordion-collapse collapse" 
                                             aria-labelledby="accountsHeading" data-bs-parent="#footerAccordion">
                                            <div class="accordion-body footer-accordion-body">
                                                <ul class="footer-links-accordion">
                                                    <li>
                                                        @if(auth('customer')->check())
                                                            <a href="{{ route('user-profile') }}">
                                                                <i class="bi bi-person me-2"></i>
                                                                {{ translate('profile_info') }}
                                                            </a>
                                                        @else
                                                            <a href="javascript:" class="customer_login_register_modal">
                                                                <i class="bi bi-person me-2"></i>
                                                                {{ translate('profile_info') }}
                                                            </a>
                                                        @endif
                                                    </li>
                                                    <li>
                                                        @if(auth('customer')->check())
                                                            <a href="{{ route('account-orders') }}">
                                                                <i class="bi bi-bag me-2"></i>
                                                                {{ translate('orders') }}
                                                            </a>
                                                        @else
                                                            <a href="javascript:" class="customer_login_register_modal">
                                                                <i class="bi bi-bag me-2"></i>
                                                                {{ translate('orders') }}
                                                            </a>
                                                        @endif
                                                    </li>

                                                    @if ($web_config['ref_earning_status'])
                                                        <li>
                                                            @if(auth('customer')->check())
                                                                <a href="{{ route('refer-earn') }}">
                                                                    <i class="bi bi-gift me-2"></i>
                                                                    {{ translate('refer_&_earn') }}
                                                                </a>
                                                            @else
                                                                <a href="javascript:" class="customer_login_register_modal">
                                                                    <i class="bi bi-gift me-2"></i>
                                                                    {{ translate('refer_&_earn') }}
                                                                </a>
                                                            @endif
                                                        </li>
                                                    @endif

                                                    <li>
                                                        <a href="{{ route('helpTopic') }}">
                                                            <i class="bi bi-question-circle me-2"></i>
                                                            {{ translate('FAQs') }}
                                                        </a>
                                                    </li>

                                                    @if(Route::has('frontend.blog.index') && getWebConfig(name: 'blog_feature_active_status'))
                                                        <li>
                                                            <a href="{{ route('frontend.blog.index') }}">
                                                                <i class="bi bi-journal-text me-2"></i>
                                                                {{ translate('blogs') }}
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if($web_config['business_mode'] == 'multi')
                                                        <li>
                                                            <a href="{{ route('vendor.auth.registration.index') }}">
                                                                <i class="bi bi-shop me-2"></i>
                                                                {{ translate('sell_on') }} {{ $web_config['company_name'] }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- قسم المساعدة والدعم -->
                                    <div class="accordion-item footer-accordion-item">
                                        <h2 class="accordion-header" id="supportHeading">
                                            <button class="accordion-button footer-accordion-button collapsed" 
                                                    type="button" data-bs-toggle="collapse" data-bs-target="#supportCollapse"
                                                    aria-expanded="false" aria-controls="supportCollapse">
                                                <i class="bi bi-headset me-2"></i>
                                                {{ translate('support') }}
                                            </button>
                                        </h2>
                                        <div id="supportCollapse" class="accordion-collapse collapse" 
                                             aria-labelledby="supportHeading" data-bs-parent="#footerAccordion">
                                            <div class="accordion-body footer-accordion-body">
                                                    <li>
                                                        @if(auth('customer')->check())
                                                            <a href="{{ route('account-tickets') }}">
                                                                <i class="bi bi-ticket-perforated me-2"></i>
                                                                {{ translate('support_ticket') }}
                                                            </a>
                                                        @else
                                                            <a href="javascript:" class="customer_login_register_modal">
                                                                <i class="bi bi-ticket-perforated me-2"></i>
                                                                {{ translate('support_ticket') }}
                                                            </a>
                                                        @endif
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('track-order.index') }}">
                                                            <i class="bi bi-geo-alt me-2"></i>
                                                            {{ translate('track_order') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('contacts') }}">
                                                            <i class="bi bi-envelope me-2"></i>
                                                            {{ translate('contact_us') }}
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- قسم الروابط السريعة -->
                                    <div class="accordion-item footer-accordion-item">
                                        <h2 class="accordion-header" id="quickLinksHeading">
                                            <button class="accordion-button footer-accordion-button collapsed" 
                                                    type="button" data-bs-toggle="collapse" data-bs-target="#quickLinksCollapse"
                                                    aria-expanded="false" aria-controls="quickLinksCollapse">
                                                <i class="bi bi-link-45deg me-2"></i>
                                                {{ translate('quick_links') }}
                                            </button>
                                        </h2>
                                        <div id="quickLinksCollapse" class="accordion-collapse collapse" 
                                             aria-labelledby="quickLinksHeading" data-bs-parent="#footerAccordion">
                                            <div class="accordion-body footer-accordion-body">
                                                <ul class="footer-links-accordion">
                                                    @foreach($web_config['business_pages']->where('default_status', 1) as $businessPage)
                                                        <li>
                                                            <a href="{{ route('business-page.view', ['slug' => $businessPage['slug']]) }}">
                                                                <i class="bi bi-file-text me-2"></i>
                                                                {{ Str::limit($businessPage['title'], 25, '...') }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                    <li>
                                                        <a href="/blog?category=نصائح%20عطرية">
                                                        <i class="bi bi-file-text me-2"></i>
                                                        نصائح عطرية
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('track-order.index') }}">
                                                        <i class="bi bi-file-text me-2"></i>
                                                            {{ translate('track_order') }}
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- قسم الكلمات الشائعة -->
                                    <!--<div class="accordion-item footer-accordion-item">
                                        <h2 class="accordion-header" id="tagsHeading">
                                            <button class="accordion-button footer-accordion-button collapsed" 
                                                    type="button" data-bs-toggle="collapse" data-bs-target="#tagsCollapse"
                                                    aria-expanded="false" aria-controls="tagsCollapse">
                                                <i class="bi bi-tags me-2"></i>
                                                {{ translate('popular_tags') }}
                                            </button>
                                        </h2>
                                        <div id="tagsCollapse" class="accordion-collapse collapse" 
                                             aria-labelledby="tagsHeading" data-bs-parent="#footerAccordion">
                                            <div class="accordion-body footer-accordion-body">
                                                <ul class="tags">
                                                    @foreach ($web_config['tags'] as $item)
                                                        <li>
                                                            <a href="{{ route('products') }}?search_category_value=all&name={{ str_replace(' ','+', trim($item->tag)) }}&data_from=search&page=1">
                                                                {{ Str::limit($item->tag, 25, '...') }}
                                                            </a>
                                                        </li>
                                                    @endforeach

                                                    @if ($web_config['tags']->count() == 0)
                                                        <li>
                                                            <a href="javascript:">{{ translate('no_Data_Found') }}</a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </div>-->

                                    <!-- قسم وسائل التواصل الاجتماعي -->
                                    <div class="accordion-item footer-accordion-item">
                                        <h2 class="accordion-header" id="socialHeading">
                                            <button class="accordion-button footer-accordion-button collapsed" 
                                                    type="button" data-bs-toggle="collapse" data-bs-target="#socialCollapse"
                                                    aria-expanded="false" aria-controls="socialCollapse">
                                                <i class="bi bi-share me-2"></i>
                                                {{ translate('social_media') }}
                                            </button>
                                        </h2>
                                        <div id="socialCollapse" class="accordion-collapse collapse" 
                                             aria-labelledby="socialHeading" data-bs-parent="#footerAccordion">
                                            <div class="accordion-body footer-accordion-body">
                                                @if($web_config['social_media'])
                                                    <ul class="social-icons">
                                                        @foreach ($web_config['social_media'] as $item)
                                                            <li>
                                                                @if ($item->name == "twitter")
                                                                    <a href="{{ $item->link}}" target="_blank" class="font-bold">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="18"
                                                                             height="18" viewBox="0 0 24 24">
                                                                            <g opacity=".3">
                                                                                <polygon fill="#fff" fill-rule="evenodd"
                                                                                         points="16.002,19 6.208,5 8.255,5 18.035,19"
                                                                                         clip-rule="evenodd"></polygon>
                                                                                <polygon
                                                                                    points="8.776,4 4.288,4 15.481,20 19.953,20 8.776,4"></polygon>
                                                                            </g>
                                                                            <polygon fill-rule="evenodd"
                                                                                     points="10.13,12.36 11.32,14.04 5.38,21 2.74,21"
                                                                                     clip-rule="evenodd"></polygon>
                                                                            <polygon fill-rule="evenodd"
                                                                                     points="20.74,3 13.78,11.16 12.6,9.47 18.14,3"
                                                                                     clip-rule="evenodd"></polygon>
                                                                            <path
                                                                                d="M8.255,5l9.779,14h-2.032L6.208,5H8.255 M9.298,3h-6.93l12.593,18h6.91L9.298,3L9.298,3z"
                                                                                fill="currentColor"></path>
                                                                        </svg>
                                                                    </a>
                                                                @else
                                                                    <a href="{{ $item->link}}" target="_blank">
                                                                        <i class="{{ $item->icon}}"></i>
                                                                    </a>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-base py-4">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="d-flex gap-3" style="align-items: center;">
                            <div>
                                <img src="https://www.goldenscent.com/assets/sbc.png" width="50" alt="موّثق لدى منصة الأعمال" class="sbc">
                            </div>
                            <div class="text-center text-black">
                                شركة بيرن التجارية شركة شخص واحد | رقم التسجيل الضريبي:  311278850200003 | رقم الرخصة: 1010690818 صادرة من وزارة التجارة
                            </div>

                            @if(count($web_config['business_pages']->where('default_status', 0)) > 0)
                                <ul class="links d-flex flex-wrap justify-content-center align-content-center flex-column flex-sm-row column-gap-1 row-gap-2 m-0">
                                    @foreach($web_config['business_pages']->where('default_status', 0) as $businessPage)
                                        <li class="opacity-75 text-absolute-white list-style-unset">
                                            <a href="{{ route('business-page.view', ['slug' => $businessPage['slug']]) }}"
                                               class="text-white">
                                                {{ Str::limit($businessPage['title'], 25, '...') }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
