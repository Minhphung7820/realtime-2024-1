<template>
  <div class="chat-container container mx-auto max-w-5xl flex h-screen p-4 bg-gray-50 rounded-lg shadow-lg">
    <!-- Cột trái (Conversation + People) -->
    <div class="left-pane w-1/3 border-r border-gray-300 pr-4 overflow-y-auto">
      <Conversation />
      <People />
    </div>

    <!-- Cột phải (Message) -->
    <div class="right-pane w-2/3 pl-4 flex flex-col">
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
};
</script>

<style scoped>
.chat-container {
  display: flex;
  flex-direction: row;
  background-color: #f9fafb; /* Màu nền nhẹ để tạo cảm giác tách biệt */
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); /* Thêm shadow để nổi bật */
}

.left-pane {
  padding-right: 1rem; /* Tạo khoảng cách giữa các cột */
  border-right: 1px solid #e5e7eb; /* Đường viền nhẹ để tách biệt các cột */
  overflow-y: auto;
}

.right-pane {
  padding-left: 1rem; /* Tạo khoảng cách giữa các cột */
  display: flex;
  flex-direction: column;
}

.right-pane .message-header {
  background-color: #f1f5f9;
  padding: 1rem;
  font-size: 1.25rem;
  font-weight: bold;
  text-align: center;
  border-bottom: 1px solid #e5e7eb;
}

.left-pane h3,
.right-pane h3 {
  font-size: 1.25rem;
  font-weight: bold;
}

.left-pane ul,
.right-pane ul {
  padding: 0;
  margin: 0;
  list-style: none;
}
</style>
