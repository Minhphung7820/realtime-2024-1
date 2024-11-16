<template>
  <div class="message-box flex flex-col h-full p-2 sm:p-4">
    <!-- Header: Avatar, Tên, và Trạng thái -->
    <div class="message-header bg-gray-100 p-2 sm:p-4 border-b flex items-center">
      <!-- Avatar -->
      <img
        :src="userInfo.avatar"
        alt="Avatar"
        class="w-10 h-10 sm:w-12 sm:h-12 rounded-full mr-3"
      />
      <!-- Tên và Trạng thái -->
      <div>
        <h3 class="text-base sm:text-lg font-bold">{{ userInfo.name }}</h3>
        <div class="flex items-center">
          <span
            :class="userInfo.isOnline ? 'bg-green-500' : 'bg-gray-500'"
            class="w-3 h-3 rounded-full mr-1"
          ></span>
          <span class="text-sm sm:text-base text-gray-600">
            {{ userInfo.isOnline ? 'Đang hoạt động' : `Online ${userInfo.lastOnline}` }}
          </span>
        </div>
      </div>
    </div>

    <!-- Content: Tin nhắn -->
    <div class="message-content flex-1 overflow-y-auto p-2">
      <div v-for="(msg, index) in messages" :key="index" class="mb-2">
        <!-- Sử dụng class dựa trên sender -->
        <div :class="msg.sender === 'me' ? 'my-message-container' : 'friend-message-container'">
          <div :class="msg.sender === 'me' ? 'my-message' : 'friend-message'">
            <p>{{ msg.text }}</p>
          </div>
        </div>
      </div>

      <!-- Hiển thị trạng thái đang gõ -->
      <div v-if="isTyping" class="typing-indicator text-gray-500 italic mt-2">
        Người bên kia đang gõ...
      </div>
    </div>

    <!-- Input: Nhập và gửi tin nhắn -->
    <div class="message-input mt-2 flex items-center">
      <input
        v-model="newMessage"
        @input="onTyping"
        type="text"
        placeholder="Nhập tin nhắn..."
        class="flex-1 p-2 sm:p-3 border rounded text-sm sm:text-base"
      />
      <button @click="sendMessage" class="ml-2 px-2 sm:px-4 py-1 sm:py-2 bg-blue-500 text-white rounded text-sm sm:text-base">
        Gửi
      </button>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      userInfo: {
        name: 'Nguyễn Văn A', // Tên người dùng
        avatar: 'https://i.vgt.vn/2023/9/12/hotgirl-tran-ha-linh-tuyen-bo-so-dan-ong-hau-bi-ban-trai-cu-tung-clip-co-khi-toi-dong-tinh-0b2-6980241.png', // Đường dẫn đến avatar
        isOnline: false, // Trạng thái online (true: online, false: offline)
        lastOnline: '3 phút trước', // Thời gian online gần nhất nếu offline
      },
      messages: [
        { sender: 'me', text: 'Xin chào!' },
        { sender: 'friend', text: 'Chào bạn àkljagl!' },
      ],
      newMessage: '', // Tin nhắn mới
      isTyping: false, // Trạng thái đang gõ
    };
  },
  methods: {
    sendMessage() {
      if (this.newMessage.trim() !== '') {
        this.messages.push({ sender: 'me', text: this.newMessage });
        this.newMessage = '';
        this.isTyping = false;
      }
    },
    onTyping() {
      this.isTyping = true;
      clearTimeout(this.typingTimeout);
      this.typingTimeout = setTimeout(() => {
        this.isTyping = false;
      }, 1000);
    },
  },
};
</script>

<style scoped>
/* Chiều cao toàn bộ màn hình */
.message-box {
  display: flex;
  flex-direction: column;
  height: 100vh;
}

/* Nội dung tin nhắn */
.message-content {
  flex: 1;
  overflow-y: auto;
}

/* Nhập tin nhắn */
.message-input {
  margin-top: auto;
}

/* Container của tin nhắn của người dùng để căn phải */
.my-message-container {
  display: flex;
  justify-content: flex-end;
}

/* Container của tin nhắn của người khác để căn trái */
.friend-message-container {
  display: flex;
  justify-content: flex-start;
}

/* Tin nhắn của tôi */
.my-message {
  background-color: #d1e7ff;
  padding: 6px 8px;
  border-radius: 8px;
  display: inline-block;
  max-width: 85%;
  text-align: right;
  word-wrap: break-word;
  font-size: 0.875rem;
}

/* Tin nhắn của bạn bè */
.friend-message {
  background-color: #e2e3e5;
  padding: 6px 8px;
  border-radius: 8px;
  display: inline-block;
  max-width: 85%;
  text-align: left;
  word-wrap: break-word;
  font-size: 0.875rem;
}

/* Kích thước lớn hơn trên màn hình rộng */
@media (min-width: 640px) {
  .my-message,
  .friend-message {
    max-width: 70%;
    font-size: 1rem;
  }
}

/* Chấm trạng thái */
.bg-green-500 {
  background-color: #22c55e;
}

.bg-gray-500 {
  background-color: #6b7280;
}

/* Avatar */
.message-header img {
  border-radius: 50%;
  object-fit: cover;
}

/* Hiển thị trạng thái đang gõ */
.typing-indicator {
  font-style: italic;
  color: gray;
}
</style>
