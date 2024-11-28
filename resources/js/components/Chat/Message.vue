<template>
<div class="message-box flex flex-col h-full p-2 sm:p-4">
  <!-- Header: Avatar, Tên, và Trạng thái -->
  <div class="message-header bg-gray-100 p-2 sm:p-4 border-b flex items-center flex-shrink-0">
    <!-- Avatar -->
    <img
      :src="userInfo.avatar"
      alt="Avatar"
      class="w-10 h-10 sm:w-12 sm:h-12 rounded-full mr-3 avatar-message"
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
          <!-- <div v-if="isLoadingMore" class="text-center text-gray-500 py-2 is-loading-more-message">
     Đang tải thêm tin nhắn...
    </div> -->
  <div v-else ref="messageContent" @scroll="scrollLoadMoreMessage" class="message-content flex-1 overflow-y-auto p-2">
    <div v-for="(msg, index) in messages" :key="index" class="mb-2 each-message">
      <div :class="msg.sender === 'me' ? 'my-message-container' : 'friend-message-container'">
        <div   :class="[
                  msg.sender === 'me' ? 'my-message relative group' : 'friend-message relative group',
                  msg.type === 'file' ? 'file-message-box' : '',
                  msg.reactions && msg.reactions.length > 0 ? 'message-have-reaction' : ''
               ]">
          <!-- Kiểm tra nếu msg.content là mảng -->
          <div v-if="msg.type === 'file'">
            <div v-for="(item, index) in JSON.parse(msg.content)" :key="index">
              <!-- Nếu là video -->
              <video v-if="item.type.startsWith('video/')" controls class="preview-video-message" @click="openPreview(item)">
                <source :src="item.url" :type="item.type" />
                Trình duyệt của bạn không hỗ trợ video.
              </video>
              <!-- Nếu là hình ảnh -->
              <img v-else-if="item.type.startsWith('image/')" :src="item.url" alt="Image" class="preview-image-message" @click="openPreview(item)" />
              <!-- Loại khác -->
              <p v-else>
                File không hỗ trợ: {{ item.type }}
              </p>
            </div>
          </div>

          <!-- Nếu msg.content không phải là mảng -->
          <p v-else>
            {{ msg.content }}
          </p>
          <!-- Reactions -->
          <div v-if="msg.reactions && msg.reactions.length > 0" class="reactions flex items-center">
            <div v-for="(reaction, i) in getTopReactions(msg.reactions)" :key="i" class="reaction flex items-center">
              <span>{{ reaction.emoji }}</span>
              <!-- <span class="text-sm text-gray-600">{{ reaction.count }}</span> -->
            </div>
            <!-- <div class="total-reactions text-xs text-gray-500">
              {{ msg.total_reactions }}
            </div> -->
          </div>
             <!-- Nút thêm reaction (hiển thị khi isActive là true) -->
          <div
            class="reaction-button-add-emoji hidden group-hover:block"
          >
           +
          <div class="reaction-picker hidden absolute">
            <!-- Menu emoji -->
            <div
              class="emoji-menu bg-white rounded-full border p-1 shadow-lg absolute bottom-full z-10 flex space-x-1"
            >
              <button
                v-for="emoji in availableReactions"
                :key="emoji"
                @click="addReaction(msg.id, emoji)"
                class="reaction-button text-gray-600 hover:text-blue-500"
              >
                {{ emoji }}
              </button>
            </div>
          </div>
          </div>
          <!-- Nút thêm reaction -->
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
 <div class="form-container">
    <div class="file-preview-container">
      <div
        v-for="(file, index) in previewFiles"
        :key="index"
        class="file-preview"
        :class="{'opacity-50': file.isUploading}"
      >
        <img
          v-if="file.type.startsWith('image/')"
          :src="file.url"
          alt="Preview"
          class="preview-image"
        />
      <video
      v-else-if="file.type.startsWith('video/')"
      :src="file.url"
      controls
      class="preview-video"
    >
    </video>
       <div
        v-if="file.isUploading"
        class="absolute inset-0 bg-gray-100 bg-opacity-70 flex items-center justify-center z-10"
      >
        <div class="spinner border-4 border-t-4 border-gray-300 rounded-full w-6 h-6 animate-spin"></div>
      </div>
        <button
          @click="removePreview(index)"
          class="remove-preview-button"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>
   </div>
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
        <EmojiPicker @select="onSelectEmoji" />
      </div>
    </div>
    <!-- Nút ba chấm dọc -->
    <div class="relative">
      <button
        @click="toggleMenu"
        class="ml-2 px-2 sm:px-3 py-1 sm:py-2 text-gray-600 rounded-full flex items-center justify-center"
      >
        <EllipsisVerticalIcon class="h-6 text-gray-600 hover:text-gray-700" />
      </button>
      <!-- Menu tùy chọn -->
      <transition name="menu-fade">
        <div
          v-if="showMenu"
          class="absolute right-0 bottom-10 bg-white border border-gray-300 rounded shadow-md z-10 w-40 mt-2 animate-slide-up"
        >
          <button @click="sendImage" class="block w-full text-left px-4 py-2 hover:bg-gray-100 flex gap-5">
            <PhotoIcon class="h-6 w-6 text-gray-600 hover:text-gray-700" /> Ảnh
          </button>
          <button @click="sendFile" class="block w-full text-left px-4 py-2 hover:bg-gray-100 flex gap-5">
            <PaperClipIcon class="h-6 w-6 text-gray-600 hover:text-gray-700" /> File
          </button>
          <button @click="startRecording" class="block w-full text-left px-4 py-2 hover:bg-gray-100 flex gap-5">
            <MicrophoneIcon class="h-6 w-6 text-gray-600 hover:text-gray-700" /> Ghi âm
          </button>
        </div>
      </transition>
    </div>
    <!-- Nút gửi -->
    <button @click="sendMessage" class="ml-2 px-2 sm:px-4 py-1 sm:py-2 bg-blue-500 text-white rounded text-sm sm:text-base">
      <PaperAirplaneIcon class="h-6 w-6 text-white-500" />
    </button>
   </div>
 </div>

 <!-- Modal Preview -->
 <div
      v-if="showPreview"
      class="preview-modal fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50"
    >
      <div class="preview-content relative">
        <button
          @click="closePreview"
          class="preview-modal-close"
        >
          &times;
        </button>
        <!-- Hiển thị hình ảnh -->
        <img
          v-if="previewItem.type.startsWith('image/')"
          :src="previewItem.url"
          alt="Preview Image"
          class="max-w-full max-h-screen"
        />
        <!-- Hiển thị video -->
        <video
          v-else-if="previewItem.type.startsWith('video/')"
          controls
          class="max-w-full max-h-screen"
        >
          <source :src="previewItem.url" :type="previewItem.type" />
          Trình duyệt của bạn không hỗ trợ video.
        </video>
      </div>
 </div>
