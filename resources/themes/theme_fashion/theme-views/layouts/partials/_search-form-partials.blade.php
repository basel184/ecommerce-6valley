<form class="search-form category-search-form" action="{{route('products')}}" type="submit">
    <div class="input-group search_input_group">
        <!-- البحث بدون تصنيفات - التعديل الصحيح -->
        <input type="text" class="form-control" id="input-value-web" name="search" value="{{ request('search', request('name')) }}"
                placeholder="{{ translate('search_for_items') }}...">

        <button class="btn btn-base" type="submit" aria-label="{{ translate('submit') }}">
            <i class="bi bi-search"></i>
        </button>
    <div class="card search-card position-absolute z-99 w-100 bg-white d-none top-100 start-0 search-result-box-web"></div>
    </div>
    <!-- المعاملات الضرورية للبحث -->
    <input name="data_from" value="search" hidden>
    <input name="page" value="1" hidden>
    <!-- عنصر مخفي للمقترحات -->
    <input type="hidden" id="search_category_value_web" name="search_category_value" value="all">
</form>
