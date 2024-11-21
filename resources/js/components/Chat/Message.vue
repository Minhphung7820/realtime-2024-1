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

      <!-- Hiển thị danh sách avatar sau tin nhắn cuối cùng của bạn -->
      <div v-if="msg.sender === 'me' && index === 0" class="seen-avatars flex items-center mt-1">
        <div
          v-for="(viewer, i) in viewers.slice(0, 5)"
          :key="viewer.id"
          class="w-6 h-6 rounded-full overflow-hidden border-2 border-white -ml-2"
          :style="{ zIndex: viewers.length - i }"
        >
          <img
            :src="viewer.avatar"
            alt="viewer avatar"
            class="w-full h-full object-cover"
          />
        </div>
        <div v-if="viewers.length > 5" class="extra-viewers text-sm text-gray-600 ml-2">
          +{{ viewers.length - 5 }}
        </div>
      </div>
    </div>
  </div>

  <div v-if="isFriendTyping" class="typing-indicator text-gray-500 italic mt-2">
      Người bên kia đang gõ...
  </div>
  <!-- Input: Nhập và gửi tin nhắn -->
  <div class="message-input mt-2 flex items-center flex-shrink-0 relative">
    <div class="input-container relative flex-1">
      <input
        v-model="newMessage"
        @keydown="sendTypingEvent"
        @keydown.enter.prevent="sendMessage"
        type="text"
        placeholder="Nhập tin nhắn..."
        class="w-full p-2 sm:p-3 border rounded text-sm sm:text-base focus:outline-none pr-10"
      />
      <!-- Nút Emoji -->
      <button
        @click="toggleEmojiPicker"
        class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
      >
          <FaceSmileIcon class="h-8 w-8 text-blue-500" />
      </button>
      <!-- Emoji Picker -->
      <div
        v-if="showEmojiPicker"
        class="emoji-picker-container absolute bottom-12 right-0"
      >
        <EmojiPicker :display-recent="true" @select="onSelectEmoji" />
      </div>
    </div>
    <!-- Nút gửi -->
    <button @click="sendMessage" class="ml-2 px-2 sm:px-4 py-1 sm:py-2 bg-blue-500 text-white rounded text-sm sm:text-base">
      <PaperAirplaneIcon class="h-6 w-6 text-white-500" />
    </button>
  </div>
</div>

</template>

<script>
import {formatTimeDifference} from '../../utils/functions.js';
import { PaperAirplaneIcon,FaceSmileIcon } from '@heroicons/vue/24/solid'
import EmojiPicker from 'vue3-emoji-picker'
import 'vue3-emoji-picker/css'