</div>

</template>

<script>
import {formatTimeDifference} from '../../utils/functions.js';
import {
  PaperAirplaneIcon,
  FaceSmileIcon,
  EllipsisVerticalIcon ,
  PhotoIcon,
  PaperClipIcon,
  MicrophoneIcon,
  XMarkIcon
  } from '@heroicons/vue/24/solid'
import EmojiPicker from 'vue3-emoji-picker'
import {
    importPublicKey,
    importPrivateKey,
    encryptMessageWithPublicKey,
    decryptMessageWithPrivateKey
} from "../../utils/functions.js"
import 'vue3-emoji-picker/css'

export default {
  inject: ['$axios','$userProfile','$socket'],
  components:{
    PaperAirplaneIcon,
    FaceSmileIcon,
    EmojiPicker,
    EllipsisVerticalIcon,
    PhotoIcon,
    PaperClipIcon,
    MicrophoneIcon,
    XMarkIcon
  },
  props:{
      dataMessage:{
        type:Object,
        required: true
      }
  },
  data() {
    return {
      previewItem: null,
      showPreview: false,
      previewFiles: [], // Danh sách file preview,
      showMenu : false,
      showEmojiPicker: false,
      availableReactions: ['👍', '❤️', '😂', '😮', '😢', '😡'],
      viewers: [],
      userInfo: {
        id : null,
        name: '', // Tên người dùng
        avatar: 'https://st3.depositphotos.com/1767687/16607/v/450/depositphotos_166074422-stock-illustration-default-avatar-profile-icon-grey.jpg', // Đường dẫn đến avatar
        isOnline: false, // Trạng thái online (true: online, false: offline)
        lastOnline: '', // Thời gian online gần nhất nếu offline
        lastOnlineString: '',
        conversation_id : null,
        publicKey: null
      },
      messages : [],
      updateLastActiveFriendInterval : null,
      newMessage: '', // Tin nhắn mới
      isFriendTyping : false, // Trạng thái đang gõ
      isFriendTypingTimer : null,
      isLoading : true,
      socket :null,
      currentPage: 1, // Trang hiện tại
      totalPages: 0, // Tổng số trang
      isLoadingMore: false, // Đang tải thêm tin nhắn hay không
      hasMoreMessages: true, // Còn tin nhắn để tải hay không
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

        const encryptedContent = e.content[this.$userProfile.id]; // Giải mã field content
        if (encryptedContent) {
            const privateKey = await importPrivateKey(
                  localStorage.getItem("privateKey")
            );
            const decryptedContent = await decryptMessageWithPrivateKey(
                  encryptedContent,
                  privateKey
            );
            //
            this.messages.unshift({ sender,
                  content: decryptedContent,
                  sender_id:e.sender_id ,
                  reactions : [],
                  total_reactions : 0,
                  id: e.message_id,
                  type : e.type
            });
            // Cuộn xuống cuối và tự động kích hoạt trigger nếu cần
            await this.scrollToBottom();
        }
      }
    });

    if (this.$refs.messageContent) {
        this.$refs.messageContent.addEventListener('scroll', this.scrollLoadMoreMessage);
    }

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

    this.socket.on('receive_reaction_message', async (e) => {
      if (parseInt(e.conversation_id) === parseInt(this.userInfo.conversation_id)) {
        const updatedMessage = this.messages.find((m) => parseInt(m.id) === parseInt(e.message_id));
          if(updatedMessage){
            const reaction = updatedMessage.reactions.find((r) => r.emoji === e.emoji);
            if (reaction) {
              reaction.count += 1; // Tăng count nếu reaction đã tồn tại
            } else {
              updatedMessage.reactions.push({ emoji:e.emoji, count: 1 }); // Thêm mới nếu chưa tồn tại
            }
            updatedMessage.total_reactions += 1; // Tăng tổng số reaction
          }
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

    if (this.$refs.messageContent) {
        this.$refs.messageContent.removeEventListener('scroll', this.scrollLoadMoreMessage);
    }
    clearInterval(this.updateLastActiveFriendInterval);
  },
  watch: {
    dataMessage: {
      immediate: true, // Gọi ngay lần đầu khi component được mount
      async handler(newData,oldData) {
         if (!newData || (oldData && newData.id === oldData.id && newData.type === oldData.type)) {
              return; // Nếu không có sự thay đổi thực sự, thoát ra
         }
          // Reset dữ liệu
          if(this.socket && this.userInfo.conversation_id){
            // this.socket.emit('leave_conversation', this.userInfo.conversation_id);
            this.socket = null;
          }
          this.previewItem = null;
          this.showPreview = false;
          this.previewFiles = [];
          this.showMenu = false;
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
            conversation_id : null,
            currentPage: 1, // Trang hiện tại
            totalPages: 0, // Tổng số trang
            isLoadingMore: false, // Đang tải thêm tin nhắn hay không
            hasMoreMessages: true, // Còn tin nhắn để tải hay không
            publicKey: null
          };
          this.socket = this.$socket;
          await this.findConversation();
          await this.getStatusUserOnline();
          await this.getMessage();
          this.socket.emit('join_conversation', this.userInfo.conversation_id);
          this.isLoading = false;
          // đăng ký lại sự kiện khi chuyển component
          this.$nextTick(() => {
            const messageContent = this.$el.querySelector('.message-content');
            if (messageContent) {
              this.scrollToBottomWithTrigger();
            }
          });
      },
    },
  },
  methods: {
    openPreview(item) {
      this.previewItem = item;
      this.showPreview = true;
    },
    // Đóng modal preview
    closePreview() {
      this.showPreview = false;
      this.previewItem = null;
    },
    areAllFilesUploaded() {
      return this.previewFiles.every((file) => !file.isUploading);
    },
    sendImage() {
      // Mở trình chọn file
      const fileInput = document.createElement('input');
      fileInput.type = 'file';
      fileInput.accept = 'image/*,video/*'; // Chỉ cho phép chọn ảnh và video
      fileInput.multiple = true; // Cho phép chọn nhiều tệp
      fileInput.addEventListener('change', async (event) => {
        const files = event.target.files;
        await this.handleFilePreview(files); // Gọi hàm xử lý preview
      });
      fileInput.click();
      this.showMenu = false;
    },
    async handleFilePreview(files) {
      const MAX_SIZE = 20 * 1024 * 1024; // Kích thước tối đa 20MB
      const newPreviews = Array.from(files).map((file) => {
        if (file.size > MAX_SIZE) {
          alert(`File ${file.name} vượt quá kích thước tối đa là 20MB. Vui lòng chọn file nhỏ hơn.`);
          return null; // Bỏ qua file vượt kích thước
        }

        const reader = new FileReader();
        const preview = {
          name: file.name,
          type: file.type,
          url: 'https://static.thenounproject.com/png/4595376-200.png', // Chờ FileReader xử lý
          file, // File gốc
          isUploading: true, // Đang tải
        };

        reader.onload = (e) => {
          preview.url = e.target.result; // Gắn URL tạm thời
        };
        reader.readAsDataURL(file);

        return preview;
      });

      // Loại bỏ file null (do vượt kích thước)
      this.previewFiles.push(...newPreviews.filter((file) => file !== null));

      try {
        // Tải file lên API song song
        await Promise.all(
          this.previewFiles.map(async (file) => {
            if (!file.isUploading) return; // Bỏ qua file đã tải xong

            try {
              // Xác định folder
              const folder = file.type.startsWith('image/')
                ? 'images'
                : file.type.startsWith('video/')
                ? 'videos'
                : 'others';

              const formData = new FormData();
              formData.append('file', file.file);
              formData.append('folder', folder);

              // Gọi API upload file
              const response = await this.$axios.post(`/api/upload-file`, formData);
              const data = response.data;

              // Cập nhật URL từ API
              file.url = data.url;
            } catch (error) {
              console.error('Error uploading file:', error);
            } finally {
              file.isUploading = false; // Hoàn tất trạng thái (thành công hoặc thất bại)
            }
          })
        );
      } catch (error) {
        console.error('Error during file uploads:', error);
      }
    },
    removePreview(index) {
      // Xóa file khỏi danh sách preview
      this.previewFiles.splice(index, 1);
    },
    toggleMenu() {
    this.showMenu = !this.showMenu;
    },
    sendFile() {
      // Logic gửi file
      alert("Chức năng gửi file đang phát triển.");
      this.showMenu = false;
    },
    startRecording() {
      // Logic ghi âm
      alert("Chức năng ghi âm đang phát triển.");
      this.showMenu = false;
    },
    async addReaction(messageId, emoji) {
      try {
        this.socket.emit(`reaction_message`,{
           message_id : messageId,
           responder_id : this.$userProfile.id,
           emoji,
           conversation_id : this.userInfo.conversation_id
        });
         const updatedMessage = this.messages.find((m) => parseInt(m.id) === parseInt(messageId));
          if(updatedMessage){
            const reaction = updatedMessage.reactions.find((r) => r.emoji === emoji);
            if (reaction) {
              reaction.count += 1; // Tăng count nếu reaction đã tồn tại
            } else {
              updatedMessage.reactions.push({ emoji, count: 1 }); // Thêm mới nếu chưa tồn tại
            }
            updatedMessage.total_reactions += 1; // Tăng tổng số reaction
          }
          await this.$axios.post(`/api/add-reaction`, {
            message_id: messageId,
            emoji: emoji,
          });
      } catch (error) {
        console.error('Failed to add reaction:', error);
      }
    },
    getTopReactions(reactions) {
      return [...reactions]
        .sort((a, b) => b.count - a.count) // Sắp xếp giảm dần theo count
        .slice(0, 3); // Lấy 3 reaction đầu tiên
    },
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
      await this.$nextTick(); // Chờ Vue render xong DOM

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
    triggerSeenMessage(latestMessage){
      this.socket.emit('seen_message', {
          viewer_id : this.$userProfile.id,
          conversation_id: this.userInfo.conversation_id,
          name : this.$userProfile.name,
          avatar: this.$userProfile.avatar,
          sender_id : latestMessage.sender_id
      });
    },
    async findConversation() {
      if (this.isDataConvFetching) return; // Nếu đang tải, không thực hiện
      this.isDataConvFetching = true;
      try {
        const id = this.dataMessage.id;
        const type = this.dataMessage.type;
        const response = await this.$axios.get(`/api/get-detail-conversation?id=${id}&type=${type}`);
        if(type === 'private'){
          this.userInfo.publicKey = response.data.public_key;
        }
        this.userInfo.id = response.data.id;
        this.userInfo.avatar = response.data.avatar;
        this.userInfo.name = response.data.name;
        this.userInfo.lastOnline = response.data.last_active;
        this.userInfo.conversation_id = response.data.conversation_id;
        this.userInfo.lastOnlineString = formatTimeDifference(response.data.last_active);
      } catch (error) {
        console.error('GET DATA FAILED:', error);
      } finally {
        this.isDataConvFetching = false; // Reset trạng thái
      }
    },
     async getStatusUserOnline() {
        if(this.isDataUserOnlineFetching){
          return;
        }
        this.isDataUserOnlineFetching = true;
        try {
          const response = await this.$axios.get(`http://localhost:6060/api/is-active?userID=${this.userInfo.id}`);
          this.userInfo.isOnline = response.data.isActive;
        } catch (error) {
          console.error('Failed to fetch online users:', error);
        } finally{
          this.isDataUserOnlineFetching = false;
        }
    },
    handleUserWithStatusFromSocket(user){
        if(parseInt(user.userID) === parseInt(this.userInfo.id)){
          this.userInfo.isOnline = user.online;
          this.userInfo.lastOnline = user.last_active;
          this.userInfo.lastOnlineString = formatTimeDifference(user.last_active);
        }
    },
    scrollLoadMoreMessage() {
      const messageContent = this.$refs.messageContent;
      if(!messageContent) return;
      const isAtTop = Math.ceil(-(messageContent.scrollTop) + messageContent.clientHeight + 1) >= messageContent.scrollHeight;

      if (
        isAtTop // Khi cuộn lên đầu
         &&
        this.hasMoreMessages && // Nếu vẫn còn tin nhắn để tải
        !this.isLoadingMore // Đảm bảo không bị tải nhiều lần cùng lúc
      ) {
        this.isLoadingMore = true;
        const oldScrollTop = messageContent.scrollTop;
        // Tải thêm tin nhắn
        this.getMessage(this.currentPage + 1).finally(() => {
          this.isLoadingMore = false;
        });
        // đang scroll chỗ nào giữ nguyên vị trí đó
        messageContent.scrollTop = oldScrollTop;
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
    async getMessage(page = 1) {
      if (this.isDataMessageFetching) {
        return;
      }
      this.isDataMessageFetching = true;
      const id = this.dataMessage.id;
      const type = this.dataMessage.type;

      try {
        const response = await this.$axios.get(
          `/api/get-message?id=${id}&type=${type}&limit=20&page=${page}`
        );
        const { data, current_page, last_page, total } = response.data;

        // Giải mã tin nhắn ngay khi nhận được từ server
        let dataConverted = data;
        if (type === "private") {
        const decryptedMessages = await Promise.all(
            data.map(async (message) => {
                try {
                  const encryptedContent = message.content[this.$userProfile.id]; // Giải mã field content
                  if (encryptedContent) {
                    const privateKey = await importPrivateKey(
                      localStorage.getItem("privateKey")
                    );
                    const decryptedContent = await decryptMessageWithPrivateKey(
                      encryptedContent,
                      privateKey
                    );
                    return {
                      ...message,
                      content: decryptedContent,
                    };
                  }
                } catch (error) {
                  console.error("Decryption failed for message ID:", message.id, error);
                }
              return {
                ...message,
                content: "Không thể giải mã", // Hiển thị thông báo nếu giải mã thất bại
              };
            })
          );
          dataConverted = decryptedMessages;
        }
        // Cập nhật danh sách tin nhắn
        if (page === 1) {
          this.messages = dataConverted; // Lấy tin nhắn mới nhất
        } else {
          this.messages = [...this.messages, ...dataConverted]; // Thêm tin nhắn cũ vào
        }

        // Cập nhật trạng thái phân trang
        this.currentPage = current_page;
        this.totalPages = last_page;
        this.totalMessages = total;
        this.hasMoreMessages = current_page < last_page;

        if (page === 1) {
          this.viewers = response.data.viewers;
          this.scrollToBottom(); // Cuộn xuống cuối cùng
        }
      } catch (error) {
        console.error("Get Failed With Message:", error);
      } finally {
        this.isDataMessageFetching = false;
      }
    },
    async sendMessage() {
        if (!this.areAllFilesUploaded()) {
          return;
        }
        if (this.newMessage.trim() !== '' || this.previewFiles.length > 0) {
            try {
                let messageSend;
                let fileSend;
                let typeMessage;
                if (this.dataMessage.type === 'private') {
                    let message = this.newMessage;
                    try {
                        // Mã hóa tin nhắn
                        if(this.newMessage.trim() !== ''){
                          const senderEncryptedMessage = await encryptMessageWithPublicKey(
                              message,
                              await importPublicKey(this.$userProfile.public_key)
                          );
                          const receiverEncryptedMessage = await encryptMessageWithPublicKey(
                              message,
                              await importPublicKey(this.userInfo.publicKey)
                          );

                          messageSend = {
                            [this.$userProfile.id]: senderEncryptedMessage,
                            [this.userInfo.id]: receiverEncryptedMessage,
                          };
                        }

                        if(this.previewFiles.length > 0){
                           const users = [
                            {
                              id: this.$userProfile.id,
                              publicKey: this.$userProfile.public_key
                            },
                            {
                              id: this.userInfo.id,
                              publicKey: this.userInfo.publicKey
                            }
                           ];
                           const json = {};

                           const promises = users.map(async (user) => {
                            const files = this.previewFiles.map((value) => ({
                              url: value.url,
                              type: value.type,
                            }));
                            const fileJson = JSON.stringify(files);
                            const filesCrypted = await encryptMessageWithPublicKey(
                              fileJson,
                              await importPublicKey(user.publicKey)
                            );
                            json[user.id] = filesCrypted;
                           });

                           await Promise.all(promises);
                           fileSend = json;

                        }
                    } catch (encryptionError) {
                        console.error("Error during encryption:", encryptionError);
                        throw encryptionError; // Ném lỗi để xử lý bên dưới
                    }
                } else {
                    messageSend = this.newMessage;
                }

                if(this.newMessage.trim() !== '' && !fileSend){
                  typeMessage = 'text';
                  const response = await this.$axios.post(`/api/save-message`, {
                      conversation_id: this.userInfo.conversation_id,
                      content: messageSend,
                      type: 'text',
                      type_conversation : this.dataMessage.type
                  });

                  this.socket.emit(`send_message`, {
                      conversation_id: this.userInfo.conversation_id,
                      sender_id: this.$userProfile.id,
                      content: messageSend,
                      message_id: response.data.id,
                      type: 'text'
                  });
                }else if(this.newMessage.trim() === '' && fileSend){
                  typeMessage = 'file';
                  const response = await this.$axios.post(`/api/save-message`, {
                      conversation_id: this.userInfo.conversation_id,
                      content: fileSend,
                      type: 'file',
                      type_conversation : this.dataMessage.type
                  });

                  this.socket.emit(`send_message`, {
                      conversation_id: this.userInfo.conversation_id,
                      sender_id: this.$userProfile.id,
                      content: fileSend,
                      message_id: response.data.id,
                      type: 'file'
                  });
                }else if(this.newMessage.trim() !== '' && fileSend){
                  typeMessage = 'file';
                  const response = await this.$axios.post(`/api/save-message-with-file`, {
                    message_text : {
                      conversation_id: this.userInfo.conversation_id,
                      content: messageSend,
                      type: 'text',
                      type_conversation : this.dataMessage.type
                     },
                    message_file : {
                      conversation_id: this.userInfo.conversation_id,
                      content: fileSend,
                      type: 'file',
                      type_conversation : this.dataMessage.type
                     }
                  });

                  this.socket.emit(`send_message`, {
                      conversation_id: this.userInfo.conversation_id,
                      sender_id: this.$userProfile.id,
                      content: messageSend,
                      message_id: response.data.text_message_id,
                      type: 'text'
                  });

                  this.socket.emit(`send_message`, {
                      conversation_id: this.userInfo.conversation_id,
                      sender_id: this.$userProfile.id,
                      content: fileSend,
                      message_id: response.data.file_message_id,
                      type: 'file'
                  });
                }
                this.viewers = [];
                this.$emit('move-conv-to-top', {
                    id: this.userInfo.conversation_id,
                    content: typeMessage === 'text' ? this.newMessage : fileSend,
                    type: typeMessage
                });

                this.newMessage = '';
                this.previewFiles = [];
            } catch (error) {
                console.error("Error in sendMessage function:", error);
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
.avatar-message{
  object-fit: cover;
}
/* Nút đóng (X) bên ngoài modal */
.preview-modal-close {
  position: fixed; /* Giữ nút cố định ở một góc */
  top: 10px; /* Khoảng cách từ trên cùng của trang */
  right: 10px; /* Khoảng cách từ phải của trang */
  background-color: rgba(0, 0, 0, 0.8); /* Màu nền đen trong suốt */
  color: white; /* Màu chữ trắng */
  width: 40px; /* Chiều rộng nút */
  height: 40px; /* Chiều cao nút */
  border: none; /* Xóa viền */
  border-radius: 50%; /* Bo tròn để tạo hình tròn */
  display: flex;
  justify-content: center;
  font-size: 1.5rem; /* Kích thước chữ lớn */
  cursor: pointer;
  z-index: 100; /* Luôn nổi trên các thành phần khác */
  transition: transform 0.3s ease, background-color 0.3s ease;
}

.preview-modal-close:hover {
  transform: scale(1.1); /* Phóng to nhẹ khi hover */
  background-color: rgba(255, 0, 0, 0.9); /* Đổi nền sang đỏ đậm */
}

/* Đối với toàn bộ modal */
.preview-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.9); /* Làm tối nền modal */
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
  overflow: auto; /* Cho phép cuộn nếu nội dung lớn */
}

.preview-content {
  position: relative;
  max-width: 90%;
  max-height: 90%;
  overflow: auto; /* Cho phép cuộn nội dung trong modal */
  background: rgba(255, 255, 255, 0.05); /* Màu nền nhạt trong suốt */
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); /* Đổ bóng mềm */
}

/* Tùy chỉnh thanh cuộn */
.preview-modal::-webkit-scrollbar {
  width: 12px; /* Độ rộng của thanh cuộn */
}

.preview-modal::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1); /* Màu nền track thanh cuộn */
  border-radius: 6px;
}

.preview-modal::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.4); /* Màu thanh cuộn */
  border-radius: 6px; /* Bo tròn góc thanh cuộn */
  border: 2px solid rgba(0, 0, 0, 0.9); /* Viền bên ngoài thanh cuộn */
}

