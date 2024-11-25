<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    /**
     * Upload ảnh hoặc file vào folder chỉ định.
     */
    public function uploadFile(Request $request)
    {
        // Xác thực dữ liệu từ request
        $request->validate([
            'file' => 'required|file|max:20000000', // File tối đa 20MB
            'folder' => 'required|string' // Tên folder là bắt buộc
        ]);

        // Lấy folder từ request
        $folder = trim($request->input('folder'));

        // Đảm bảo tên folder hợp lệ
        if (preg_match('/[^a-zA-Z0-9_\-\/]/', $folder)) {
            return response()->json([
                'message' => 'Invalid folder name. Only alphanumeric characters, underscores, dashes, and slashes are allowed.'
            ], 400);
        }

        // Đường dẫn đầy đủ của folder
        $folderPath = public_path('uploads/' . $folder);

        // Tạo folder nếu chưa tồn tại
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true); // Tạo folder và các folder con nếu cần
        }

        // Lưu file với tên mới (thời gian + tên gốc)
        $file = $request->file('file');
        $newFileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($folderPath, $newFileName);

        // Tạo URL cho file đã lưu
        $url = asset('uploads/' . $folder . '/' . $newFileName);

        // Trả về đường dẫn file cho frontend
        return response()->json([
            'message' => 'File uploaded successfully',
            'url' => $url
        ]);
    }
}
