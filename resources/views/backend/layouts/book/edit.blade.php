@extends('backend.app', ['title' => 'Edit Book'])
@section('title', 'Admin || Edit Book')

@push('styles')
    <link href="{{ asset('default/datatable.css') }}" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
    <div class="app-content main-content mt-0">
        <div class="side-app">
            <div class="main-container container-fluid">

                <!-- PAGE HEADER -->
                <div class="page-header d-flex justify-content-between align-items-center">
                    <h1 class="page-title mb-0">Edit Book</h1>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.book.index') }}">Books</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </div>

                <!-- EDIT BOOK FORM -->
                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card shadow-sm">
                            <div class="card-header border-0 bg-primary text-white">
                                <h3 class="card-title mb-0">Update Book</h3>
                            </div>

                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.book.update', $book->id) }}"
                                    enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')

                                    <!-- BASIC INFO -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label for="title" class="form-label required">Title</label>
                                            <input type="text" name="title" id="title"
                                                class="form-control @error('title') is-invalid @enderror"
                                                value="{{ old('title', $book->title) }}">
                                            @error('title')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="author" class="form-label required">Author</label>
                                            <input type="text" name="author" id="author"
                                                class="form-control @error('author') is-invalid @enderror"
                                                value="{{ old('author', $book->author) }}">
                                            @error('author')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- CATEGORY & ISBN -->
                                    <div class="row mb-4">




                                        <div class="form-group mb-3">
                                            <label for="category_ids">Categories</label>
                                            <select name="category_ids[]" id="category_ids" class="form-control select2"
                                                multiple required>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}"
                                                        {{ in_array($category->id, $book->book_categories->pluck('category_id')->toArray()) ? 'selected' : '' }}>
                                                        {{ $category->title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>




                                        <div class="col-md-4">
                                            <label for="isbn" class="form-label">ISBN</label>
                                            <input type="text" name="isbn" id="isbn"
                                                class="form-control @error('isbn') is-invalid @enderror"
                                                value="{{ old('isbn', $book->isbn) }}">
                                            @error('isbn')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- PREMIUM ACCESS & PUBLISH DATE -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="form-check mt-4">
                                                <input type="checkbox" name="is_premium" id="is_premium"
                                                    class="form-check-input" value="1"
                                                    {{ $book->is_premium ? 'checked' : '' }}>
                                                <label for="is_premium" class="form-check-label fw-bold">Require
                                                    Subscription</label>
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                <strong>OFF:</strong> Free for all users<br>
                                                <strong>ON:</strong> Subscribers only
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="published_at" class="form-label">Published At</label>
                                            <input type="datetime-local" name="published_at" id="published_at"
                                                class="form-control @error('published_at') is-invalid @enderror"
                                                value="{{ $book->published_at ? $book->published_at : '' }}">
                                            @error('published_at')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <hr>

                                    <!-- FILE UPLOADS -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label for="ebook_file" class="form-label">Ebook File (PDF/EPUB)</label>
                                            <input type="file" name="ebook_file" id="ebook_file"
                                                class="form-control @error('ebook_file') is-invalid @enderror"
                                                accept=".pdf,.epub">
                                            @if ($book->pdf_file)
                                                <div class="mt-2">
                                                    <a href="{{ asset($book->pdf_file) }}" target="_blank">Download Current
                                                        Ebook</a>
                                                </div>
                                            @endif
                                            @error('ebook_file')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="file_format" class="form-label">File Format</label>
                                            <select name="file_format" id="file_format"
                                                class="form-control @error('file_format') is-invalid @enderror">
                                                <option value="">Select format</option>
                                                <option value="pdf"
                                                    {{ $book->file_format == 'pdf' ? 'selected' : '' }}>PDF</option>
                                                <option value="epub"
                                                    {{ $book->file_format == 'epub' ? 'selected' : '' }}>EPUB</option>
                                            </select>
                                            @error('file_format')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <hr>

                                    <!-- DESCRIPTION -->
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea name="description" id="description"
                                                class="form-control summernote @error('description') is-invalid @enderror" rows="4">{{ old('description', $book->description) }}</textarea>
                                            @error('description')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- IMAGES -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label for="cover_image" class="form-label">Cover Image</label>
                                            <input type="file" name="cover_image" id="cover_image"
                                                class="dropify form-control @error('cover_image') is-invalid @enderror"
                                                data-height="120">
                                            @error('cover_image')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror

                                            @if ($book->cover_image)
                                                <div class="mt-2">
                                                    <img src="{{ asset($book->cover_image) }}" alt="cover"
                                                        width="120">
                                                </div>
                                            @endif
                                        </div>

                                        <div class="col-md-6">
                                            <label for="images" class="form-label">Additional Images</label>
                                            <input type="file" name="images[]" id="images"
                                                class="form-control @error('images') is-invalid @enderror" multiple>
                                            @error('images')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror

                                            <div id="imagePreview" class="mt-3 d-flex flex-wrap gap-2">
                                                @foreach ($book->book_images as $img)
                                                    <img src="{{ asset($img->image_url) }}" width="80"
                                                        class="border rounded">
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ACTION BUTTONS -->
                                    <div class="form-group mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fe fe-save"></i> Update Book
                                        </button>
                                        <a href="{{ route('admin.book.index') }}" class="btn btn-secondary ms-2">
                                            <i class="fe fe-x-circle"></i> Cancel
                                        </a>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#category_ids').select2({
                placeholder: "Select Categories",
                allowClear: true,
                width: '100%'
            });
        });
    </script>


    <script>
        // Preview Additional Images
        document.getElementById('images').addEventListener('change', function() {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('border', 'rounded');
                    img.style.width = '80px';
                    img.style.height = '80px';
                    img.style.objectFit = 'cover';
                    img.style.padding = '3px';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endpush
