<template>
  <div class="people-list bg-gray-50 p-2 sm:p-4">
    <h3 class="font-bold text-lg mb-2">{{ tabTitle }}</h3>

    <!-- Tabs -->
    <div class="tabs flex border-b mb-4">
         <!-- Tab Đang hoạt động -->
      <button
        class="tab px-4 py-2"
        :class="{ 'border-b-2 border-blue-500 font-bold': activeTab === 'online' }"
        @click="activeTab = 'online'"
      >
        <GlobeAltIcon class="w-6 h-6 text-blue-500" />
      </button>
      <button
        class="tab px-4 py-2"
        :class="{ 'border-b-2 border-blue-500 font-bold': activeTab === 'friends' }"
        @click="activeTab = 'friends'"
      >
        <UsersIcon class="w-6 h-6 text-blue-500" />
      </button>
      <button
        class="tab px-4 py-2 relative"
        :class="{ 'border-b-2 border-blue-500 font-bold': activeTab === 'requests' }"
        @click="activeTab = 'requests'"
      >
        <UserPlusIcon class="w-6 h-6 text-blue-500" />
        <span
          v-if="countRequestFriend > 0"
          class="absolute top-0 right-0 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full"
        >
          {{ countRequestFriend > 9 ? '9+' : countRequestFriend }}
        </span>
      </button>
      <button
        class="tab px-4 py-2"
        :class="{ 'border-b-2 border-blue-500 font-bold': activeTab === 'search' }"
        @click="activeTab = 'search'"
      >
        <UserGroupIcon class="w-6 h-6 text-blue-500" />
      </button>
    </div>


    <!-- Nội dung tab -->
    <div v-if="activeTab === 'online'" class="min-h-[400px]">
      <!-- Danh sách Đang hoạt động -->
      <div v-if="isLoading" class="loading-container">
        <div class="spinner"></div>
      </div>
      <ul v-else>
        <li
          v-for="(person, index) in onlineUsers"
          :key="index"
          class="people-item flex items-center p-2 sm:p-3 border-b cursor-pointer hover:bg-gray-200"
          @click="openChat(person.id, 'private')"
        >
          <!-- Avatar and status dot container -->
          <div class="relative mr-3">
            <!-- Avatar -->
            <img :src="person.avatar" alt="Avatar" class="avatar w-10 h-10 sm:w-12 sm:h-12 rounded-full avatar-message" />
            <!-- Trạng thái online -->
            <span class="bg-green-500 w-3 h-3 rounded-full absolute bottom-0 right-0 border-2 border-white"></span>
          </div>

          <!-- Nội dung -->
          <div class="flex-1">
            <h4 class="font-semibold text-sm sm:text-base truncate">{{ person.name }}</h4>
          </div>
        </li>
        <li v-if="onlineUsers.length === 0" class="text-center py-4 text-gray-500">Chưa có ai</li>
      </ul>
    </div>
    <!-- Nội dung các tab -->
    <div v-if="activeTab === 'friends'" class="min-h-[400px]">
      <!-- Danh sách bạn bè (cũ) -->
      <div v-if="isLoading" class="loading-container">
        <div class="spinner"></div>
      </div>
      <ul v-else>
        <li
          v-for="(person, index) in people"
          :key="index"
          class="people-item flex items-center p-2 sm:p-3 border-b cursor-pointer hover:bg-gray-200"
          @click="openChat(person.id, 'private')"
        >
          <!-- Avatar and status dot container -->
          <div class="relative mr-3">
            <!-- Avatar -->
            <img :src="person.avatar" alt="Avatar" class="avatar w-10 h-10 sm:w-12 sm:h-12 rounded-full avatar-message" />
            <!-- Trạng thái online/offline -->
            <!-- <span
              :class="person.isOnline ? 'bg-green-500' : 'bg-gray-400'"
              class="w-3 h-3 rounded-full absolute bottom-0 right-0 border-2 border-white"
            ></span> -->
          </div>

          <!-- Nội dung -->
          <div class="flex-1">
            <h4 class="font-semibold text-sm sm:text-base truncate">{{ person.name }}</h4>
            <!-- <p class="text-xs text-gray-400 mt-1 truncate">
              {{ !person.isOnline ? person.last_active_string : '' }}
            </p> -->
          </div>
        </li>
        <li v-if="onlineUsers.length === 0" class="text-center py-4 text-gray-500">Chưa có ai</li>
      </ul>
       <!-- Nút Xem thêm -->
      <div class="flex justify-center mt-4">
        <button
          v-if="!isLoading && hasMoreFriend"
          @click="loadMoreFriend"
          :disabled="isLoadingMoreFriend"
          class="see-more-btn flex items-center justify-center px-4 py-2 border border-gray-500 rounded-full hover:bg-gray-100 transition-colors"
        >
          <!-- Biểu tượng (icon) -->
          <span v-if="!isLoadingMoreFriend" class="icon-container mr-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </span>

          <!-- Vòng tròn xoay nếu đang tải -->
          <span v-if="isLoadingMoreFriend" class="loader w-5 h-5 border-2 border-t-2 border-gray-500 rounded-full animate-spin"></span>

          <!-- Text -->
          <span v-else>Xem thêm</span>
        </button>
      </div>
    </div>

    <div v-else-if="activeTab === 'requests'" class="min-h-[400px]">
      <!-- UI Yêu cầu kết bạn -->
      <ul>
        <li
          v-for="(request, index) in friendRequests"
          :key="index"
          class="people-item flex items-center p-2 sm:p-3 border-b cursor-pointer hover:bg-gray-200"
        >
          <img
            :src="request.avatar"
            alt="Avatar"
            class="avatar w-8 h-8 sm:w-10 sm:h-10 rounded-full mr-2 sm:mr-3"
          />
          <div class="flex-1">
            <h4 class="font-semibold text-sm sm:text-base truncate">
              {{ request.name }}
            </h4>
            <div class="flex space-x-2 mt-1">
                <!-- Nút Chấp nhận -->
                <button
                  v-if="isHandlingChangeStatusRequest[request.id] === 'accepted'"
                  disabled
                  class="px-2 py-1 bg-gray-400 text-white rounded text-xs cursor-not-allowed"
                >
                  Đang xử lý
                </button>
                <button
                  v-else
                  @click="changeStatusRequestFriend(request.id, 'accepted')"
                  class="px-2 py-1 bg-blue-500 text-white rounded text-xs"
                >
                  Chấp nhận
                </button>

                <!-- Nút Từ chối -->
                <button
                  v-if="isHandlingChangeStatusRequest[request.id] === 'blocked'"
                  disabled
                  class="px-2 py-1 bg-gray-400 text-white rounded text-xs cursor-not-allowed"
                >
                  Đang xử lý
                </button>
                <button
                  v-else
                  @click="changeStatusRequestFriend(request.id, 'blocked')"
                  class="px-2 py-1 bg-red-500 text-white rounded text-xs"
                >
                  Từ chối
                </button>
            </div>
          </div>
        </li>
        <li v-if="friendRequests.length === 0" class="text-center py-4 text-gray-500">Chưa có ai</li>
      </ul>
    </div>

    <div v-else-if="activeTab === 'search'" class="min-h-[400px]">
      <!-- UI Tìm kiếm -->
      <div class="search-container mb-4">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm người dùng..."
          class="w-full px-3 py-2 border rounded"
          @keyup.enter="getOtherUser"
        />
      </div>
      <div v-if="isLoading" class="loading-container">
        <div class="spinner"></div>
      </div>
      <ul v-else>
        <li
          v-for="(user, index) in searchResults"
          :key="index"
          class="people-item flex items-center p-2 sm:p-3 border-b cursor-pointer hover:bg-gray-200"
        >
          <img
            :src="user.avatar"
            alt="Avatar"
            class="avatar w-8 h-8 sm:w-10 sm:h-10 rounded-full mr-2 sm:mr-3"
          />
          <div class="flex-1">
            <h4 class="font-semibold text-sm sm:text-base truncate">{{ user.name }}</h4>
            <!-- Nút Kết bạn -->
            <button
              v-if="user.status_friend === 'not_friend'"
              @click="sendFriendRequest(user.id)"
              class="px-2 py-1 bg-blue-500 text-white rounded text-xs"
            >
              Kết bạn
            </button>
            <!-- Nút chờ chấp nhận -->
            <button
              v-else-if="user.status_friend === 'pending'"
              disabled
              class="px-2 py-1 bg-blue-500 text-white rounded text-xs cursor-not-allowed"
            >
              Chờ chấp nhận
            </button>
            <!-- Hiển thị bạn bè với dấu tick -->
            <div
              v-else-if="user.status_friend === 'accepted'"
              class="flex items-center text-green-500 text-sm font-semibold"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5 mr-1"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              Bạn bè
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import { onlineStore } from "../../stores/UserOnline.js";
import {formatTimeDifference,encodeQueryParams} from '../../utils/functions.js';
import { UserPlusIcon,UsersIcon,UserGroupIcon,GlobeAltIcon } from '@heroicons/vue/24/solid';

