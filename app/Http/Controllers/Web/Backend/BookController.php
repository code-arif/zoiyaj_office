<?php
namespace App\Http\Controllers\Web\Backend;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookImage;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class BookController extends Controller
{

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $data = Book::with(['book_categories.category'])->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('cover_image', function ($data) {
                    if ($data->cover_image) {
                        $url = asset($data->cover_image);
                        return '<img src="' . $url . '" alt="image" width="50px" height="50px" style="margin-left:20px;">';
                    } else {
                        return '<img src="' . asset('default/logo.png') . '" alt="image" width="50px" height="50px" style="margin-left:20px;">';
                    }
                })

                ->addColumn('ebook', function ($data) {
                    if ($data->pdf_file) {
                        $url = asset($data->pdf_file);
                        return '<a href="' . $url . '" target="_blank">Download</a>';
                    }
                    return 'N/A';
                })

                ->addColumn('category', function ($data) {
                    return e($data->book_categories->pluck('category.title')->join(', ') ?: 'N/A');

                })

                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">

                                <a href="#" type="button" onclick="goToEdit(' . $data->id . ')" class="btn ms-2 btn-primary fs-14 text-white delete-icn" title="Edit">
                                    <i class="fe fe-edit"></i>
                                </a>



                                <a href="#" type="button" onclick="showDeleteConfirm(' . $data->id . ')" class="btn btn-danger ms-3 fs-14 text-white delete-icn" title="Delete">
                                    <i class="fe fe-trash"></i>
                                </a>
                            </div>';
                })
                ->rawColumns(['cover_image', 'ebook', 'category', 'action'])
                ->make();
        }
        return view("backend.layouts.book.index");
    }

    public function create(Request $request)
    {
        $categories = Category::all();

        return view('backend.layouts.book.create', compact('categories'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'title'          => 'required|string|max:255',
            'author'         => 'required|string|max:255',
            'category_ids'   => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'isbn'           => 'nullable|string|unique:books,isbn',
            'description'    => 'nullable|string',
            'is_premium'     => 'nullable|boolean',
            'published_at'   => 'nullable|date',
            'cover_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'images.*'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'ebook_file'     => 'nullable|file|mimes:pdf,epub|max:10240',
            'file_format'    => 'nullable|in:pdf,epub',
        ]);

        // Cover Image
        $cover_image = null;
        if ($request->hasFile('cover_image')) {
            $cover_image = Helper::uploadImage($request->file('cover_image'), 'books');
        }

        // Ebook File
        $ebook_file = null;

        if ($request->hasFile('ebook_file')) {
            $ebook_file = Helper::fileUpload($request->file('ebook_file'), 'ebook_file', time() . '_' . $request->file('ebook_file'));
        }
        // dd($ebook_file);

        $admin = User::where('role', 'admin')->first();

        $book = Book::create([
            'title'        => $request->title,
            'slug' => Str::slug($request->title),
            'author'       => $request->author,
            'category_ids' => json_encode($request->category_ids), // store as JSON
            'isbn'         => $request->isbn,
            'description'  => $request->description,
            'cover_image'  => $cover_image,
            'pdf_file'     => $ebook_file,
            'is_premium'   => $request->has('is_premium') ? 1 : 0,
            'published_at' => $request->published_at,
            'uploaded_by'  =>  $admin->id ,
            'type'         => 'ebook',
            'status'       => 'draft',
            'price'        => 0, // adjust if needed
            'stock'        => 0, // adjust if needed
        ]);

        // Additional Images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $image_path = Helper::uploadImage($img, 'books');
                BookImage::create([
                    'book_id'   => $book->id,
                    'image_url' => $image_path,
                ]);
            }
        }

        if (! empty($request->category_ids)) {
            foreach ($request->category_ids as $categoryId) {
                BookCategory::create([
                    'book_id'     => $book->id,
                    'category_id' => $categoryId,
                ]);
            }
        }

        return redirect()->route('admin.book.index')
            ->with('success', 'Book saved successfully!');
    }

    public function show($id)
    {
        $category = Category::findOrFail($id);
        return view('backend.layouts.book.edit', compact('category'));
    }

    public function edit($id)
    {
        $book       = Book::with(['book_categories'])->findOrFail($id);
        $categories = Category::all(); // For category selection

        return view('backend.layouts.book.edit', compact('book', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $book = Book::findOrFail($id);

        // Validate
        $request->validate([
            'title'          => 'required|string|max:255',
            'author'         => 'required|string|max:255',
            'category_ids'   => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'isbn'           => 'nullable|string|unique:books,isbn,' . $book->id,
            'cover_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'ebook_file'     => 'nullable|mimes:pdf,epub|max:10240',
            'images.*'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'published_at'   => 'nullable|date',
            'is_premium'     => 'nullable|boolean',
            'description'    => 'nullable|string',
            'file_format'    => 'nullable|in:pdf,epub',
        ]);

        // Update basic info
        $book->title        = $request->title;
        $book->slug         = Str::slug($request->title);
        $book->author       = $request->author;
        $book->isbn         = $request->isbn;
        $book->description  = $request->description;
        $book->is_premium   = $request->has('is_premium') ? 1 : 0;
        $book->published_at = $request->published_at;
        // $book->file_format  = $request->file_format;

        // Update cover image
        if ($request->hasFile('cover_image')) {
            if ($book->cover_image && file_exists(public_path($book->cover_image))) {
                unlink(public_path($book->cover_image));
            }
            $book->cover_image = Helper::uploadImage($request->file('cover_image'), 'books'); // Assign path to model
        }

        // Update ebook file
        if ($request->hasFile('ebook_file')) {
            if ($book->pdf_file && file_exists(public_path($book->pdf_file))) {
                unlink(public_path($book->pdf_file));
            }
            $book->pdf_file = Helper::fileUpload(
                $request->file('ebook_file'),
                'books/ebook',                                                       // folder
                time() . '_' . $request->file('ebook_file')->getClientOriginalName() // filename
            );
        }

        $book->save();

        // Sync categories
        $book->book_categories()->delete();
        if (! empty($request->category_ids)) {
            foreach ($request->category_ids as $categoryId) {
                BookCategory::create([
                    'book_id'     => $book->id,
                    'category_id' => $categoryId,
                ]);
            }
        }
        // Handle additional images
        // if ($request->hasFile('images')) {
        //     foreach ($request->file('images') as $image) {
        //         $path = $image->store('uploads/books/images', 'public');
        //         $book->book_images()->create([
        //             'image_url' => 'storage/' . $path,
        //         ]);
        //     }
        // }

        // Additional Images
        if ($request->hasFile('images')) {

            $book->book_images()->delete(); // Remove existing images
            foreach ($request->file('images') as $img) {
                $image_path = Helper::uploadImage($img, 'books');
                BookImage::create([
                    'book_id'   => $book->id,
                    'image_url' => $image_path,
                ]);
            }
        }

        return redirect()->route('admin.book.index')
            ->with('success', 'Book updated successfully.');
    }

    public function destroy($id)
    {
        try {
            $book = Book::with(['book_images', 'book_categories'])->findOrFail($id);

            // Delete cover image
            if ($book->cover_image && file_exists(public_path($book->cover_image))) {
                Helper::fileDelete(public_path($book->cover_image));
            }

            // Delete ebook file
            if ($book->pdf_file && file_exists(public_path($book->pdf_file))) {
                Helper::fileDelete(public_path($book->pdf_file));
            }

            // Delete additional images
            if ($book->book_images) {
                foreach ($book->book_images as $image) {
                    if ($image->image_url && file_exists(public_path($image->image_url))) {
                        Helper::fileDelete(public_path($image->image_url));
                    }
                    $image->delete(); // Remove image record
                }
            }

            // Delete category relationships
            $book->book_categories()->delete();

            // Delete the book
            $book->delete();

            return response()->json([
                'success' => true,
                'message' => 'Book deleted successfully!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete book: ' . $e->getMessage(),
            ], 500);
        }
    }

}
