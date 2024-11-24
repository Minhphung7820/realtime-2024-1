<template>
  <div>
    <h3 class="font-bold text-lg mb-2">Cuộc trò chuyện</h3>
    <div v-if="conversations.length">
      <transition-group
        name="smooth-move"
        tag="ul"
        class="conversation-list"
      >
        <li
          v-for="(conversation, index) in conversations"
          :key="conversation.conversation_id"
          class="conversation-item flex items-center p-3 border-b cursor-pointer hover:bg-gray-200"
          @click="openChat(conversation.id, 'private')"
        >
          <!-- Avatar and status dot container -->
          <div class="relative mr-3">
            <!-- Avatar -->
            <img :src="conversation.avatar" alt="Avatar" class="w-10 h-10 rounded-full" />

            <!-- Trạng thái online/offline -->
            <span
              :class="conversation.isOnline ? 'bg-green-500' : 'bg-gray-400'"
              class="w-3 h-3 rounded-full absolute bottom-0 right-0 border-2 border-white"
            ></span>
          </div>

          <!-- Nội dung trò chuyện -->
          <div class="flex-1">
            <div class="flex justify-between items-center">
              <h4 class="font-semibold">{{ conversation.name }}</h4>
              <!-- Hiển thị số tin nhắn chưa đọc -->
              <span
                v-if="conversation.unread > 0"
                class="unread-count text-xs text-white bg-red-500 rounded-full px-2 py-0.5"
              >
                {{ conversation.unread > 5 ? '5+' : conversation.unread }}
              </span>
            </div>
            <p class="text-gray-500 text-sm truncate">
              <span v-if="!conversation.lastMessage" class="font-bold">
                Bắt đầu trò chuyện nào!
              </span>
              <span v-else>
                <strong v-if="conversation.sender === 'me'">Bạn:</strong> {{ conversation.lastMessage }}
              </span>
            </p>
          </div>
        </li>
      </transition-group>
    </div>
    <div v-else class="text-center text-gray-500 py-4">
      Chưa có cuộc trò chuyện nào
    </div>
  </div>
</template>

