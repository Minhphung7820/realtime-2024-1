<template>
  <div class="chat-container container mx-auto max-w-5xl flex h-screen p-2 sm:p-4 bg-gray-50 rounded-lg shadow-lg">
    <!-- Hiển thị Conversation và People trên màn hình nhỏ, ẩn khi đang xem Message -->
    <div v-if="!isChatOpen || isDesktop" class="left-pane w-full sm:w-1/2 md:w-1/3 border-r border-gray-300 pr-4 overflow-y-auto">
      <Conversation @open-chat="openChat" />
      <People />
    </div>

    <!-- Hiển thị Message component khi đang xem chat hoặc trên màn hình lớn -->
    <div v-if="isChatOpen || isDesktop" class="right-pane w-full sm:w-1/2 md:w-2/3 pl-4 flex flex-col">
      <!-- Nút quay lại trên thiết bị di động -->
      <button v-if="!isDesktop" @click="closeChat" class="p-2 text-blue-500 font-semibold flex items-center mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707a1 1 0 00-1.414-1.414L9 11.586 7.707 10.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        Quay lại
      </button>
      <Message />
    </div>
  </div>
</template>

<script>
import Conversation from './Conversation.vue';
import People from './People.vue';
import Message from './Message.vue';

export default {
  components: {
    Conversation,
    People,
    Message,
  },
  data() {
    return {
      isChatOpen: false, // Trạng thái để kiểm soát hiển thị Message
      isDesktop: window.innerWidth >= 640, // Kiểm tra màn hình có phải desktop
    };
  },
  methods: {
    openChat() {
      this.isChatOpen = true; // Mở Message component
    },
    closeChat() {
      this.isChatOpen = false; // Quay lại Conversation và People
    },
    handleResize() {
      this.isDesktop = window.innerWidth >= 640;
      if (this.isDesktop) {
        this.isChatOpen = false; // Reset trạng thái khi chuyển sang màn hình desktop
      }
    },
  },
  mounted() {
    window.addEventListener('resize', this.handleResize);
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.handleResize);
  },
};
</script>

<style scoped>
.chat-container {
  display: flex;
  flex-direction: column;
  background-color: #f9fafb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

@media (min-width: 640px) {
  .chat-container {
    flex-direction: row;
  }
}

.left-pane {
  padding-right: 1rem;
  border-right: 1px solid #e5e7eb;
  overflow-y: auto;
}

.right-pane {
  padding-left: 1rem;
  display: flex;
  flex-direction: column;
}
</style>
