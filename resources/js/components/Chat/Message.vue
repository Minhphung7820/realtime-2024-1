<template>
  <div v-if="isLoading" class="loading-container">
     <div class="spinner"></div>
  </div>
  <div v-else class="message-box flex flex-col h-full p-2 sm:p-4">
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
            {{ userInfo.isOnline ? 'Đang hoạt động' : `${userInfo.lastOnlineString}` }}
          </span>
        </div>
      </div>
    </div>

      <div ref="messageContent" class="message-content flex-1 overflow-y-auto p-2">
        <div v-for="(msg, index) in messages" :key="index" class="mb-2">
          <div :class="msg.sender === 'me' ? 'my-message-container' : 'friend-message-container'">
            <div :class="msg.sender === 'me' ? 'my-message' : 'friend-message'">
              <p>{{ msg.content }}</p>
          </div>
        </div>
      </div>
      <!-- Hiển thị trạng thái đang gõ -->
      <div v-if="isFriendTyping " class="typing-indicator text-gray-500 italic mt-2">
        Người bên kia đang gõ...
      </div>
    </div>

    <!-- Input: Nhập và gửi tin nhắn -->
    <div class="message-input mt-2 flex items-center">
      <input
        v-model="newMessage"
        @keydown="sendTypingEvent"
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
  inject: ['$axios','$userProfile','$socket'],
  props:{
      dataMessage:{
        type:Object,
        required: true
      }
  },
  data() {

    return {
      userInfo: {
        id : null,
        name: '', // Tên người dùng
        avatar: 'https://st3.depositphotos.com/1767687/16607/v/450/depositphotos_166074422-stock-illustration-default-avatar-profile-icon-grey.jpg', // Đường dẫn đến avatar
        isOnline: false, // Trạng thái online (true: online, false: offline)
        lastOnline: '', // Thời gian online gần nhất nếu offline
        lastOnlineString: '',
        conversation_id : null
      },
      messages : [],
      updateLastActiveFriendInterval : null,
      newMessage: '', // Tin nhắn mới
      isFriendTyping : false, // Trạng thái đang gõ
      isFriendTypingTimer : null,
      isLoading : true,
      socket :null
    };
  },
  async mounted(){
     this.socket = this.$socket;
     await this.getConversation();
     await this.getStatusUserOnline();
     await this.getMessage();
     this.socket.on('user_list',this.handleUserWithStatusFromSocket);
     this.socket.on('user_disconnect_list', this.handleUserWithStatusFromSocket);
     this.socket.emit('join_conversation', this.userInfo.conversation_id);

    this.socket.on('receive_message', (e) => {
      if (parseInt(e.conversation_id) === parseInt(this.userInfo.conversation_id)) {
         const sender = parseInt(e.sender_id) === parseInt(this.$userProfile.id) ? 'me' : 'friend';
         this.messages.unshift({sender, content : e.content});
      }
    });

    this.socket.on('typing', (e) => {
      if (parseInt(e.conversation_id) === parseInt(this.userInfo.conversation_id)) {
         this.isFriendTyping = parseInt(e.typewriter_id) === parseInt(this.userInfo.id);

         if (this.isFriendTypingTimer) {
               clearTimeout(this.isFriendTypingTimer);
         }

         this.isFriendTypingTimer = setTimeout(() => {
                this.isFriendTyping = false;
         }, 500);
      }
    });
    this.updateLastActiveFriendInterval = setInterval(() => {
        this.updateLastActiveFriendConversation();
    }, 1000);
    this.isLoading = false;
  },
  beforeUnmount() {
    // Dừng interval khi component bị hủy
    if(this.socket && this.userInfo.conversation_id){
      this.socket.emit('leave_conversation', this.userInfo.conversation_id);
    }
    clearInterval(this.updateLastActiveFriendInterval);
  },
  watch: {
    dataMessage: {
      immediate: true, // Gọi ngay lần đầu khi component được mount
      async handler(newData) {
        if (newData) {
          // Reset dữ liệu
          if(this.socket && this.userInfo.conversation_id){
            this.socket.emit('leave_conversation', this.userInfo.conversation_id);
            this.socket = null;
          }
          this.isLoading = true;
          this.messages = [];
          this.userInfo = {
            id: null,
            name: '',
            avatar: 'https://st3.depositphotos.com/1767687/16607/v/450/depositphotos_166074422-stock-illustration-default-avatar-profile-icon-grey.jpg',
            isOnline: false,
            lastOnline: '',
            lastOnlineString: '',
            conversation_id : null
          };
          this.socket = this.$socket;
          await this.getConversation();
          await this.getStatusUserOnline();
          await this.getMessage();
          this.socket.emit('join_conversation', this.userInfo.conversation_id);
          this.isLoading = false;
        }
      },
    },
  },
  methods: {
    async getConversation()
    {
      const id = this.dataMessage.id;
      const type = this.dataMessage.type;
      try {
        const response = await this.$axios.get(`/api/get-detail-conversation?id=${id}&type=${type}`);
        this.userInfo.id = response.data.id;
        this.userInfo.avatar = response.data.avatar;
        this.userInfo.name = response.data.name;
        this.userInfo.lastOnline = response.data.last_active;
        this.userInfo.conversation_id = response.data.conversation_id;
        console.log(response.data.conversation_id);

        this.userInfo.lastOnlineString = this.formatTimeDifference(response.data.last_active);
      } catch (error) {
        console.log("GET DATA FAILED : ",error);
      }
     },
     async getStatusUserOnline() {
        try {
          const response = await this.$axios.get(`http://localhost:6060/api/online-users`);
          const onlineUsers = response.data.data;
          this.userInfo.isOnline = onlineUsers.some(item => parseInt(item.userID) === parseInt(this.userInfo.id));
        } catch (error) {
          console.error('Failed to fetch online users:', error);
        }
    },
    handleUserWithStatusFromSocket(user){
        if(parseInt(user.userID) === parseInt(this.userInfo.id)){
          this.userInfo.isOnline = user.online;
          this.userInfo.lastOnline = user.last_active;
          this.userInfo.lastOnlineString = this.formatTimeDifference(user.last_active);
        }
    },
    formatTimeDifference(lastActive) {
      const now = new Date();
      const lastActiveDate = new Date(lastActive);
      const diffSeconds = Math.floor((now - lastActiveDate) / 1000); // Chênh lệch giây

      if (diffSeconds < 60) {
        return 'Vừa truy cập';
      } else if (diffSeconds < 3600) {
        const minutes = Math.floor(diffSeconds / 60);
        return `${minutes} phút trước`;
      } else if (diffSeconds < 86400) {
        const hours = Math.floor(diffSeconds / 3600);
        return `${hours} giờ trước`;
      } else if (diffSeconds < 604800) {
        const days = Math.floor(diffSeconds / 86400);
        return `${days} ngày trước`;
      } else if (diffSeconds < 2592000) {
        const weeks = Math.floor(diffSeconds / 604800);
        return `${weeks} tuần trước`;
      } else {
        const months = Math.floor(diffSeconds / 2592000);
        return `${months} tháng trước`;
      }
    },
    scrollToBottom() {
    const messageContent = this.$refs.messageContent;
        if (messageContent) {
          messageContent.scrollTop = messageContent.scrollHeight;
        }
    },
    async getMessage(){
        const id = this.dataMessage.id;
        const type = this.dataMessage.type;
        try {
             const response = await this.$axios.get(`/api/get-message?id=${id}&type=${type}`);
             this.messages = response.data.data;
             this.scrollToBottom(); // Cuộn xuống cuối cùng
        } catch (error) {
             console.log("Get Failed With Message :".error);
        }
    },
   async sendMessage() {
      if (this.newMessage.trim() !== '') {
         try {
             await this.$axios.post(`/api/save-message`,{
                   conversation_id : this.userInfo.conversation_id,
                   content: this.newMessage,
                   type : 'text'
             });
             this.socket.emit(`send_message`,{
               conversation_id : this.userInfo.conversation_id,
               sender_id : this.$userProfile.id,
               content: this.newMessage
             });
        this.newMessage = '';
         } catch (error) {
              console.log("GET DATA FAILED : ",error);
         }
      }
    },
    sendTypingEvent() {
        this.socket.emit(`typing`,{
           conversation_id : this.userInfo.conversation_id, typewriter_id : this.$userProfile.id
        });
    },
    updateLastActiveFriendConversation(){
       if(!this.userInfo.isOnline && this.userInfo.lastOnline){
          this.userInfo.lastOnlineString = this.formatTimeDifference(this.userInfo.lastOnline);
       }
    }
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

.message-content {
  display: flex;
  flex-direction: column-reverse;
  justify-content: flex-end; /* Căn nội dung xuống cuối */
  flex: 1;
  overflow-y: auto; /* Cho phép cuộn nếu nội dung vượt quá chiều cao */
  padding: 1rem;
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