<script>
import { onlineStore } from "../../stores/UserOnline.js";
import {encodeQueryParams} from '../../utils/functions.js';
import {
    importPrivateKey,
    decryptMessageWithPrivateKey
} from "../../utils/functions.js"
export default {
  inject: ['$axios','$userProfile','$socket'],
  props:{
     dataMoveConvToTop:{
        type: Object,
        required : false
     },
     dataResetUnread: {
        type: Object,
        required : false
     }
  },
  data() {
    return {
      conversations: [],
      isLoading : true,
      socket: null
    };
  },
  watch: {
    dataMoveConvToTop: {
      immediate: true, // Lắng nghe ngay từ khi component được mount
      handler(newData) {
        this.moveConvToTop(newData);
      },
    },
    dataResetUnread:{
      immediate: true,
      handler(newData) {
        this.resetUnread(newData);
      },
    }
  },
  async mounted()
  {
    this.socket = this.$socket;
    await this.getConversations();
    this.socket.on('user_list',this.handleUserWithStatus);
    this.socket.on('user_disconnect_list', this.handleUserWithStatus);
   //
   this.conversations.forEach((convo) => {
      this.socket.emit('join_conversation', convo.conversation_id); // Join tất cả các phòng của user
   });

  this.socket.on(`receive_noti_change_friend_request`, (e) => {
    if (e.status === 'accepted') {
         this.getConversations();
         this.socket.emit('join_conversation', e.conversation_id);
    }
  });
   //
    this.socket.on('receive_message', async (e) => {

      const matchingConversation = this.conversations.find(convo => parseInt(convo.conversation_id) === parseInt(e.conversation_id));
         if(matchingConversation){
            if (parseInt(e.sender_id) !== parseInt(this.$userProfile.id)) {
              if (this.$parent.dataMessage.id !== parseInt(e.conversation_id)) {
                // Nếu cuộc trò chuyện không được mở, tăng số lượng tin nhắn chưa đọc
                const encryptedContent = e.content[this.$userProfile.id]; // Giải mã field content
                if (encryptedContent) {
                    const privateKey = await importPrivateKey(
                          localStorage.getItem("privateKey")
                    );
                    const decryptedLastMessage = await decryptMessageWithPrivateKey(
                          encryptedContent,
                          privateKey
                    );
                    //
                    matchingConversation.lastMessage = decryptedLastMessage;
                    matchingConversation.unread = (matchingConversation.unread || 0) + 1;
                    matchingConversation.sender = 'friend';
                    this.moveConvToTop({id:e.conversation_id});
                }
              } else {
                // Nếu cuộc trò chuyện đang được mở, có thể xử lý tin nhắn ngay tại đây
                console.log("Tin nhắn mới trong cuộc trò chuyện đang mở:", e.content);
              }
            }else{
                matchingConversation.sender = 'me';
            }
         }
    });
   //
    this.isLoading = false;
  },
  computed:{
    onlineStoreData() {
      return onlineStore().data;
    },
    onlineStoreLoading() {
      return onlineStore().isLoading; // Trạng thái đang tải
    },
  },
  methods: {
    resetUnread(data)
    {
      const convReset = this.conversations.find(conv => parseInt(conv.conversation_id) === parseInt(data.id));
      if(convReset){
         convReset.unread = 0;
      }
    },
    async moveConvToTop(objectConv) {
      if (Object.keys(objectConv).length > 0) {
        // Kiểm tra xem conversation đã tồn tại hay chưa
        const conversationIndex = this.conversations.findIndex(
          (convo) => parseInt(convo.conversation_id) === parseInt(objectConv.id)
        );

        if (conversationIndex !== -1) {
          // Nếu đã tồn tại, di chuyển cuộc trò chuyện lên đầu
          if (objectConv.content) {
            this.conversations[conversationIndex].lastMessage = objectConv.content;
          }
          const [movedConversation] = this.conversations.splice(conversationIndex, 1);
          this.conversations.unshift(movedConversation);

          // Đảm bảo reactivity để Vue trigger lại hiệu ứng
          this.conversations = [...this.conversations];
        }else{
          await this.getConversations();
          this.socket.emit('join_conversation', objectConv.id);
          this.conversations = [...this.conversations];
        }
      }
    },
    async openChat(userId, type) {
      if (this.$parent.dataMessage.id === userId && this.$parent.dataMessage.type === type) {
        // Nếu người dùng đang mở chính họ, không làm gì cả
        return;
      }
      this.$router.push({ query: {messages: encodeQueryParams({id:userId,type})}});
       // Reset số tin nhắn chưa đọc
      const matchingConversation = this.conversations.find(convo => parseInt(convo.id) === parseInt(userId));
      if (matchingConversation) {
          matchingConversation.unread = 0;
      }
      //
      this.$emit('open-chat', userId, type); // Phát sự kiện open-chat lên cha
    },
    async getConversations() {
      try {
        // Lấy danh sách các cuộc trò chuyện
        const conversationResponse = await this.$axios.get(`/api/get-list-conversation`);

        const decryptedConversation = await Promise.all(
          conversationResponse.data.map(async (conversation) => {
            if (conversation.type === "private") {
              let lastMessageDecrypt;
              try {
                const encryptedContent = JSON.parse(conversation.lastMessage)[this.$userProfile.id]; // Giải mã field content
                  if (encryptedContent) {
                    const privateKey = await importPrivateKey(
                      localStorage.getItem("privateKey")
                    );
                    const decryptedLastMessage = await decryptMessageWithPrivateKey(
                      encryptedContent,
                      privateKey
                    );
                    lastMessageDecrypt = decryptedLastMessage;
                  }
                  return {
                    ...conversation,
                    lastMessage: lastMessageDecrypt,
                  };
              } catch (error) {
                lastMessageDecrypt =  "Không thể giải mã!";
              }
              return {
                ...conversation,
                lastMessage: lastMessageDecrypt, // Hiển thị thông báo nếu giải mã thất bại
              };
            }
            if (conversation.type === "group") {
              return {
                ...conversation,
              };
            }
          })
        );

        this.conversations = decryptedConversation;

        // Lấy danh sách người dùng online
        const onlineUsers = this.onlineStoreData.data;

        // Cập nhật trạng thái online vào mảng conversations
        onlineUsers.forEach(user => {
          const matchingPerson = this.conversations.find(person => parseInt(person.id) === parseInt(user.userID)
          && person.type === 'private');
          if (matchingPerson) {
            matchingPerson.isOnline = user.isOnline;
          }
        });
      } catch (error) {
        console.error('Failed to fetch conversations and users:', error);
      }
    },
    handleUserWithStatus(user) {
      const matchingPerson = this.conversations.find(person => parseInt(person.id) === parseInt(user.userID)
      && person.type ==='private');
      if (matchingPerson) {
        matchingPerson.isOnline = user.online;
      }
    },
  }
};
</script>

<style scoped>
/* Định nghĩa hiệu ứng chuyển động */
.smooth-move-enter-active,
.smooth-move-leave-active {
  transition: transform 0.5s ease-in-out;
}

.smooth-move-enter-from,
.smooth-move-leave-to {
  transform: translateY(20px);
}

.smooth-move-move {
  transition: transform 0.5s ease-in-out;
}

.conversation-item {
  display: flex;
  align-items: center;
}

.conversation-item .flex-1 {
  max-width: 100%; /* Giới hạn kích thước của container */
  overflow: hidden; /* Ẩn nội dung tràn */
  display: flex; /* Cần thiết để đảm bảo bố cục flex hoạt động đúng */
  flex-direction: column; /* Để văn bản xếp dọc */
}

.conversation-item p {
  display: block;           /* Đảm bảo hiển thị dưới dạng khối */
  white-space: nowrap;      /* Không cho phép xuống dòng */
  overflow: hidden;         /* Ẩn phần văn bản bị tràn */
  text-overflow: ellipsis;  /* Hiển thị dấu "..." khi nội dung quá dài */
  max-width: 100%;          /* Đảm bảo không vượt quá chiều rộng container */
}

/* Loại bỏ lớp .status-dot nếu không cần thiết */
/* .status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
} */

.unread-count {
  background-color: red;
  color: white;
  font-weight: bold;
  border-radius: 9999px;
  padding: 2px 6px;
}
</style>