.preview-modal::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.6); /* Màu khi hover vào thanh cuộn */
}

/* Đối với trình duyệt không hỗ trợ Webkit (Firefox) */
.preview-modal {
  scrollbar-width: thin; /* Đặt thanh cuộn mỏng */
  scrollbar-color: rgba(255, 255, 255, 0.4) rgba(0, 0, 0, 0.9); /* Thanh cuộn và track */
}

.preview-image-message,
.preview-video-message {
  width: 280px; /* Chiều rộng cố định */
  height: 200px; /* Chiều cao cố định */
  border-radius: 12px; /* Bo tròn góc mềm mại */
  object-fit: cover; /* Đảm bảo video/hình ảnh vừa khung */
  border: 1px solid #ccc; /* Viền mỏng màu xám */
  /* box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); */
  overflow: hidden;
  position: relative;
  background-color: #f9f9f9; /* Màu nền nhạt */
}

/* Căn chỉnh video và hình ảnh cho từng tin nhắn */
.friend-message .preview-image-message,
.friend-message .preview-video-message,
.my-message .preview-image-message,
.my-message .preview-video-message {
  display: block; /* Đảm bảo các phần tử nằm trên từng dòng */
}

/* Hiệu ứng hover (tăng thêm tính thẩm mỹ khi người dùng rê chuột) */
/* .preview-video-message:hover,
.preview-image-message:hover {
  transform: scale(1.05);
  transition: transform 0.3s ease;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
} */

