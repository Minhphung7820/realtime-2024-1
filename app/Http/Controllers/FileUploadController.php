<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    /**
     * Upload file trực tiếp vào thư mục public/uploads/$folder.
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file', // File được upload
            'folder' => 'required|string' // Tên folder trong public/uploads
        ]);

        // Lấy thông tin folder và file
        $folder = $request->input('folder');
        $file = $request->file('file');

        // Tạo đường dẫn folder đầy đủ
        $destinationPath = public_path("uploads/$folder");

        // Đảm bảo thư mục tồn tại
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true); // Tạo thư mục nếu chưa tồn tại
        }

        // Tạo tên file duy nhất
        $fileName = time() . '_' . md5(rand(0, 99999)) . '_' . uniqid() . '_encrypted_' . $file->getClientOriginalName();

        // Di chuyển file vào thư mục public/uploads/$folder
        $file->move($destinationPath, $fileName);

        // Tạo URL công khai của file
        $url = asset("uploads/$folder/$fileName");

        // Trả về JSON response
        return response()->json([
            'message' => 'File uploaded successfully',
            'url' => $url,
        ]);
    }

    public function readFile(Request $request)
    {
        $fileName = '1733045762_efd33731692792594017105dc0931699_674c2e021b6de_encrypted_blob';
        $filePath = public_path('uploads/videos/' . $fileName);

        if (file_exists($filePath)) {
            return response()->file($filePath);
        } else {
            return response()->json(['message' => 'File không tồn tại.'], 404);
        }
    }
}