export default {
  inject: ['$axios','$userProfile','$socket'],
  components:{
    PaperAirplaneIcon,
    FaceSmileIcon,
    EmojiPicker
  },
  props:{
      dataMessage:{
        type:Object,
        required: true
      }
  },
  data() {
    return {
      showEmojiPicker: false,
      viewers: [
      // {
      //   id: 1,
      //   name: 'John Doe',
      //   avatar: 'https://i.pravatar.cc/50?img=1' // URL mẫu cho avatar
      // }
      ],
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
     await this.findConversation();
     await this.getStatusUserOnline();
     await this.getMessage();
     this.socket.on('user_list',this.handleUserWithStatusFromSocket);
     this.socket.on('user_disconnect_list', this.handleUserWithStatusFromSocket);
     this.socket.emit('join_conversation', this.userInfo.conversation_id);

    this.socket.on('receive_message', async (e) => {
      if (parseInt(e.conversation_id) === parseInt(this.userInfo.conversation_id)) {
        const sender = parseInt(e.sender_id) === parseInt(this.$userProfile.id) ? 'me' : 'friend';
        this.messages.unshift({ sender, content: e.content,sender_id:e.sender_id });

        // Cuộn xuống cuối và tự động kích hoạt trigger nếu cần
        await this.scrollToBottom();
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

    if (this.$refs.messageContent) {
      const messageContent = this.$refs.messageContent;
      messageContent.addEventListener('scroll', this.scrollToBottomWithTrigger);
    }

    this.socket.on(`seen_message`,(e) =>{
         const isSeen = this.viewers.some(viewer => parseInt(viewer.id) === parseInt(e.viewer_id));
         if(!isSeen && parseInt(e.conversation_id) === parseInt(this.userInfo.conversation_id)){
            this.viewers.push({
              id: e.viewer_id,
              name: e.name,
              avatar:e.avatar
            });
         }
    });
    //
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
          this.viewers = [];
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
          await this.findConversation();
          await this.getStatusUserOnline();
          await this.getMessage();
          this.socket.emit('join_conversation', this.userInfo.conversation_id);
          this.isLoading = false;
          this.scrollToBottom();
        }
      },
    },
  },
  methods: {
    toggleEmojiPicker() {
      this.showEmojiPicker = !this.showEmojiPicker;
      if (this.showEmojiPicker) {
        document.addEventListener('click', this.closeEmojiPickerOnBlur, { capture: true });
      } else {
        document.removeEventListener('click', this.closeEmojiPickerOnBlur, { capture: true });
      }
    },
    closeEmojiPickerOnBlur(event) {
      const emojiPicker = this.$el.querySelector('.emoji-picker-container');
      if (
        emojiPicker &&
        !emojiPicker.contains(event.target) // Kiểm tra nếu click không nằm trong Emoji Picker
      ) {
        this.showEmojiPicker = false;
        document.removeEventListener('click', this.closeEmojiPickerOnBlur, { capture: true });
      }
    },
    onSelectEmoji(event) {
      if (event && event.i) {
        this.newMessage += event.i; // Thêm emoji vào nội dung tin nhắn
      }
    },
    async scrollToBottomWithTrigger() {
      const messageContent = this.$refs.messageContent;
      if (!messageContent) return;

      // Kiểm tra nếu đã cuộn đến cuối
      const isAtBottom = messageContent.scrollTop === 0;
      if (isAtBottom) {

        // Bắn sự kiện seen_message
        const latestMessage = this.messages[0];
        if(latestMessage && parseInt(latestMessage.sender_id) !== parseInt(this.$userProfile.id)){
          this.triggerSeenMessage(latestMessage);
          this.$emit(`reset-unread`,{
            id:this.userInfo.conversation_id,
            message_id: latestMessage.id
          });
          try {
             await this.$axios.post(`/api/seen-message`,{
              conversation_id : this.userInfo.conversation_id
             });
          } catch (error) {
             console.log("Seen Failed",error);

          }
        }
      }
    },
    triggerSeenMessage(latestMessage)
    {
      this.socket.emit('seen_message', {
          viewer_id : this.$userProfile.id,
          conversation_id: this.userInfo.conversation_id,
          name : this.$userProfile.name,
          avatar: this.$userProfile.avatar,
          sender_id : latestMessage.sender_id
      });
    },
    async findConversation()
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
        this.userInfo.lastOnlineString = formatTimeDifference(response.data.last_active);
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
          this.userInfo.lastOnlineString = formatTimeDifference(user.last_active);
        }
    },
    async scrollToBottom() {
      await this.$nextTick(); // Đảm bảo DOM đã được render trước khi thực hiện
      const messageContent = this.$refs.messageContent;
      if (messageContent) {
        messageContent.scrollTop = 0; // Cuộn xuống cuối
        this.scrollToBottomWithTrigger();
      } else {
        console.warn('messageContent is not available yet.');
      }
    },
    async getMessage(){
        const id = this.dataMessage.id;
        const type = this.dataMessage.type;
        try {
             const response = await this.$axios.get(`/api/get-message?id=${id}&type=${type}&limit=20`);
             this.messages = response.data.data;
             this.viewers = response.data.viewers;
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
             this.viewers = [];
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
          this.userInfo.lastOnlineString = formatTimeDifference(this.userInfo.lastOnline);
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

.seen-avatars {
  margin-top: 8px; /* Khoảng cách giữa tin nhắn và danh sách avatar */
  display: flex;
  justify-content: flex-end; /* Căn sang phải */
  align-items: center; /* Căn giữa theo chiều dọc */
}

.seen-avatars img {
  border-radius: 50%;
  box-shadow: 0 0 0 2px white;
}

.seen-avatars .extra-viewers {
  margin-left: 4px;
  font-weight: bold;
  color: #666;
}

.message-content .seen-avatars {
  margin-top: 8px; /* Khoảng cách giữa tin nhắn và danh sách avatar */
}

.input-container {
  position: relative;
}

.input-container input {
  padding-right: 2.5rem; /* Dành không gian cho nút emoji */
}

.input-container button {
  background: none;
  border: none;
  font-size: 1.25rem;
  line-height: 1;
  cursor: pointer;
  padding: 0;
}

.input-container button:hover {
  color: #007bff; /* Màu hover giống Messenger */
}

.emoji-picker-container {
  z-index: 10;
  background: white;
  border: 1px solid #e5e5e5;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

</style>