/* Video thumbnail (nếu muốn tuỳ chỉnh thêm) */
.preview-video-message::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 50px;
  height: 50px;
  background: url('https://cdn-icons-png.flaticon.com/512/725/725003.png') no-repeat center center; /* Biểu tượng phát */
  background-size: contain;
  opacity: 0.8;
  pointer-events: none; /* Không ảnh hưởng đến click */
}

.file-preview.opacity-50 {
  opacity: 0.5;
  pointer-events: none; /* Ngăn tương tác khi đang tải */
}

.file-preview-container {
  display: flex;
  gap: 10px;
  margin-top: 10px;
  flex-wrap: wrap;
  padding-left: 38px;
  overflow-y: auto;
  max-height: 220px;

  /* Ẩn thanh cuộn */
  scrollbar-width: none; /* Ẩn scrollbar trên Firefox */
  -ms-overflow-style: none; /* Ẩn scrollbar trên IE/Edge */
}

.file-preview-container::-webkit-scrollbar {
  display: none; /* Ẩn scrollbar trên Chrome, Safari và Edge */
}

.file-preview {
  position: relative;
  width: 100px;
  height: 100px;
}

.preview-image,
.preview-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #ccc;
}

.remove-preview-button {
  position: absolute;
  top: 0;
  right: 0;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  width: 20px;
  height: 20px;
  color: red;
}

