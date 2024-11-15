<template>
  <div class="message-box flex flex-col h-full p-2 sm:p-4">
    <div class="message-header bg-gray-100 p-2 sm:p-4 border-b">
      <h3 class="text-base sm:text-lg font-bold">Chat</h3>
    </div>
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
      messages: [
        { sender: 'me', text: 'Xin chào!' },
        { sender: 'friend', text: 'Chào bạn àkljagl!' },
      ],
      newMessage: '',
      isTyping: false,
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

      // Reset trạng thái "đang gõ" sau 1 giây nếu không có thêm nhập liệu
      clearTimeout(this.typingTimeout);
      this.typingTimeout = setTimeout(() => {
        this.isTyping = false;
      }, 1000);
    },
  },
};
</script>

<style scoped>
.message-box {
  display: flex;
  flex-direction: column;
  height: 100vh; /* Chiếm toàn bộ chiều cao màn hình */
}

.message-content {
  flex: 1;
  overflow-y: auto;
}

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

@media (min-width: 640px) {
  .my-message, .friend-message {
    max-width: 70%;
    font-size: 1rem;
  }
}

.typing-indicator {
  font-style: italic;
  color: gray;
}
</style>
