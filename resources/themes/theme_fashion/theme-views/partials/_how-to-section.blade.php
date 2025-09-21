<section class="pt-4">
    <div class="container">

        @if (!empty($web_config['features_section']['features_section_top']))
            <div class="section-title text-center pt-xl-2">
                @php($featuresSectionTop = $web_config['features_section']['features_section_top'])
                <h2 class="title">
                    {{ $featuresSectionTop['title'] ?? '' }}
                </h2>
                <p>
                    {{ $featuresSectionTop['subtitle'] ?? '' }}
                </p>
            </div>
        @endif

        @if (!empty($web_config['features_section']['features_section_middle']))
            @php($totalFeatures = count($web_config['features_section']['features_section_middle']))
            @if($totalFeatures > 0)
            <div class="table-responsive">
                <div class="how-we-work-grid">
                    @foreach ($web_config['features_section']['features_section_middle'] as $key=> $item)
                        <div class="how-to-card max-width-unset-custom">
                            <div class="how-to-icon">
                                {{ ($key + 1 <10 ? '0'.$key + 1 : $key + 1) }}
                            </div>
                            <div class="how-to-cont">
                                <h5 class="title">{{ $item['title'] ?? '' }}</h5>
                                <div>{{ $item['subtitle'] ?? '' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endif
    </div>
</section>

@if(!empty($web_config['features_section']['features_section_bottom']))
    @if(count($web_config['features_section']['features_section_bottom']) > 0)
    <div class="support-section">
        <div class="container">
            <div class="support-wrapper">
                @foreach($web_config['features_section']['features_section_bottom'] as $item)
                    <div class="support-card mb-4">
                        <div class="support-card-inner">
                            <div class="icon">
                                <?php
                                    $imageName = isset($item['icon']['image_name']) ? $item['icon']['image_name'] : ($item['icon'] ?? '');
                                    $storageType = isset($item['icon']['storage']) ? $item['icon']['storage'] : 'public';
                                    $imagePath = storageLink('banner', $imageName, $storageType);
                                ?>
                                <img loading="lazy" src="{{ getStorageImages(path: $imagePath, type:'banner') }}"
                                     alt="{{ translate('banner') }}" class="icon">
                            </div>
                            <h6 class="title">{{ $item['title'] ?? '' }}</h6>
                            <div>{{ $item['subtitle'] ?? '' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @else
    <div class="pt-4"></div>
    @endif
@endif
