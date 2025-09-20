@extends('layouts.admin.app')

@section('title', translate('homepage_sections'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <h2 class="h1 mb-0 text-capitalize">{{ translate('homepage_sections') }}</h2>
            <a href="{{ route('admin.pages-and-media.homepage.sections.create') }}" class="btn btn-primary">{{ translate('add_new') }}</a>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ translate('title') }}</th>
                        <th>{{ translate('slug') }}</th>
                        <th>{{ translate('show_limit') }}</th>
                        <th>{{ translate('sort_order') }}</th>
                        <th>{{ translate('status') }}</th>
                        <th class="text-end">{{ translate('action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($sections as $k => $section)
                        <tr>
                            <td>{{ $sections->firstItem() + $k }}</td>
                            <td>{{ $section->title }}</td>
                            <td>{{ $section->slug }}</td>
                            <td>{{ $section->show_limit }}</td>
                            <td>{{ $section->sort_order }}</td>
                            <td>
                                <span class="badge text-bg-{{ $section->status ? 'success' : 'secondary' }}">{{ $section->status ? translate('active') : translate('inactive') }}</span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.pages-and-media.homepage.sections.edit', $section->id) }}">{{ translate('edit') }}</a>
                                <form action="{{ route('admin.pages-and-media.homepage.sections.destroy', $section->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ translate('are_you_sure') }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">{{ translate('delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $sections->links() }}
            </div>
        </div>
    </div>
@endsection
