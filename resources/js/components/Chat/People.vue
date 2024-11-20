<template>
  <div class="people-list bg-gray-50 p-2 sm:p-4">
    <h3 class="font-bold text-lg mb-2">{{ tabTitle }}</h3>

    <!-- Tabs -->
    <div class="tabs flex border-b mb-4">
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

    <!-- Nội dung các tab -->
    <div v-if="activeTab === 'friends'">
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
            <img :src="person.avatar" alt="Avatar" class="avatar w-10 h-10 sm:w-12 sm:h-12 rounded-full" />
            <!-- Trạng thái online/offline -->
            <span
              :class="person.isOnline ? 'bg-green-500' : 'bg-gray-400'"
              class="w-3 h-3 rounded-full absolute bottom-0 right-0 border-2 border-white"
            ></span>
          </div>

          <!-- Nội dung -->
          <div class="flex-1">
            <h4 class="font-semibold text-sm sm:text-base truncate">{{ person.name }}</h4>
            <p class="text-xs text-gray-400 mt-1 truncate">
              {{ !person.isOnline ? person.last_active_string : '' }}
            </p>
          </div>
        </li>
      </ul>
    </div>

    <div v-else-if="activeTab === 'requests'">
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
              <button @click="changeStatusRequestFriend(request.id, 'accepted')" class="px-2 py-1 bg-blue-500 text-white rounded text-xs">Chấp nhận</button>
              <button @click="changeStatusRequestFriend(request.id, 'blocked')" class="px-2 py-1 bg-red-500 text-white rounded text-xs">Từ chối</button>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <div v-else-if="activeTab === 'search'">
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
import {formatTimeDifference,encodeQueryParams} from '../../utils/functions.js';
import { UserPlusIcon,UsersIcon,UserGroupIcon } from '@heroicons/vue/24/solid';

export default {
  components: {
    UserPlusIcon,UsersIcon,UserGroupIcon
  },
  inject: ['$axios', '$socket','$userProfile'],
  data() {
    return {
      people: [],
      updateLastActiveInterval : null,
      isLoading: true,
      activeTab: 'friends',
      friendRequests:[], // Danh sách yêu cầu kết bạn (mẫu)
      searchQuery: '', // Dữ liệu input tìm kiếm
      searchResults:[], // Kết quả tìm kiếm (mẫu)
      countRequestFriend : 0
    };
  },
  computed: {
    tabTitle() {
      switch (this.activeTab) {
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
    await this.fetchPeopleWithStatus();
    await this.getRequestFriend();
    // Lắng nghe sự kiện từ WebSocket
    this.$socket.on('user_list',this.handleUserWithStatus);
    this.$socket.on('user_disconnect_list', this.handleUserWithStatus);
    this.$socket.on('receive_friend_request', (data) => {
        const { sender_id, receiver_id } = data;
        this.getRequestFriend();
        this.countRequestFriend ++;
    });

    this.$socket.on(`receive_noti_change_friend_request`, (e) => {
      if (e.status === 'accepted') {
        this.fetchPeopleWithStatus();
      }
  });
    // Chạy hàm cập nhật last_active mỗi giây
    this.updateLastActiveInterval = setInterval(() => {
        this.updateLastActive();
    }, 1000);
    this.isLoading = false;
  },
  beforeUnmount() {
    // Dừng interval khi component bị hủy
    clearInterval(this.updateLastActiveInterval);
  },
  methods: {
    async fetchPeopleWithStatus() {
      try {
        // Gọi API để lấy danh sách người dùng
        const limitPeople = 10;
        const peopleResponse = await this.$axios.get(`/api/get-people?limit=${limitPeople}`);
        const onlineUsersResponse = await this.$axios.get('http://localhost:6060/api/online-users');
        const onlineUsers = onlineUsersResponse.data.data;
        // Chỉ xử lý last_active_string cho 10 người đầu tiên
        const firstTenPeople = peopleResponse.data.data.slice(0, 10).map(person => {
        const isOnlineUser = onlineUsers.find(user => parseInt(user.userID) === parseInt(person.id));
          return {
            ...person,
            isOnline: !!isOnlineUser, // Kiểm tra nếu có trong danh sách online
            last_active_string: person.last_active
              ? formatTimeDifference(person.last_active)
              : null,
          };
        });

        // Các người dùng còn lại không có last_active_string
        const remainingPeople = peopleResponse.data.data.slice(10).map(person => {
          const isOnlineUser = onlineUsers.find(user => user.userID === person.id);
          return {
            ...person,
            isOnline: !!isOnlineUser, // Kiểm tra nếu có trong danh sách online
            last_active_string: null, // Không có giá trị
          };
        });

        // Kết hợp danh sách
        this.people = [...firstTenPeople, ...remainingPeople];
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
         try {
         const responese =  await this.$axios.put(`/api/change-request-friend/${requestId}`,{
            status
          });
          this.friendRequests = this.friendRequests.filter(request => request.id !== requestId);

          this.countRequestFriend = this.friendRequests.length;
         await this.fetchPeopleWithStatus();

          this.$socket.emit(`noti_change_friend_request`,{
            sender_id:this.$userProfile.id,
            receiver_id:responese.data.user_id,
            status:status,
            conversation_id:responese.data.conversation_id
          });

          if(status === 'accepted'){
              this.$emit('move-conv-to-top' , {id:responese.data.conversation_id,content : null});
          }
        } catch (error) {
          console.error('Failed to fetch online users:', error);
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
      const matchingPerson = this.people.find(person => person.id === parseInt(user.userID));
      if (matchingPerson) {
        matchingPerson.isOnline = user.online;
        matchingPerson.last_active = user.last_active;
        matchingPerson.last_active_string = formatTimeDifference(user.last_active)
      }
    },
    // Hàm cập nhật last_active cho từng người online
    updateLastActive() {
      this.people.slice(0, 10).forEach(person => {
        if (!person.isOnline && person.last_active) {
          person.last_active_string = formatTimeDifference(person.last_active);
        }
      });
    }
  }
};
</script>

<style scoped>
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

</style>
