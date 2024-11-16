// plugins/socket.js
import { io } from "socket.io-client";

// Hàm khởi tạo socket, chỉ kết nối khi người dùng đã đăng nhập
export function initializeSocket(userID, options = {}) {
  return io("http://localhost:6060", {
    query: {
      userID: userID,
      ...options, // Truyền thêm các trường từ `options` (nếu có)
    }
  });
}