export default {
  components: {
    UserPlusIcon,UsersIcon,UserGroupIcon,GlobeAltIcon
  },
  inject: ['$axios', '$socket','$userProfile'],
  data() {
    return {
      onlineUsers : [],
      people: [],
      // updateLastActiveInterval : null,
      isLoading: true,
      activeTab: 'online',
      friendRequests:[], // Danh sách yêu cầu kết bạn (mẫu)
      searchQuery: '', // Dữ liệu input tìm kiếm
      searchResults:[], // Kết quả tìm kiếm (mẫu)
      countRequestFriend : 0,
      currentPageFriend: 1, // Trang hiện tại
      totalPagesFriend: 0, // Tổng số trang
      isLoadingMoreFriend: false, // Đang tải thêm tin nhắn hay không
      hasMoreFriend: true, // Còn tin nhắn để tải hay không,
      isHandlingChangeStatusRequest: {}
    };
  },
  computed: {
    onlineStoreData() {
      return onlineStore().data;
    },
    onlineStoreLoading() {
      return onlineStore().isLoading; // Trạng thái đang tải
    },
    tabTitle() {
      switch (this.activeTab) {
        case 'online':
          return 'Đang hoạt động';
        case 'friends':
          return 'Bạn bè';
        case 'requests':
          return 'Lời mời kết bạn';
        case 'search':
          return 'Tìm kiếm tài khoản';
        default:
          return 'Mọi người';
      }
    }
  },
  async mounted() {
    await Promise.all([
        this.fetchPeopleWithStatus(),
        this.getRequestFriend(),
        this.onlines()
    ]);

    // Lắng nghe sự kiện từ WebSocket
    this.$socket.on('user_list',this.handleUserWithStatus);
    this.$socket.on('user_disconnect_list', this.handleUserWithStatus);
    this.$socket.on('receive_friend_request', (data) => {
        const { sender_id, receiver_id } = data;
        this.getRequestFriend();
        this.countRequestFriend ++;
    });

    this.$socket.on(`receive_noti_change_friend_request`,async (e) => {
      if (e.status === 'accepted') {
        await onlineStore().fetchData(true);
        await Promise.all([
          this.fetchPeopleWithStatus(),
          this.onlines()
        ]);
      }
  });
    // Chạy hàm cập nhật last_active mỗi giây
    // this.updateLastActiveInterval = setInterval(() => {
    //     this.updateLastActive();
    // }, 1000);
    this.isLoading = false;
  },
  beforeUnmount() {
    // Dừng interval khi component bị hủy
    // clearInterval(this.updateLastActiveInterval);
  },
  methods: {
    async onlines()
    {
      try {
        const users = await this.$axios.get(`/api/get-people?getAll=true`);
        this.onlineUsers = this.onlineStoreData.data
          .filter((onlineUser) => onlineUser.isOnline)
          .map((onlineUser) => {
            return users.data.find((user) => parseInt(user.id) === parseInt(onlineUser.userID));
          })
          .filter((user) => user);
      } catch (error) {
        console.error("GET DATA FAILED :",error);
      }
    },
    loadMoreFriend(){
     if(!this.isLoadingMoreFriend){
          this.isLoadingMoreFriend = true;
          this.fetchPeopleWithStatus(this.currentPageFriend + 1).finally(() => {
          this.isLoadingMoreFriend = false;
        });
     }
    },
    async fetchPeopleWithStatus(page = 1) {
      try {
        // Gọi API để lấy danh sách người dùng
        const limitPeople = 10;
        const peopleResponse = await this.$axios.get(`/api/get-people?limit=${limitPeople}&page=${page}`);
        //
        const { data, current_page, last_page, total } = peopleResponse.data;
        //
        const onlineUsers = this.onlineStoreData.data;
        // Các người dùng còn lại không có last_active_string
        if(page === 1){
          this.people = data.map((person, index) => {
          const userSocket = onlineUsers.find(user => parseInt(user.userID) === parseInt(person.id));
          return {
            ...person,
            isOnline: userSocket.isOnline,
            last_active_string: index < 9 ? (person.last_active
              ? formatTimeDifference(person.last_active)
              : null) : null,
          };
        });
        }else{
          this.people = [...this.people, ...data].map((person, index) => {
          const userSocket = onlineUsers.find(user => parseInt(user.userID) === parseInt(person.id));
          return {
            ...person,
            isOnline: userSocket.isOnline,
            last_active_string: index <= 9 ? (person.last_active
              ? formatTimeDifference(person.last_active)
              : null) : null,
          };
        });
        }
        //
        this.currentPageFriend = current_page,
        this.hasMoreFriend= current_page < last_page
        // Kết hợp danh sách
      } catch (error) {
        console.error('Failed to fetch people or online users:', error);
      }
    },
    openChat(userId, type) {
      if (this.$parent.dataMessage.id === userId && this.$parent.dataMessage.type === type) {
        // Nếu người dùng đang mở chính họ, không làm gì cả
        return;
      }
      this.$router.push({ query: {messages: encodeQueryParams({id:userId,type})}});
      this.$emit('open-chat', userId, type); // Phát sự kiện open-chat lên cha
    },
    async changeStatusRequestFriend(requestId,status){
          this.isHandlingChangeStatusRequest[requestId] = status;
         try {
         const responese =  await this.$axios.put(`/api/change-request-friend/${requestId}`,{
            status
          });
          this.friendRequests = this.friendRequests.filter(request => request.id !== requestId);

          this.countRequestFriend = this.friendRequests.length;
          await onlineStore().fetchData(true);
          await Promise.all([
            this.fetchPeopleWithStatus(),
            this.onlines()
          ]);

          this.$socket.emit(`noti_change_friend_request`,{
            sender_id:this.$userProfile.id,
            receiver_id:responese.data.user_id,
            status:status,
            conversation_id:responese.data.conversation_id
          });

          if(status === 'accepted'){
              this.$emit('move-conv-to-top' , {id:responese.data.conversation_id,content : null,type: 'text'});
          }
        } catch (error) {
          console.error('Failed to fetch online users:', error);
        } finally {
         delete this.isHandlingChangeStatusRequest[requestId];
      }
    },
    async getRequestFriend()
    {
        try {
          const response = await this.$axios.get(`/api/get-request-friend`);
          this.friendRequests = response.data.data;
          this.countRequestFriend = response.data.total;
        } catch (error) {
          console.error('Failed to fetch online users:', error);
        }
    },
    async sendFriendRequest(userID) {
      try {
        await this.$axios.post(`/api/send-request-friend`, {
          friend_id: userID,
        });
        const data = {
          sender_id: this.$userProfile.id,
          receiver_id: userID,
        };
        this.$socket.emit('send_friend_request', data);

        // Cập nhật trạng thái của người dùng trong danh sách searchResults
        const user = this.searchResults.find((u) => u.id === userID);
        if (user) {
          user.status_friend = 'pending';
        }
      } catch (error) {
        console.error('Failed to send friend request:', error);
      }
    },
    async getOtherUser()
    {
      try {
        this.isLoading = true;
        const response = await this.$axios.get(`/api/get-other-user`, {
          params: {
            keyword: this.searchQuery
          }
        });
        this.searchResults = response.data.data;
        this.isLoading = false;
      } catch (error) {
        console.error('Failed to fetch online users:', error);
      }
    },
    handleUserWithStatus(user) {
      const matchingPerson = this.people.find(person => parseInt(person.id) === parseInt(user.userID));
      if(!user.online){
         this.onlineUsers = this.onlineUsers.filter(person => parseInt(person.id) !== parseInt(user.userID));
      }else{
         this.onlineUsers.unshift({
            id:user.userID,
            name: user.name,
            avatar: user.avatar,
            last_active: user.last_active
         });
      }
      if (matchingPerson) {
        matchingPerson.isOnline = user.online;
        matchingPerson.last_active = user.last_active;
        matchingPerson.last_active_string = formatTimeDifference(user.last_active)
      }
    },
    // Hàm cập nhật last_active cho từng người online
    // updateLastActive() {
    //   this.people.slice(0, 10).forEach(person => {
    //     if (!person.isOnline && person.last_active) {
    //       person.last_active_string = formatTimeDifference(person.last_active);
    //     }
    //   });
    // }
  }
};
</script>

