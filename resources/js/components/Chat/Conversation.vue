<template>
  <div class="conversation-list bg-gray-100 p-2 sm:p-4">
    <h3 class="font-bold text-lg mb-2">Cuộc trò chuyện</h3>
    <ul>
      <li v-for="(conversation, index) in conversations" :key="index" class="conversation-item flex items-center p-2 sm:p-3 border-b cursor-pointer hover:bg-gray-200">
        <!-- Avatar -->
        <img :src="conversation.avatar" alt="Avatar" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full mr-2 sm:mr-3" />

        <!-- Nội dung trò chuyện -->
        <div class="flex-1 max-w-xs">
          <div class="flex justify-between items-center">
            <h4 class="font-semibold text-sm sm:text-base">{{ conversation.name }}</h4>
            <span v-if="conversation.unread > 0" class="unread-count text-xs text-white bg-red-500 rounded-full px-1.5 sm:px-2 py-0.5">
              {{ conversation.unread > 5 ? '5+' : conversation.unread }}
            </span>
          </div>
          <p class="text-gray-500 text-xs sm:text-sm truncate">{{ conversation.lastMessage }}</p>
        </div>

        <!-- Trạng thái online/offline -->
        <span :class="conversation.isOnline ? 'bg-green-500' : 'bg-gray-400'" class="status-dot w-2 h-2 sm:w-3 sm:h-3 rounded-full ml-2 sm:ml-3"></span>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  data() {
    return {
      conversations: [
        { name: 'Người A', avatar: 'https://via.placeholder.com/40', lastMessage: 'Hello!', isOnline: true, unread: 3 },
        { name: 'Người B', avatar: 'https://via.placeholder.com/40', lastMessage: 'Lâu rồi không gặp!', isOnline: false, unread: 5 },
        { name: 'Người C', avatar: 'https://via.placeholder.com/40', lastMessage: 'Bạn khỏe không?', isOnline: true, unread: 1 },
        { name: 'Người D', avatar: 'https://via.placeholder.com/40', lastMessage: 'Hẹn gặp lại sau nhé!', isOnline: false, unread: 6 },
      ],
    };
  },
};
</script>

<style scoped>
.conversation-item {
  display: flex;
  align-items: center;
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.unread-count {
  background-color: red;
  color: white;
  font-weight: bold;
  border-radius: 9999px;
  padding: 2px 6px;
}
</style>
