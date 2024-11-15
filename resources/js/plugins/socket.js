// plugins/socket.js
import { io } from "socket.io-client";

// Hàm khởi tạo socket, chỉ kết nối khi người dùng đã đăng nhập
export function initializeSocket(userID) {
  return io("http://localhost:6060", {
    query: {
      userID: userID,
    }
  });
}