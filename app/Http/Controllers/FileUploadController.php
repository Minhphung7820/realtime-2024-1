<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    /**
     * Upload ảnh và trả về link ảnh.
     */
    public function uploadImage(Request $request)
    {
        // Xác thực file upload
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:20000000' // Chỉ cho phép các file ảnh với kích thước tối đa 10MB
        ]);

        // Lưu file trực tiếp vào thư mục 'public/uploads' và giữ nguyên tên gốc
        $fileName = time() . '_' . $request->file('image')->getClientOriginalName();
        $request->file('image')->move(public_path('uploads'), $fileName);

        // Tạo URL cho file đã lưu
        $url = asset('uploads/' . $fileName);

        // Trả về đường dẫn của file đã upload
        return response()->json([
            'message' => 'Image uploaded successfully',
            'url' => $url
        ]);
    }
}
