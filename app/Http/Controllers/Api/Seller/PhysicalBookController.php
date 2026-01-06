<?php
namespace App\Http\Controllers\Api\Seller;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookImage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PhysicalBookController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = auth('api')->user();

        $books = Book::where('uploaded_by', $user->id)
            ->where('type', 'physical')
            ->with('book_categories', 'book_images')
            ->get();

        return $this->success($books, 'Physical books retrieved successfully.');
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'title'          => 'required|string|max:255',
            'author'         => 'required|string|max:255',
            'category_ids'   => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'isbn'           => 'nullable|string|unique:books,isbn',
            'description'    => 'nullable|string',
            'published_at'   => 'nullable|date',
            'cover_image'    => 'nullable|image|',
            'images'         => 'nullable|array|max:5',
            'images.*'       => 'image',
            'price' => 'required|numeric|min:0',
            'shipping_cost' => 'required|numeric|min:0',


        ]);

        // if user not connect in the stripe account






        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 404);
        }

        // Cover Image
        $cover_image = null;
        if ($request->hasFile('cover_image')) {
            $cover_image = Helper::uploadImage($request->file('cover_image'), 'books');
        }

        $book = Book::create([
            'title'        => $request->title,
            'slug'         => Str::slug($request->title),
            'author'       => $request->author,
            'category_ids' => json_encode($request->category_ids), // store as JSON
            'isbn'         => $request->isbn,
            'description'  => $request->description,
            'condition'    => $request->condition,
            'weight_gram'  => $request->weight_gram,
            'cover_image'  => $cover_image,
            'published_at' => $request->published_at,
            'uploaded_by'  => auth('api')->id(),
            'type'         => 'physical',
            'status'       => 'draft',
            'price'        => $request->price ?? 0,
            'shipping_cost'        => $request->shipping_cost ?? 0,

            'stock'        => $request->stock ?? 0,
        ]);

        // additional Images
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

        $book->load('book_categories', 'book_images');

        return $this->success($book, 'Physical book created successfully.');
    }

    // book edit

    public function edit($id)
    {
        $user = auth('api')->user();

        $book = Book::where('id', $id)
            ->where('uploaded_by', $user->id)
            ->where('type', 'physical')
            ->with('book_categories.category', 'book_images')
            ->first();

        if (! $book) {
            return $this->error([], 'Book not found.', 404);
        }

        return $this->success($book, 'Physical book retrieved successfully for editing.');
    }

    // book update
    public function update(Request $request)
    {

        $user = auth('api')->user();
        $id   = $request->book_id;


        $book = Book::where('id', $id)
            ->where('uploaded_by', $user->id)
            ->where('type', 'physical')
            ->first();

        if (! $book) {
            return $this->error([], 'Book not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'title'          => 'sometimes|required|string|max:255',
            'author'         => 'sometimes|required|string|max:255',
            'category_ids'   => 'sometimes|required|array',
            'category_ids.*' => 'exists:categories,id',
            'isbn'           => 'sometimes|nullable|string|unique:books,isbn,' . $book->id,
            'description'    => 'sometimes|nullable|string',
            'published_at'   => 'sometimes|nullable|date',
            'cover_image'    => 'sometimes|nullable|image|',
            'images'         => 'sometimes|nullable|array|max:5',
            'images.*'       => 'image',

        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 404);
        }

        // Update Cover Image
        if ($request->hasFile('cover_image')) {

            $cover_image       = Helper::uploadImage($request->file('cover_image'), 'books');
            $book->cover_image = $cover_image;
            $book->save();
        }

        // Update other fields
        $book->update($request->only([
            'title',
            'author',
            'isbn',
            'description',
            'published_at',
            'price',
            'stock',
            'condition',
            'weight_gram',
        ]));

        // Update Categories
        if ($request->has('category_ids')) {
            // Remove existing categories
            BookCategory::where('book_id', $book->id)->delete();
            // Add new categories
            foreach ($request->category_ids as $categoryId) {
                BookCategory::create([
                    'book_id'     => $book->id,
                    'category_id' => $categoryId,
                ]);
            }

        }

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

        $book->load('book_categories.category', 'book_images');

        return $this->success($book, 'Physical book updated successfully.');

    }

    // book delete
    public function destroy($id)
    {
        $user = auth('api')->user();

        $book = Book::where('id', $id)
            ->where('uploaded_by', $user->id)
            ->where('type', 'physical')
            ->first();

        if (! $book) {
            return $this->error([], 'Book not found.', 404);

        }

        $book->book_images()->delete();
        $book->book_categories()->delete();
        $book->delete();


        return $this->success([], 'Physical book deleted successfully.');

    }

    // single book image delete
    public function deleteImage($id)
    {

        $bookImage = BookImage::find($id);

        // dd($bookImage);

        if (! $bookImage) {
            return $this->error([], 'Book image not found.', 404);
        }

        $bookImage->delete();

        return $this->success([], 'Book image deleted successfully.');

    }

}
