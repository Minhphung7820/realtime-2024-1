<template>
<div class="message-box flex flex-col h-full p-2 sm:p-4">
  <!-- Header: Avatar, Tên, và Trạng thái -->
  <div class="message-header bg-gray-100 p-2 sm:p-4 border-b flex items-center flex-shrink-0">
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
  <div v-if="isLoading" class="loading-container">
      <div class="spinner"></div>
  </div>
  <!-- Phần tin nhắn (có cuộn) -->
  <div v-else ref="messageContent" class="message-content flex-1 overflow-y-auto p-2">
    <div v-for="(msg, index) in messages" :key="index" class="mb-2">
      <div :class="msg.sender === 'me' ? 'my-message-container' : 'friend-message-container'">
        <div :class="msg.sender === 'me' ? 'my-message' : 'friend-message'">
          <p>{{ msg.content }}</p>
        </div>
      </div>
    </div>
    <!-- Hiển thị trạng thái đang gõ -->
  </div>
  <div v-if="isFriendTyping" class="typing-indicator text-gray-500 italic mt-2">
      Người bên kia đang gõ...
  </div>
  <!-- Input: Nhập và gửi tin nhắn -->
  <div class="message-input mt-2 flex items-center flex-shrink-0">
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
         this.scrollToBottom();
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
      // this.socket.emit('leave_conversation', this.userInfo.conversation_id);
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
            // this.socket.emit('leave_conversation', this.userInfo.conversation_id);
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
      this.$nextTick(() => {
        const messageContent = this.$refs.messageContent;
        if (messageContent) {
          messageContent.scrollTop = messageContent.scrollHeight;
        }
      });
    },
    async getMessage(){
        const id = this.dataMessage.id;
        const type = this.dataMessage.type;
        try {
             const response = await this.$axios.get(`/api/get-message?id=${id}&type=${type}&limit=20`);
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
             this.$emit('move-conv-to-top' , {id:this.userInfo.conversation_id,content : this.newMessage});
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
.message-box {
  display: flex;
  flex-direction: column;
  height: 100%; /* Chiếm toàn bộ chiều cao của container */
}

.message-header {
  flex-shrink: 0; /* Không co lại */
  border-bottom: 1px solid #e5e5e5;
}

.message-content {
  flex: 1; /* Chiếm phần còn lại của không gian */
  overflow-y: auto; /* Cho phép cuộn nội dung */
  padding: 1rem;
  scrollbar-width: none; /* Ẩn thanh cuộn trên Firefox */
  display: flex;
  flex-direction: column-reverse; /* Đảo ngược thứ tự hiển thị */
}

.loading-container {
  height: 100%;
}

.message-content::-webkit-scrollbar {
  display: none; /* Ẩn thanh cuộn trên Chrome */
}

.message-input {
  flex-shrink: 0; /* Không co lại */
  padding: 1rem;
  border-top: 1px solid #e5e5e5;
  background-color: white; /* Đảm bảo phần input không bị mờ hoặc ẩn */
  display: flex;
  align-items: center; /* Căn giữa nội dung theo trục ngang */
}
@media (max-width: 768px) {
  .message-box {
    height: 90vh; /* Chiếm toàn bộ chiều cao thiết bị */
  }

  .message-header {
    padding: 8px; /* Giảm padding cho header trên màn hình nhỏ */
  }

  .message-input {
    padding: 8px; /* Giảm padding cho input */
    flex-direction: row; /* Giữ input và nút gửi trên cùng một dòng */
  }

  .message-input input {
    font-size: 0.875rem; /* Giảm kích thước chữ */
  }

  .message-input button {
    font-size: 0.875rem; /* Giảm kích thước nút gửi */
  }
}

/* Căn phải tin nhắn của tôi */
.my-message-container {
  display: flex;
  justify-content: flex-end;
}

/* Căn trái tin nhắn bạn bè */
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
  word-wrap: break-word;
  font-size: 0.875rem;
  text-align: right;
}

/* Tin nhắn của bạn bè */
.friend-message {
  background-color: #e2e3e5;
  padding: 6px 8px;
  border-radius: 8px;
  display: inline-block;
  max-width: 85%;
  word-wrap: break-word;
  font-size: 0.875rem;
  text-align: left;
}

</style>
