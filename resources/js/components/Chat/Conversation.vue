<template>
  <div class="conversation-list bg-gray-100 p-4">
    <h3 class="font-bold text-lg mb-2">Cuộc trò chuyện</h3>
    <ul>
      <li
        v-for="(conversation, index) in conversations"
        :key="index"
        class="conversation-item flex items-center p-3 border-b cursor-pointer hover:bg-gray-200"
        @click="openChat(conversation.id,'private')"
      >
        <!-- Avatar -->
        <img :src="conversation.avatar" alt="Avatar" class="w-10 h-10 rounded-full mr-3" />

        <!-- Nội dung trò chuyện -->
        <div class="flex-1">
          <div class="flex justify-between items-center">
            <h4 class="font-semibold">{{ conversation.name }}</h4>
            <!-- Hiển thị số tin nhắn chưa đọc -->
            <span v-if="conversation.unread > 0" class="unread-count text-xs text-white bg-red-500 rounded-full px-2 py-0.5">
              {{ conversation.unread > 5 ? '5+' : conversation.unread }}
            </span>
          </div>
          <p class="text-gray-500 text-sm truncate">{{ conversation.lastMessage }}</p>
        </div>

        <!-- Trạng thái online/offline -->
        <span :class="conversation.isOnline ? 'bg-green-500' : 'bg-gray-400'" class="status-dot w-3 h-3 rounded-full ml-3"></span>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  inject: ['$axios','$userProfile','$socket'],
  data() {
    return {
      conversations: [],
    };
  },
  async mounted()
  {
     await this.getConversations();
     await this.fetchOnlineUsers();
    this.$socket.on('user_list',this.handleUserWithStatus);
    this.$socket.on('user_disconnect_list', this.handleUserWithStatus);
  },
  methods: {
    openChat(userId, type) {
      if (this.$parent.dataMessage.id === userId && this.$parent.dataMessage.type === type) {
        // Nếu người dùng đang mở chính họ, không làm gì cả
        return;
      }
      this.$emit('open-chat', userId, type); // Phát sự kiện open-chat lên cha
    },
    async fetchOnlineUsers() {
        try {
          const response = await this.$axios.get('http://localhost:6060/api/online-users');
          const onlineUsers = response.data.data;

          // Cập nhật trạng thái online vào mảng people
          onlineUsers.forEach(user => {
            const matchingPerson = this.conversations.find(person => person.id === parseInt(user.userID)
            && person.type ==='private');
            if (matchingPerson) {
              matchingPerson.isOnline = user.isOnline;
            }
          });
        } catch (error) {
          console.error('Failed to fetch online users:', error);
        }
    },
    async getConversations()
    {
       try {
         const response = await this.$axios.get(`/api/get-list-conversation`);
         this.conversations = response.data;
       } catch (error) {
        console.log("GET DATA FAILED WITH ERROR : ",error);

       }
    },
    handleUserWithStatus(user) {
      const matchingPerson = this.conversations.find(person => person.id === parseInt(user.userID)
      && person.type ==='private');
      if (matchingPerson) {
        matchingPerson.isOnline = user.online;
      }
    },
  }
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