<style scoped>
.avatar-message{
  object-fit: cover;
}

.cursor-not-allowed {
  cursor: not-allowed;
}
.bg-gray-400 {
  background-color: #cbd5e0; /* Màu xám nhạt */
}

.people-item {
  display: flex;
  align-items: center;
  padding: 0.5rem 1rem;
  border-bottom: 1px solid #e5e7eb; /* Màu viền */
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.people-item:hover {
  background-color: #f3f4f6; /* Màu nền khi hover */
}

.avatar {
  width: 40px; /* Kích thước avatar */
  height: 40px;
  border-radius: 50%; /* Bo tròn */
  object-fit: cover; /* Đảm bảo hình ảnh không bị méo */
}

.relative {
  position: relative; /* Để sử dụng absolute bên trong */
}

.absolute {
  position: absolute; /* Đặt vị trí tuyệt đối */
}

.bottom-0 {
  bottom: 0; /* Dính đáy */
}

.right-0 {
  right: 0; /* Dính phải */
}

.flex-1 {
  flex: 1; /* Tận dụng không gian còn lại */
}

.text-gray-400 {
  color: #9ca3af; /* Màu xám cho text */
}

.text-xs {
  font-size: 0.75rem; /* Kích thước chữ nhỏ */
}

.mt-1 {
  margin-top: 0.25rem; /* Khoảng cách giữa các phần */
}

.font-semibold {
  font-weight: 600; /* Đậm vừa */
}

.text-sm {
  font-size: 0.875rem; /* Cỡ chữ nhỏ */
}

.text-base {
  font-size: 1rem; /* Cỡ chữ cơ bản */
}

.truncate {
  overflow: hidden; /* Ẩn phần text thừa */
  text-overflow: ellipsis; /* Thêm dấu "..." khi text quá dài */
  white-space: nowrap; /* Không xuống dòng */
}

.border-b {
  border-bottom-width: 1px;
}

.cursor-pointer {
  cursor: pointer;
}

.hover\:bg-gray-200:hover {
  background-color: #e5e7eb;
}

.p-2 {
  padding: 0.5rem; /* Khoảng cách padding */
}

.p-3 {
  padding: 0.75rem;
}

.sm\:p-3 {
  padding: 0.75rem;
}

.bg-gray-50 {
  background-color: #f9fafb; /* Nền xám nhạt */
}

.bg-green-500 {
  background-color: #22c55e; /* Màu xanh trạng thái online */
}

.bg-gray-400 {
  background-color: #9ca3af; /* Màu xám trạng thái offline */
}

.rounded-full {
  border-radius: 9999px; /* Bo tròn hoàn toàn */
}

.transition {
  transition: all 0.3s ease; /* Hiệu ứng mượt khi hover */
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
}

.spinner {
  border: 4px solid #f3f4f6; /* Màu viền nhạt */
  border-top: 4px solid #3b82f6; /* Màu xanh quay */
  border-radius: 50%;
  width: 24px;
  height: 24px;
  animation: spin 1s linear infinite; /* Hiệu ứng quay */
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
/* CSS button see more */
.see-more-btn {
  background-color: white; /* Nền trắng */
  color: black; /* Màu chữ đen */
  border: 1px solid #d1d5db; /* Viền xám nhạt */
  font-size: 16px; /* Kích thước chữ */
  cursor: pointer;
}

.see-more-btn:hover {
  background-color: #f3f4f6; /* Màu nền khi hover */
}

.see-more-btn:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

/* Vòng tròn xoay */
.loader {
  border-top-color: transparent;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
/*END CSS button see more */
</style>
