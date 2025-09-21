<div class="modal fade contact-seller-modal" id="contact_sellerModal" tabindex="-1"
     aria-labelledby="contact_sellerModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header px-sm-4 pb-1 border-0">
                <h5 class="text-capitalize" id="contact_sellerModalLabel">
                    @if(isset($shop) && isset($user_type) && $user_type == 'admin')
                        {{ translate('contact_with') }} {{ getWebConfig(name: 'company_name') }}
                    @elseif(isset($shop) && isset($user_type) && $user_type == 'seller')
                        {{ translate('contact_with') }} {{ $shop['name'] }}
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{route('messages') }}" method="post" id="contact_with_seller_form"
                      data-success-message="{{ translate('send_successfully') }}">
                    @csrf
                    <input value="{{ isset($user_type) && $user_type == 'admin' ? 0 : $shop->seller->id }}" name="vendor_id" hidden>
                    <textarea name="message" class="form-control min-height-100px max-height-200px" required
                              placeholder="{{ translate('type_your_message') }}"></textarea>
                    <div class="d-flex justify-content-end mt-3 gap-3 align-items-center">
                        <div>
                            <a href="{{route('chat', ['type' => 'vendor'])}}"
                            class="btn text-base text-capitalize">{{ translate('go_to_chatbox') }}</a>
                        </div>
                        <button class="btn btn-base text-white me-2">
                            {{ translate('send') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