.send-preview-button {
  margin-top: 10px;
  padding: 5px 10px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
.send-preview-button:hover {
  background-color: #0056b3;
}

.menu-fade-enter-active,
.menu-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.menu-fade-enter-from,
.menu-fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
.menu-fade-enter-to,
.menu-fade-leave-from {
  opacity: 1;
  transform: translateY(0);
}

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

.message-content .is-loading-more-message{
  order: -1 !important;
}
.message-content .each-message{
   order: 0  !important;
}

.loading-container {
  height: 100%;
}

.message-content::-webkit-scrollbar {
  display: none; /* Ẩn thanh cuộn trên Chrome */
}

.form-container{
   background-color: white;
   border-radius: 10px;
}

.message-input {
  flex-shrink: 0; /* Không co lại */
  padding:0 1rem 1rem 1rem;
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
  background-color: #0084FF;
  border-radius: 8px;
  display: inline-block;
  max-width: 85%;
  color: white;
  min-width: 10%;
  word-wrap: break-word;
  font-size: 1.1rem;
  text-align: left;
  cursor: pointer;
}

/* Tin nhắn của bạn bè */
.friend-message {
  background-color: #F0F0F0;
  border-radius: 8px;
  display: inline-block;
  max-width: 85%;
  min-width: 10%;
  word-wrap: break-word;
  font-size: 1.1rem;
  text-align: left;
  cursor: pointer;
}

.file-message-box{
  padding: 0 !important;
  background: none;
}
.file-message-box >image{
  width: 100% !important;
  height: 100% !important;

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

.my-message,
.friend-message {
  position: relative;
  padding: 8px;
}
/* Nút thêm reaction (dấu cộng) */
.reaction-button-add-emoji {
  text-align: center;
  position: absolute;
  justify-content: center;
  bottom: -12px; /* Căn dưới tin nhắn */
  right: -12px; /* Căn phải tin nhắn */
  width: 30px; /* Kích thước nút */
  height: 30px;
  background-color: #ffffff !important; /* Đảm bảo nền trắng */
  border: 2px solid #d1e7ff; /* Viền xanh nhạt */
  color: #007bff; /* Màu chữ xanh lam */
  border-radius: 50%; /* Hình tròn */
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15); /* Đổ bóng nhẹ */
  cursor: pointer;
  transition: all 0.3s ease; /* Hiệu ứng hover */
  z-index: 10; /* Luôn nằm trên nội dung tin nhắn */
}

.reactions {
  display: flex;
  align-items: center;
  position: absolute;
  width: auto;
  margin-top: -5px;
  border-radius: 20px;
}

.file-message-box  .reactions{
  z-index: 10;
  bottom: -10px;
}

.my-message .reactions{
  left: 0 !important;
}

.friend-message .reactions{
  right: -9px !important;
}

.message-have-reaction{
 margin-bottom: 20px;
}

.reaction {
  display: flex;
  align-items: center;
  font-size: 1rem;
  border-radius: 4px;
  margin-left:-8px ;
}

.reaction-button {
  font-size: 1.5rem;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0 6px;
}

.reaction-button:hover {
  transform: scale(1.1);
}

.group:hover .reaction-button {
  opacity: 1; /* Hiện khi hover */
}

.emoji-menu {
  display: flex;
  gap: 4px;
  padding: 4px;
}

.emoji-menu .reaction-button {
  font-size: 1.25rem;
  margin: 0;
  padding: 2px;
}

.my-message .reaction-button-add-emoji:hover .reaction-picker{
  left: -13.6rem;
  display: block;
}

.reaction-button-add-emoji:hover .reaction-picker{
  left: 1.6rem;
  display: block;
}

.friend-message:hover .reaction-button-add-emoji{
  bottom: -12px; /* Căn dưới tin nhắn */
  left: -12px; /* Căn phải tin nhắn */
}
</style>
